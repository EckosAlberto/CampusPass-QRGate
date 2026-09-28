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

class RegistroDelDiaController extends Controller
{
    use OpcionesCarreras;

    private const POR_PAGINA = 10;

    /** Pantalla "Registro del día": tabla de todos los accesos de hoy, con tiempo de permanencia. */
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'carrera' => ['nullable', 'string', 'max:3'],
            'semestre' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sexo' => ['nullable', 'in:H,M'],
            'movimiento' => ['nullable', 'in:ENTRADA,SALIDA'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);

        $bibliotecaId = Ubicacion::where('nombre', 'Biblioteca')->value('id');

        $registrosHoy = RegistroAcceso::where('id_ubicacion', $bibliotecaId)
            ->whereDate('fecha_hora', today())
            ->with(['alumno', 'personal'])
            ->orderBy('fecha_hora')
            ->get();

        $filas = $this->conTiempoDePermanencia($registrosHoy)
            ->filter(fn (array $fila) => $this->coincideConFiltros($fila, $filtros))
            ->sortByDesc('ordenFechaHora')
            ->values();

        $pagina = (int) ($filtros['pagina'] ?? 1);
        $paginador = new LengthAwarePaginator(
            $filas->forPage($pagina, self::POR_PAGINA)->values(),
            $filas->count(),
            self::POR_PAGINA,
            $pagina,
        );

        $filasVisibles = collect($paginador->items())
            ->map(fn (array $fila) => Arr::except($fila, ['ordenFechaHora', '_carreraCodigo', '_sexo', '_identificador']))
            ->all();

        return Inertia::render('panel/registro-del-dia', [
            'registros' => [
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
                'sexo' => $filtros['sexo'] ?? '',
                'movimiento' => $filtros['movimiento'] ?? '',
            ],
            'opciones' => [
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
            ],
        ]);
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registros
     * @return Collection<int, array<string, mixed>>
     */
    private function conTiempoDePermanencia(Collection $registros): Collection
    {
        $entradaAbierta = [];

        return $registros->map(function (RegistroAcceso $registro) use (&$entradaAbierta) {
            $identificador = $registro->identificador();
            $tiempoPermanencia = null;

            if ($registro->tipo_movimiento === 'ENTRADA') {
                $entradaAbierta[$identificador] = $registro->fecha_hora;
            } elseif ($registro->tipo_movimiento === 'SALIDA' && isset($entradaAbierta[$identificador])) {
                $tiempoPermanencia = $entradaAbierta[$identificador]
                    ->diff($registro->fecha_hora)
                    ->format('%H:%I:%S');
                unset($entradaAbierta[$identificador]);
            }

            return [
                'id' => $registro->id,
                'fecha' => $registro->fecha_hora->format('d/m/Y'),
                'hora' => $registro->fecha_hora->format('h:i A'),
                'nombre' => $registro->alumno?->nombreCompleto() ?? $registro->personal?->nombreCompleto() ?? 'Desconocido',
                'noDeControl' => $registro->no_de_control,
                'rfc' => $registro->rfc_personal,
                'carrera' => $registro->alumno?->siglasCarrera(),
                'semestre' => $registro->alumno?->semestre,
                'movimiento' => $registro->tipo_movimiento,
                'tipo' => $registro->rfc_personal ? 'Personal' : 'Estudiante',
                'tiempoPermanencia' => $tiempoPermanencia,
                
                'sexo' => $registro->alumno?->sexo ?? $registro->personal?->sexo(),
                'ordenFechaHora' => $registro->fecha_hora->timestamp,
                '_carreraCodigo' => $registro->alumno?->carrera,
                '_sexo' => $registro->alumno?->sexo ?? $registro->personal?->sexo(),
                '_identificador' => $identificador,
            ];
        });
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

        if (! empty($filtros['sexo']) && $fila['_sexo'] !== $filtros['sexo']) {
            return false;
        }

        if (! empty($filtros['movimiento']) && $fila['movimiento'] !== $filtros['movimiento']) {
            return false;
        }

        return true;
    }
}
