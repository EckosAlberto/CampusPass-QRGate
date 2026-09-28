<?php

namespace App\Http\Controllers\Panel;

use App\Concerns\OpcionesCarreras;
use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EstadisticasController extends Controller
{
    use OpcionesCarreras;

    private const HORA_APERTURA = 7;

    private const HORA_CIERRE = 20;

    private const MAX_SEMESTRE = 9;

    private const DIAS_POR_DEFECTO = 14;

    /** Pantalla de Estadísticas: gráficas de accesos (por hora, sexo, carrera, semestre, día). */
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'fecha_inicial' => ['nullable', 'date'],
            'fecha_final' => ['nullable', 'date'],
            'carrera' => ['nullable', 'string', 'max:3'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_SEMESTRE],
        ]);

        $fechaFinal = $filtros['fecha_final'] ?? today()->toDateString();
        $fechaInicial = $filtros['fecha_inicial'] ?? today()->subDays(self::DIAS_POR_DEFECTO - 1)->toDateString();

        $bibliotecaId = Ubicacion::where('nombre', 'Biblioteca')->value('id');

        $registros = RegistroAcceso::query()
            ->where('id_ubicacion', $bibliotecaId)
            ->whereBetween('fecha_hora', ["{$fechaInicial} 00:00:00", "{$fechaFinal} 23:59:59"])
            ->when($filtros['carrera'] ?? null, fn ($query, $carrera) => $query->whereHas('alumno', fn ($q) => $q->where('carrera', $carrera)))
            ->when($filtros['semestre'] ?? null, fn ($query, $semestre) => $query->whereHas('alumno', fn ($q) => $q->where('semestre', $semestre)))
            ->with(['alumno', 'personal'])
            ->get();

        return Inertia::render('panel/estadisticas', [
            'filtros' => [
                'fechaInicial' => $fechaInicial,
                'fechaFinal' => $fechaFinal,
                'carrera' => $filtros['carrera'] ?? '',
                'semestre' => isset($filtros['semestre']) ? (string) $filtros['semestre'] : '',
            ],
            'opciones' => [
                'carreras' => $this->opcionesCarreras(),
                'semestres' => collect(range(1, self::MAX_SEMESTRE))
                    ->map(fn (int $semestre) => ['value' => (string) $semestre, 'label' => "{$semestre}°"])
                    ->all(),
            ],
            'datos' => [
                'resumen' => $this->resumen($registros),
                'entradasPorHora' => $this->entradasPorHora($registros),
                'distribucionPorSexo' => $this->distribucionPorSexo($registros),
                'accesosPorDia' => $this->accesosPorDia($registros, $fechaInicial, $fechaFinal),
                'accesosPorCarrera' => $this->accesosPorCarrera($registros),
                'accesosPorSemestre' => $this->accesosPorSemestre($registros),
            ],
        ]);
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return array{hombres: int, mujeres: int, totalAlumnos: int, entradas: int, salidas: int, personasDentro: int}
     */
    private function resumen(Collection $registros): array
    {
        $alumnosUnicos = $registros->pluck('alumno')->filter()->unique('no_de_control');

        $personasDentro = $registros
            ->groupBy(fn (RegistroAcceso $registro) => $registro->identificador())
            ->filter(fn (Collection $porPersona) => $porPersona->sortBy('fecha_hora')->last()->tipo_movimiento === 'ENTRADA')
            ->count();

        return [
            'hombres' => $alumnosUnicos->where('sexo', 'H')->count(),
            'mujeres' => $alumnosUnicos->where('sexo', 'M')->count(),
            'totalAlumnos' => $alumnosUnicos->count(),
            'entradas' => $registros->where('tipo_movimiento', 'ENTRADA')->count(),
            'salidas' => $registros->where('tipo_movimiento', 'SALIDA')->count(),
            'personasDentro' => $personasDentro,
        ];
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{hora: string, total: int}>
     */
    private function entradasPorHora(Collection $registros): array
    {
        $conteo = $registros
            ->where('tipo_movimiento', 'ENTRADA')
            ->countBy(fn (RegistroAcceso $registro) => $registro->fecha_hora->hour);

        return collect(range(self::HORA_APERTURA, self::HORA_CIERRE))
            ->map(fn (int $hora) => [
                'hora' => sprintf('%02d:00', $hora),
                'total' => $conteo->get($hora, 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{sexo: string, total: int}>
     */
    private function distribucionPorSexo(Collection $registros): array
    {
        $etiquetas = ['H' => 'Hombres', 'M' => 'Mujeres'];

        $conteo = $registros->pluck('alumno')->filter()->unique('no_de_control')
            ->countBy(fn (Alumno $alumno) => $etiquetas[$alumno->sexo] ?? 'No especificado');

        return $conteo
            ->map(fn (int $total, string $sexo) => ['sexo' => $sexo, 'total' => $total])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{fecha: string, etiqueta: string, total: int}>
     */
    private function accesosPorDia(Collection $registros, string $fechaInicial, string $fechaFinal): array
    {
        $inicio = Carbon::parse($fechaInicial)->startOfDay();
        $fin = Carbon::parse($fechaFinal)->startOfDay();
        $dias = max(1, $inicio->diffInDays($fin) + 1);

        $conteo = $registros->countBy(fn (RegistroAcceso $registro) => $registro->fecha_hora->toDateString());

        return collect(range(0, $dias - 1))
            ->map(function (int $offset) use ($inicio, $conteo) {
                $fecha = $inicio->clone()->addDays($offset);

                return [
                    'fecha' => $fecha->toDateString(),
                    'etiqueta' => $fecha->format('d/m'),
                    'total' => $conteo->get($fecha->toDateString(), 0),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{carrera: string, total: int}>
     */
    private function accesosPorCarrera(Collection $registros): array
    {
        $nombresPorCodigo = Carrera::query()
            ->selectRaw('carrera, MIN(nombre_carrera) as nombre_carrera')
            ->groupBy('carrera')
            ->pluck('nombre_carrera', 'carrera');

        return $registros
            ->filter(fn (RegistroAcceso $registro) => $registro->alumno !== null)
            ->countBy(fn (RegistroAcceso $registro) => $registro->alumno->carrera)
            ->map(fn (int $total, string $codigo) => [
                'carrera' => $nombresPorCodigo->get($codigo, $codigo),
                'total' => $total,
            ])
            ->values()
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return list<array{semestre: string, total: int}>
     */
    private function accesosPorSemestre(Collection $registros): array
    {
        $conteo = $registros
            ->filter(fn (RegistroAcceso $registro) => $registro->alumno?->semestre !== null)
            ->countBy(fn (RegistroAcceso $registro) => $registro->alumno->semestre);

        return collect(range(1, self::MAX_SEMESTRE))
            ->map(fn (int $semestre) => [
                'semestre' => "{$semestre}°",
                'total' => $conteo->get($semestre, 0),
            ])
            ->all();
    }
}
