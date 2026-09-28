<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\IncidenciaAcceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IncidenciasController extends Controller
{
    private const POR_PAGINA = 10;

    private const TIPOS = [
        IncidenciaAcceso::QR_INVALIDO => 'Código QR inválido',
        IncidenciaAcceso::NO_REGISTRADO => 'No registrado',
        IncidenciaAcceso::INACTIVO => 'Estatus inactivo',
        IncidenciaAcceso::DOBLE_ESCANEO => 'Doble escaneo',
    ];

    /** Pantalla de Incidencias: bitácora completa de escaneos rechazados, con filtros. */
    public function index(Request $request): Response
    {
        $filtros = $this->validarFiltros($request);

        $incidencias = $this->consultaFiltrada($filtros)
            ->with(['alumno', 'ubicacion'])
            ->orderByDesc('fecha_hora')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', (int) ($filtros['pagina'] ?? 1))
            ->withQueryString();

        return Inertia::render('panel/incidencias', [
            'incidencias' => [
                'data' => collect($incidencias->items())
                    ->map(fn (IncidenciaAcceso $incidencia) => [
                        'id' => $incidencia->id,
                        'fecha' => $incidencia->fecha_hora->format('d/m/Y'),
                        'hora' => $incidencia->fecha_hora->format('h:i A'),
                        'noDeControl' => $incidencia->no_de_control,
                        'nombre' => $incidencia->alumno?->nombreCompleto(),
                        'tipo' => $incidencia->tipo,
                        'mensaje' => $incidencia->mensaje,
                        'ubicacion' => $incidencia->ubicacion?->nombre,
                    ])
                    ->all(),
                'meta' => [
                    'paginaActual' => $incidencias->currentPage(),
                    'ultimaPagina' => $incidencias->lastPage(),
                    'total' => $incidencias->total(),
                    'desde' => $incidencias->firstItem(),
                    'hasta' => $incidencias->lastItem(),
                ],
            ],
            'filtros' => [
                'buscar' => $filtros['buscar'] ?? '',
                'tipo' => $filtros['tipo'] ?? '',
                'fechaInicial' => $filtros['fechaInicial'] ?? '',
                'fechaFinal' => $filtros['fechaFinal'] ?? '',
            ],
            'opciones' => [
                'tipos' => collect(self::TIPOS)
                    ->map(fn (string $etiqueta, string $valor) => ['value' => $valor, 'label' => $etiqueta])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /** Botón "Eliminar" de una fila: borra una sola incidencia. */
    public function destroy(IncidenciaAcceso $incidencia): RedirectResponse
    {
        abort_if($incidencia->id_evento !== null, 404);

        $incidencia->delete();

        return back()->with('success', 'Incidencia eliminada.');
    }

    /** Botón "Eliminar todas" */
    public function destroyTodas(Request $request): RedirectResponse
    {
        $filtros = $this->validarFiltros($request);

        $total = $this->consultaFiltrada($filtros)->delete();

        return back()->with('success', "{$total} incidencia(s) eliminada(s).");
    }

    /**
     * @return array<string, mixed>
     */
    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', Rule::in(array_keys(self::TIPOS))],
            'fechaInicial' => ['nullable', 'date'],
            'fechaFinal' => ['nullable', 'date'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consultaFiltrada(array $filtros)
    {
        return IncidenciaAcceso::query()
            ->whereNull('id_evento')
            ->when(
                $filtros['buscar'] ?? null,
                fn ($query, $buscar) => $query->where(fn ($sub) => $sub
                    ->where('no_de_control', 'like', "%{$buscar}%")
                    ->orWhere('mensaje', 'like', "%{$buscar}%")),
            )
            ->when(
                $filtros['tipo'] ?? null,
                fn ($query, $tipo) => $query->where('tipo', $tipo),
            )
            ->when(
                $filtros['fechaInicial'] ?? null,
                fn ($query, $fecha) => $query->whereDate('fecha_hora', '>=', $fecha),
            )
            ->when(
                $filtros['fechaFinal'] ?? null,
                fn ($query, $fecha) => $query->whereDate('fecha_hora', '<=', $fecha),
            );
    }
}
