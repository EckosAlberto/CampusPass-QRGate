<?php

namespace App\Http\Controllers\Panel;

use App\Concerns\OpcionesCarreras;
use App\Http\Controllers\Controller;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PersonasDentroController extends Controller
{
    use OpcionesCarreras;

    private const POR_PAGINA = 10;

    /** Pantalla "Personas dentro": quién sigue dentro ahora mismo (última entrada del día sin salida). */
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'carrera' => ['nullable', 'string', 'max:3'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:12'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);

        $bibliotecaId = Ubicacion::where('nombre', 'Biblioteca')->value('id');

        $registrosHoy = RegistroAcceso::where('id_ubicacion', $bibliotecaId)
            ->whereDate('fecha_hora', today())
            ->with(['alumno', 'personal'])
            ->orderBy('fecha_hora')
            ->get();

        $personasDentro = $this->personasActualmenteDentro($registrosHoy)
            ->sortBy('ordenFechaHora')
            ->values();

        $filas = $personasDentro
            ->filter(fn (array $fila) => $this->coincideConFiltros($fila, $filtros))
            ->values();

        $pagina = (int) ($filtros['pagina'] ?? 1);
        $paginador = new LengthAwarePaginator(
            $filas->forPage($pagina, self::POR_PAGINA)->values(),
            $filas->count(),
            self::POR_PAGINA,
            $pagina,
        );

        $filasVisibles = collect($paginador->items())
            ->map(fn (array $fila) => Arr::except($fila, ['ordenFechaHora', '_carreraCodigo', '_identificador']))
            ->all();

        return Inertia::render('panel/personas-dentro', [
            'personas' => [
                'data' => $filasVisibles,
                'meta' => [
                    'paginaActual' => $paginador->currentPage(),
                    'ultimaPagina' => $paginador->lastPage(),
                    'total' => $paginador->total(),
                    'desde' => $paginador->firstItem(),
                    'hasta' => $paginador->lastItem(),
                ],
            ],
            'filtros' => [
                'buscar' => $filtros['buscar'] ?? '',
                'carrera' => $filtros['carrera'] ?? '',
                'semestre' => isset($filtros['semestre']) ? (string) $filtros['semestre'] : '',
            ],
            'opciones' => [
                'carreras' => $this->opcionesCarreras(),
                'semestres' => collect(range(1, 12))
                    ->map(fn (int $semestre) => ['value' => (string) $semestre, 'label' => "{$semestre}°"])
                    ->all(),
            ],
            'totalDentro' => $personasDentro->count(),
            'actualizadoEn' => now()->format('H:i:s'),
        ]);
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return Collection<int, array<string, mixed>>
     */
    private function personasActualmenteDentro(Collection $registros): Collection
    {
        return $registros
            ->groupBy(fn (RegistroAcceso $registro) => $registro->identificador())
            ->filter(fn (Collection $registrosAlumno) => $registrosAlumno->last()->tipo_movimiento === 'ENTRADA')
            ->map(function (Collection $registrosAlumno) {
                /** @var RegistroAcceso $entrada */
                $entrada = $registrosAlumno->last();

                return [
                    'id' => $entrada->id,
                    'nombre' => $entrada->alumno?->nombreCompleto() ?? $entrada->personal?->nombreCompleto() ?? 'Desconocido',
                    'noDeControl' => $entrada->no_de_control,
                    'rfc' => $entrada->rfc_personal,
                    'carrera' => $entrada->alumno?->siglasCarrera(),
                    'semestre' => $entrada->alumno?->semestre,
                    'fechaEntrada' => $entrada->fecha_hora->format('d/m/Y'),
                    'horaEntrada' => $entrada->fecha_hora->format('h:i A'),
                    'estadoActual' => 'Dentro',
                    'ordenFechaHora' => $entrada->fecha_hora->timestamp,
                    '_carreraCodigo' => $entrada->alumno?->carrera,
                    '_identificador' => $entrada->identificador(),
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, mixed>  $filtros
     */
    private function coincideConFiltros(array $fila, array $filtros): bool
    {
        if (! empty($filtros['buscar'])) {
            $buscar = Str::lower($filtros['buscar']);

            if (! Str::contains(Str::lower($fila['nombre']), $buscar)
                && ! Str::contains(Str::lower($fila['_identificador'] ?? ''), $buscar)) {
                return false;
            }
        }

        if (! empty($filtros['carrera']) && $fila['_carreraCodigo'] !== $filtros['carrera']) {
            return false;
        }

        if (! empty($filtros['semestre']) && (int) $fila['semestre'] !== (int) $filtros['semestre']) {
            return false;
        }

        return true;
    }
}
