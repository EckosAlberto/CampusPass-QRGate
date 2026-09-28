<?php

namespace App\Http\Controllers\Panel;

use App\Concerns\OpcionesCarreras;
use App\Http\Controllers\Controller;
use App\Mail\ReporteBibliotecaGenerado;
use App\Models\RegistroAcceso;
use App\Models\ReporteBiblioteca;
use App\Models\Ubicacion;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    use OpcionesCarreras;

    private const POR_PAGINA = 10;

    private const TOPE_DETALLE = 500;

    private const ETIQUETAS_TIPO = [
        'resumen_general' => 'Resumen general',
        'detallado' => 'Detallado',
    ];

    /** Pantalla de Reportes: la lista de reportes ya generados. */
    public function index(Request $request): Response
    {
        $reportes = ReporteBiblioteca::query()
            ->with('generadoPor')
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('panel/reportes', [
            'usuarioId' => $request->user()->id,
            'reportes' => [
                'data' => collect($reportes->items())->map(fn (ReporteBiblioteca $reporte) => $this->serializar($reporte))->all(),
                'meta' => [
                    'paginaActual' => $reportes->currentPage(),
                    'ultimaPagina' => $reportes->lastPage(),
                    'total' => $reportes->total(),
                    'desde' => $reportes->firstItem(),
                    'hasta' => $reportes->lastItem(),
                ],
            ],
            'catalogos' => [
                'carreras' => $this->opcionesCarreras(),
                'semestres' => collect(range(1, 12))
                    ->map(fn (int $semestre) => ['value' => (string) $semestre, 'label' => "{$semestre}°"])
                    ->all(),
                'sexos' => [
                    ['value' => 'H', 'label' => 'Hombre'],
                    ['value' => 'M', 'label' => 'Mujer'],
                ],
                'movimientos' => [
                    ['value' => 'ENTRADA', 'label' => 'Entrada'],
                    ['value' => 'SALIDA', 'label' => 'Salida'],
                ],
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
            'carrera' => ['nullable', 'string', 'max:3'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sexo' => ['nullable', 'in:H,M'],
            'tipo_movimiento' => ['nullable', 'in:ENTRADA,SALIDA'],
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

        $reporte = ReporteBiblioteca::create([
            'nombre' => $nombre,
            'tipo' => $filtros['tipo'],
            'fk_id_ubicacion' => Ubicacion::where('nombre', 'Biblioteca')->value('id'),
            'carrera' => $filtros['carrera'] ?? null,
            'semestre' => $filtros['semestre'] ?? null,
            'sexo' => $filtros['sexo'] ?? null,
            'tipo_movimiento' => $filtros['tipo_movimiento'] ?? null,
            'fecha_inicial' => $filtros['fecha_inicial'],
            'fecha_final' => $filtros['fecha_final'],
            'registros' => $totalRegistros,
            'datos' => $datos,
            'fk_generado_por' => Auth::id(),
        ]);

        return redirect()->route('reportes.show', $reporte);
    }

    /** Vista previa de un reporte guardado. */
    public function show(ReporteBiblioteca $reporte): Response
    {
        return Inertia::render('panel/reporte-vista-previa', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ]);
    }

    /** Botón "Eliminar": solo lo puede borrar quien lo generó. */
    public function destroy(ReporteBiblioteca $reporte): RedirectResponse
    {
        if ($reporte->fk_generado_por !== Auth::id()) {
            abort(403, 'Solo la persona que generó el reporte puede eliminarlo.');
        }

        $reporte->delete();

        return back();
    }

    /** Botón "Descargar PDF". */
    public function descargarPdf(ReporteBiblioteca $reporte): StreamedResponse
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
    public function exportarExcel(ReporteBiblioteca $reporte): StreamedResponse
    {
        $writer = $this->generarExcel($reporte);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $this->nombreArchivo($reporte, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /** Botón "Compartir" */
    public function compartir(Request $request, ReporteBiblioteca $reporte): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
        ]);

        Mail::to($datos['correo'])->send(new ReporteBibliotecaGenerado($reporte, $this->generarPdf($reporte)));

        return back()->with('success', "Reporte enviado a {$datos['correo']}.");
    }

    /**
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function calcularDatos(array $filtros): array
    {
        $bibliotecaId = Ubicacion::where('nombre', 'Biblioteca')->value('id');

        $registros = RegistroAcceso::query()
            ->where('id_ubicacion', $bibliotecaId)
            ->whereBetween(DB::raw('DATE(fecha_hora)'), [$filtros['fecha_inicial'], $filtros['fecha_final']])
            ->when($filtros['tipo_movimiento'] ?? null, fn ($query, $movimiento) => $query->where('tipo_movimiento', $movimiento))
            ->when($filtros['carrera'] ?? null, fn ($query, $carrera) => $query->whereHas('alumno', fn ($q) => $q->where('carrera', $carrera)))
            ->when($filtros['semestre'] ?? null, fn ($query, $semestre) => $query->whereHas('alumno', fn ($q) => $q->where('semestre', $semestre)))
            ->when($filtros['sexo'] ?? null, fn ($query, $sexo) => $query->where(fn ($q) => $q
                ->whereHas('alumno', fn ($q2) => $q2->where('sexo', $sexo))
                
                ->orWhereHas('personal', fn ($q2) => $q2->whereRaw('SUBSTR(curp_empleado, 11, 1) = ?', [$sexo]))
            ))
            ->with(['alumno', 'personal'])
            ->orderBy('fecha_hora')
            ->get();

        return [
            [
                'indicadores' => [
                    'entradas' => $registros->where('tipo_movimiento', 'ENTRADA')->count(),
                    'salidas' => $registros->where('tipo_movimiento', 'SALIDA')->count(),
                    'usuariosUnicos' => $registros->map(fn (RegistroAcceso $registro) => $registro->identificador())->unique()->count(),
                    'mujeres' => $this->alumnosUnicosPorSexo($registros, 'M'),
                    'hombres' => $this->alumnosUnicosPorSexo($registros, 'H'),
                    'totalAccesos' => $registros->count(),
                ],
                'serieDiaria' => $this->agruparPorDia($registros),
                'detalle' => $registros->take(self::TOPE_DETALLE)->map(fn (RegistroAcceso $registro) => [
                    'fecha' => $registro->fecha_hora->format('d/m/Y'),
                    'hora' => $registro->fecha_hora->format('h:i A'),
                    'nombre' => $registro->alumno?->nombreCompleto() ?? $registro->personal?->nombreCompleto() ?? 'Desconocido',
                    'noDeControl' => $registro->no_de_control,
                    'rfc' => $registro->rfc_personal,
                    'carrera' => $registro->alumno?->siglasCarrera(),
                    'semestre' => $registro->alumno?->semestre,
                    'tipoMovimiento' => $registro->tipo_movimiento,
                ])->values()->all(),
            ],
            $registros->count(),
        ];
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     */
    private function alumnosUnicosPorSexo(Collection $registros, string $sexo): int
    {
        return $registros
            ->filter(fn (RegistroAcceso $registro) => ($registro->alumno?->sexo ?? $registro->personal?->sexo()) === $sexo)
            ->map(fn (RegistroAcceso $registro) => $registro->identificador())
            ->unique()
            ->count();
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
     * @return array<string, mixed>
     */
    private function serializar(ReporteBiblioteca $reporte): array
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

    
    public function generarPdf(ReporteBiblioteca $reporte): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reportes.pdf-biblioteca', [
            'reporte' => $this->serializar($reporte),
            'datos' => $reporte->datos,
        ])->render());
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generarExcel(ReporteBiblioteca $reporte): Xlsx
    {
        $datos = $reporte->datos;

        $spreadsheet = new Spreadsheet;
        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $resumen->fromArray([
            ['Reporte', $reporte->nombre],
            ['Periodo', $reporte->fecha_inicial->format('d/m/Y').' - '.$reporte->fecha_final->format('d/m/Y')],
            ['Entradas', $datos['indicadores']['entradas']],
            ['Salidas', $datos['indicadores']['salidas']],
            ['Usuarios únicos', $datos['indicadores']['usuariosUnicos']],
            ['Mujeres', $datos['indicadores']['mujeres']],
            ['Hombres', $datos['indicadores']['hombres']],
            ['Total de accesos', $datos['indicadores']['totalAccesos']],
        ], null, 'A1');

        if (! empty($datos['detalle'])) {
            $detalle = $spreadsheet->createSheet();
            $detalle->setTitle('Detalle');
            $detalle->fromArray(['Fecha', 'Hora', 'Nombre', 'No. Control', 'RFC', 'Carrera', 'Semestre', 'Movimiento'], null, 'A1');

            $fila = 2;

            foreach ($datos['detalle'] as $registro) {
                $detalle->fromArray([
                    $registro['fecha'],
                    $registro['hora'],
                    $registro['nombre'],
                    $registro['noDeControl'],
                    $registro['rfc'] ?? null,
                    $registro['carrera'],
                    $registro['semestre'],
                    $registro['tipoMovimiento'],
                ], null, "A{$fila}");
                $fila++;
            }
        }

        return new Xlsx($spreadsheet);
    }

    
    private function nombreArchivo(ReporteBiblioteca $reporte, string $extension): string
    {
        $nombre = str_replace(['/', '\\'], '-', $reporte->nombre);

        return "{$nombre}.{$extension}";
    }
}
