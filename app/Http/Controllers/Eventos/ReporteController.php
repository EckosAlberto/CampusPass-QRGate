<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Mail\ReporteGenerado;
use App\Models\Carrera;
use App\Models\EventoTutorias;
use App\Models\RegistroAcceso;
use App\Models\Reporte;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private const POR_PAGINA = 10;

    private const TOPE_DETALLE = 500;

    private const ETIQUETAS_TIPO = [
        'resumen_general' => 'Resumen general',
        'detallado' => 'Detallado',
    ];

    /** Pantalla de Reportes: la lista de reportes ya generados. */
    public function index(Request $request): Response
    {
        $reportes = Reporte::query()
            ->with(['evento', 'generadoPor'])
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('eventos/reportes', [
            'usuarioId' => $request->user('eventos')->id,
            'reportes' => [
                'data' => collect($reportes->items())->map(fn (Reporte $reporte) => $this->serializar($reporte))->all(),
                'meta' => [
                    'paginaActual' => $reportes->currentPage(),
                    'ultimaPagina' => $reportes->lastPage(),
                    'total' => $reportes->total(),
                    'desde' => $reportes->firstItem(),
                    'hasta' => $reportes->lastItem(),
                ],
            ],
            'catalogos' => [
                'eventos' => EventoTutorias::query()->orderByDesc('fecha')->get()
                    ->map(fn (EventoTutorias $evento) => ['value' => $evento->id, 'label' => $evento->nombre])->all(),
                'carreras' => $this->opcionesCarreras(),
                'tipos' => collect(self::ETIQUETAS_TIPO)
                    ->map(fn (string $etiqueta, string $valor) => ['value' => $valor, 'label' => $etiqueta])
                    ->values()->all(),
            ],
        ]);
    }

    /** Botón "Generar reporte": calcula y guarda un reporte nuevo con los filtros elegidos. */
    public function store(Request $request): RedirectResponse
    {
        $filtros = $request->validate([
            'fecha_inicial' => ['required', 'date'],
            'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicial'],
            'fk_id_evento' => ['nullable', 'uuid', 'exists:evento_tutorias,id'],
            'carrera' => ['nullable', 'string', 'max:3'],
            'tipo' => ['required', 'in:resumen_general,detallado'],
        ]);

        [$datos, $totalRegistros] = $this->calcularDatos($filtros);

        $etiquetaTipo = self::ETIQUETAS_TIPO[$filtros['tipo']];
        $nombre = sprintf(
            'Reporte %s (%s – %s)',
            $etiquetaTipo,
            Carbon::parse($filtros['fecha_inicial'])->format('d/m/Y'),
            Carbon::parse($filtros['fecha_final'])->format('d/m/Y'),
        );

        $reporte = Reporte::create([
            'nombre' => $nombre,
            'tipo' => $filtros['tipo'],
            'fk_id_evento' => $filtros['fk_id_evento'] ?? null,
            'carrera' => $filtros['carrera'] ?? null,
            'fecha_inicial' => $filtros['fecha_inicial'],
            'fecha_final' => $filtros['fecha_final'],
            'registros' => $totalRegistros,
            'datos' => $datos,
            'fk_generado_por' => $request->user('eventos')->id,
        ]);

        return redirect()->route('eventos.reportes.show', $reporte);
    }

    /** Vista previa de un reporte guardado. */
    public function show(Reporte $reporte): Response
    {
        return Inertia::render('eventos/reporte-vista-previa', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ]);
    }

    /** Botón "Eliminar": solo lo puede borrar quien lo generó. */
    public function destroy(Request $request, Reporte $reporte): RedirectResponse
    {
        if ($reporte->fk_generado_por !== $request->user('eventos')->id) {
            abort(403, 'Solo la persona que generó el reporte puede eliminarlo.');
        }

        $reporte->delete();

        return back();
    }

    /** Botón "Descargar PDF". */
    public function descargarPdf(Reporte $reporte): StreamedResponse
    {
        $pdf = $this->generarPdf($reporte);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf;
            },
            $this->nombreArchivo($reporte, 'pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /** Botón "Descargar Excel". */
    public function exportarExcel(Reporte $reporte): StreamedResponse
    {
        $writer = $this->generarExcel($reporte);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $this->nombreArchivo($reporte, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * El nombre del reporte incluye fechas con "/" (p. ej. "08/09/2026"), que
     * rompen la cabecera Content-Disposition — se sanean para el archivo.
     */
    private function nombreArchivo(Reporte $reporte, string $extension): string
    {
        $nombre = str_replace(['/', '\\'], '-', $reporte->nombre);

        return "{$nombre}.{$extension}";
    }

    /** Botón "Compartir": envía el PDF del reporte por correo. */
    public function compartir(Request $request, Reporte $reporte): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
        ]);

        Mail::to($datos['correo'])->send(new ReporteGenerado($reporte, $this->generarPdf($reporte)));

        return back()->with('success', "Reporte enviado a {$datos['correo']}.");
    }

    /**
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function calcularDatos(array $filtros): array
    {
        $registros = RegistroAcceso::query()
            ->whereNotNull('id_evento')
            ->whereBetween(DB::raw('DATE(fecha_hora)'), [$filtros['fecha_inicial'], $filtros['fecha_final']])
            ->when($filtros['fk_id_evento'] ?? null, fn ($query, $eventoId) => $query->where('id_evento', $eventoId))
            ->when($filtros['carrera'] ?? null, fn ($query, $carrera) => $query->whereHas('alumno', fn ($q) => $q->where('carrera', $carrera)))
            ->with(['alumno', 'evento'])
            ->orderBy('fecha_hora')
            ->get();

        $datos = [
            'indicadores' => [
                'asistentesRegistrados' => $registros->pluck('no_de_control')->unique()->count(),
                'entradas' => $registros->where('tipo_movimiento', 'ENTRADA')->count(),
                'salidas' => $registros->where('tipo_movimiento', 'SALIDA')->count(),
            ],
            'porCarrera' => $this->agruparPorCarrera($registros),
            'serieDiaria' => $this->agruparPorDia($registros),
        ];

        if ($filtros['tipo'] === 'detallado') {
            $datos['detalle'] = $registros->take(self::TOPE_DETALLE)->map(fn (RegistroAcceso $registro) => [
                'fecha' => $registro->fecha_hora->format('d/m/Y'),
                'hora' => $registro->fecha_hora->format('h:i A'),
                'nombre' => $registro->alumno?->nombreCompleto() ?? 'Desconocido',
                'noDeControl' => $registro->no_de_control,
                'carrera' => $registro->alumno?->siglasCarrera(),
                'evento' => $registro->evento?->nombre,
                'tipoMovimiento' => $registro->tipo_movimiento,
            ])->values()->all();
        }

        return [$datos, $registros->count()];
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array<string, mixed>>
     */
    private function agruparPorCarrera(Collection $registros): array
    {
        return $registros->groupBy(fn (RegistroAcceso $registro) => $registro->alumno?->carrera ?? '—')
            ->map(fn (Collection $grupo) => [
                'carrera' => $grupo->first()->alumno?->nombreCarrera() ?? 'Sin carrera',
                'alumnos' => $grupo->pluck('no_de_control')->unique()->count(),
                'entradas' => $grupo->where('tipo_movimiento', 'ENTRADA')->count(),
                'salidas' => $grupo->where('tipo_movimiento', 'SALIDA')->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{fecha: string, etiqueta: string, total: int}>
     */
    private function agruparPorDia(Collection $registros): array
    {
        return $registros->groupBy(fn (RegistroAcceso $registro) => $registro->fecha_hora->format('Y-m-d'))
            ->map(fn (Collection $grupo, string $fecha) => [
                'fecha' => $fecha,
                'etiqueta' => Carbon::parse($fecha)->translatedFormat('d/M'),
                'total' => $grupo->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function opcionesCarreras(): array
    {
        return Carrera::query()
            ->selectRaw('carrera, MIN(nombre_carrera) as nombre_carrera')
            ->groupBy('carrera')
            ->orderBy('nombre_carrera')
            ->get()
            ->map(fn (Carrera $carrera) => [
                'value' => $carrera->carrera,
                'label' => $carrera->nombre_carrera ?? $carrera->carrera,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(Reporte $reporte): array
    {
        return [
            'id' => $reporte->id,
            'nombre' => $reporte->nombre,
            'tipo' => $reporte->tipo,
            'tipoLabel' => self::ETIQUETAS_TIPO[$reporte->tipo] ?? $reporte->tipo,
            'periodo' => $reporte->fecha_inicial->format('d/m/Y').' – '.$reporte->fecha_final->format('d/m/Y'),
            'fechaGeneracion' => $reporte->created_at->format('d/m/Y h:i A'),
            'generadoPor' => $reporte->generadoPor?->name,
            'registros' => $reporte->registros,
            'creadoPor' => $reporte->fk_generado_por,
        ];
    }

    /** Arma el PDF del reporte a partir de los datos ya guardados. */
    public function generarPdf(Reporte $reporte): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reportes.pdf', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ])->render());
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generarExcel(Reporte $reporte): Xlsx
    {
        $datos = $reporte->datos;

        $spreadsheet = new Spreadsheet;
        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $resumen->fromArray([
            ['Reporte', $reporte->nombre],
            ['Periodo', $reporte->fecha_inicial->format('d/m/Y').' - '.$reporte->fecha_final->format('d/m/Y')],
            ['Asistentes registrados', $datos['indicadores']['asistentesRegistrados']],
            ['Entradas', $datos['indicadores']['entradas']],
            ['Salidas', $datos['indicadores']['salidas']],
            [],
            ['Carrera', 'Alumnos', 'Entradas', 'Salidas'],
        ], null, 'A1');

        $fila = 8;

        foreach ($datos['porCarrera'] as $carrera) {
            $resumen->fromArray([$carrera['carrera'], $carrera['alumnos'], $carrera['entradas'], $carrera['salidas']], null, "A{$fila}");
            $fila++;
        }

        if (! empty($datos['detalle'])) {
            $detalle = $spreadsheet->createSheet();
            $detalle->setTitle('Detalle');
            $detalle->fromArray(['Fecha', 'Hora', 'Nombre', 'No. Control', 'Carrera', 'Evento', 'Movimiento'], null, 'A1');

            $fila = 2;

            foreach ($datos['detalle'] as $registro) {
                $detalle->fromArray([
                    $registro['fecha'],
                    $registro['hora'],
                    $registro['nombre'],
                    $registro['noDeControl'],
                    $registro['carrera'],
                    $registro['evento'],
                    $registro['tipoMovimiento'],
                ], null, "A{$fila}");
                $fila++;
            }
        }

        return new Xlsx($spreadsheet);
    }
}
