<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Mail\ReporteGraduacionGenerado;
use App\Models\BoletoGraduacion;
use App\Models\Carrera;
use App\Models\CeremoniaGraduacion;
use App\Models\RegistroAccesoGraduacion;
use App\Models\ReporteGraduacion;
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

    /** Pantalla de Reportes */
    public function index(Request $request): Response
    {
        $reportes = ReporteGraduacion::query()
            ->with(['ceremonia', 'generadoPor'])
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('graduacion/reportes', [
            'usuarioId' => $request->user('graduacion')->id,
            'reportes' => [
                'data' => collect($reportes->items())->map(fn (ReporteGraduacion $reporte) => $this->serializar($reporte))->all(),
                'meta' => [
                    'paginaActual' => $reportes->currentPage(),
                    'ultimaPagina' => $reportes->lastPage(),
                    'total' => $reportes->total(),
                    'desde' => $reportes->firstItem(),
                    'hasta' => $reportes->lastItem(),
                ],
            ],
            'catalogos' => [
                'ceremonias' => CeremoniaGraduacion::query()->orderByDesc('fecha_inicio')->get()
                    ->map(fn (CeremoniaGraduacion $ceremonia) => ['value' => $ceremonia->id, 'label' => $ceremonia->nombre])->all(),
                'carreras' => $this->opcionesCarreras(),
                'tipos' => collect(self::ETIQUETAS_TIPO)
                    ->map(fn (string $etiqueta, string $valor) => ['value' => $valor, 'label' => $etiqueta])
                    ->values()->all(),
            ],
        ]);
    }

    /** Botón "Generar reporte" */
    public function store(Request $request): RedirectResponse
    {
        $filtros = $request->validate([
            'fecha_inicial' => ['required', 'date'],
            'fecha_final' => ['required', 'date', 'after_or_equal:fecha_inicial'],
            'fk_id_ceremonia' => ['nullable', 'uuid', 'exists:ceremonias_graduacion,id'],
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

        $reporte = ReporteGraduacion::create([
            'nombre' => $nombre,
            'tipo' => $filtros['tipo'],
            'fk_id_ceremonia' => $filtros['fk_id_ceremonia'] ?? null,
            'carrera' => $filtros['carrera'] ?? null,
            'fecha_inicial' => $filtros['fecha_inicial'],
            'fecha_final' => $filtros['fecha_final'],
            'registros' => $totalRegistros,
            'datos' => $datos,
            'fk_generado_por' => $request->user('graduacion')->id,
        ]);

        return redirect()->route('graduacion.reportes.show', $reporte);
    }

    /** Vista previa de un reporte guardado. */
    public function show(ReporteGraduacion $reporte): Response
    {
        return Inertia::render('graduacion/reporte-vista-previa', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ]);
    }

    /** Botón "Eliminar" */
    public function destroy(Request $request, ReporteGraduacion $reporte): RedirectResponse
    {
        if ($reporte->fk_generado_por !== $request->user('graduacion')->id) {
            abort(403, 'Solo la persona que generó el reporte puede eliminarlo.');
        }

        $reporte->delete();

        return back();
    }

    /** Botón "Descargar PDF". */
    public function descargarPdf(ReporteGraduacion $reporte): StreamedResponse
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
    public function exportarExcel(ReporteGraduacion $reporte): StreamedResponse
    {
        $writer = $this->generarExcel($reporte);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $this->nombreArchivo($reporte, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    
    private function nombreArchivo(ReporteGraduacion $reporte, string $extension): string
    {
        $nombre = str_replace(['/', '\\'], '-', $reporte->nombre);

        return "{$nombre}.{$extension}";
    }

    /** Botón "Compartir": envía el PDF del reporte por correo. */
    public function compartir(Request $request, ReporteGraduacion $reporte): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
        ]);

        Mail::to($datos['correo'])->send(new ReporteGraduacionGenerado($reporte, $this->generarPdf($reporte)));

        return back()->with('success', "Reporte enviado a {$datos['correo']}.");
    }

    /**
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function calcularDatos(array $filtros): array
    {
        $registros = RegistroAccesoGraduacion::query()
            ->whereBetween(DB::raw('DATE(fecha_hora)'), [$filtros['fecha_inicial'], $filtros['fecha_final']])
            ->when($filtros['fk_id_ceremonia'] ?? null, fn ($query, $ceremoniaId) => $query->whereHas('boleto', fn ($q) => $q->where('fk_id_ceremonia', $ceremoniaId)))
            ->when($filtros['carrera'] ?? null, fn ($query, $carrera) => $query->whereHas('boleto.alumno', fn ($q) => $q->where('carrera', $carrera)))
            ->with(['boleto.alumno', 'boleto.ceremonia'])
            ->orderBy('fecha_hora')
            ->get();

        $datos = [
            'indicadores' => [
                'egresadosRegistrados' => $registros->filter(fn (RegistroAccesoGraduacion $r) => $r->boleto?->tipo === BoletoGraduacion::TIPO_GRADUADO)->pluck('boleto.no_de_control')->unique()->count(),
                'invitadosRegistrados' => $registros->filter(fn (RegistroAccesoGraduacion $r) => $r->boleto?->tipo === BoletoGraduacion::TIPO_INVITADO)->count(),
                'totalAsistentes' => $registros->count(),
            ],
            'porCarrera' => $this->agruparPorCarrera($registros),
            'porCeremonia' => $this->agruparPorCeremonia($registros),
        ];

        if ($filtros['tipo'] === 'detallado') {
            $datos['detalle'] = $registros->take(self::TOPE_DETALLE)->map(fn (RegistroAccesoGraduacion $registro) => [
                'fecha' => $registro->fecha_hora->format('d/m/Y'),
                'hora' => $registro->fecha_hora->format('h:i A'),
                'nombre' => $registro->boleto?->alumno?->nombreCompleto() ?? 'Desconocido',
                'noDeControl' => $registro->boleto?->no_de_control,
                'carrera' => $registro->boleto?->alumno?->siglasCarrera(),
                'ceremonia' => $registro->boleto?->ceremonia?->nombre,
                'tipo' => $registro->boleto?->tipo === BoletoGraduacion::TIPO_GRADUADO ? 'Egresado' : 'Invitado',
            ])->values()->all();
        }

        return [$datos, $registros->count()];
    }

    /**
     * @param  Collection<int, RegistroAccesoGraduacion>  $registros
     * @return list<array<string, mixed>>
     */
    private function agruparPorCarrera(Collection $registros): array
    {
        return $registros->groupBy(fn (RegistroAccesoGraduacion $registro) => $registro->boleto?->alumno?->carrera ?? '—')
            ->map(fn (Collection $grupo) => [
                'carrera' => $grupo->first()->boleto?->alumno?->nombreCarrera() ?? 'Sin carrera',
                'egresados' => $grupo->filter(fn (RegistroAccesoGraduacion $r) => $r->boleto?->tipo === BoletoGraduacion::TIPO_GRADUADO)->pluck('boleto.no_de_control')->unique()->count(),
                'invitados' => $grupo->filter(fn (RegistroAccesoGraduacion $r) => $r->boleto?->tipo === BoletoGraduacion::TIPO_INVITADO)->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAccesoGraduacion>  $registros
     * @return list<array<string, mixed>>
     */
    private function agruparPorCeremonia(Collection $registros): array
    {
        return $registros->groupBy(fn (RegistroAccesoGraduacion $registro) => $registro->boleto?->fk_id_ceremonia ?? '—')
            ->map(fn (Collection $grupo) => [
                'ceremonia' => $grupo->first()->boleto?->ceremonia?->nombre ?? 'Desconocida',
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
    private function serializar(ReporteGraduacion $reporte): array
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
    public function generarPdf(ReporteGraduacion $reporte): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reportes.graduacion-pdf', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ])->render());
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generarExcel(ReporteGraduacion $reporte): Xlsx
    {
        $datos = $reporte->datos;

        $spreadsheet = new Spreadsheet;
        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $resumen->fromArray([
            ['Reporte', $reporte->nombre],
            ['Periodo', $reporte->fecha_inicial->format('d/m/Y').' - '.$reporte->fecha_final->format('d/m/Y')],
            ['Egresados registrados', $datos['indicadores']['egresadosRegistrados']],
            ['Invitados registrados', $datos['indicadores']['invitadosRegistrados']],
            ['Total de asistentes', $datos['indicadores']['totalAsistentes']],
            [],
            ['Carrera', 'Egresados', 'Invitados'],
        ], null, 'A1');

        $fila = 8;

        foreach ($datos['porCarrera'] as $carrera) {
            $resumen->fromArray([$carrera['carrera'], $carrera['egresados'], $carrera['invitados']], null, "A{$fila}");
            $fila++;
        }

        if (! empty($datos['detalle'])) {
            $detalle = $spreadsheet->createSheet();
            $detalle->setTitle('Detalle');
            $detalle->fromArray(['Fecha', 'Hora', 'Nombre', 'No. Control', 'Carrera', 'Ceremonia', 'Tipo'], null, 'A1');

            $fila = 2;

            foreach ($datos['detalle'] as $registro) {
                $detalle->fromArray([
                    $registro['fecha'],
                    $registro['hora'],
                    $registro['nombre'],
                    $registro['noDeControl'],
                    $registro['carrera'],
                    $registro['ceremonia'],
                    $registro['tipo'],
                ], null, "A{$fila}");
                $fila++;
            }
        }

        return new Xlsx($spreadsheet);
    }
}
