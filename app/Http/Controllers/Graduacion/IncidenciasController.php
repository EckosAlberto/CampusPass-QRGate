<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\IncidenciaAccesoGraduacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IncidenciasController extends Controller
{
    private const POR_PAGINA = 10;

    private const TIPOS = [
        IncidenciaAccesoGraduacion::QR_INVALIDO => 'Código QR inválido',
        IncidenciaAccesoGraduacion::NO_REGISTRADO => 'No registrado',
        IncidenciaAccesoGraduacion::INACTIVO => 'Estatus inactivo',
        IncidenciaAccesoGraduacion::CEREMONIA_NO_VIGENTE => 'Ceremonia no vigente',
        IncidenciaAccesoGraduacion::SIN_INVITACIONES => 'Sin invitaciones disponibles',
        IncidenciaAccesoGraduacion::YA_UTILIZADO => 'Boleto ya utilizado',
        IncidenciaAccesoGraduacion::DOBLE_ESCANEO => 'Escaneo duplicado',
    ];

    /** Pantalla de Incidencias: bitácora completa de escaneos rechazados en ceremonias, con filtros. */
    public function index(Request $request): Response
    {
        $filtros = $this->validarFiltros($request);

        $incidencias = $this->consultaFiltrada($filtros)
            ->with('ceremonia')
            ->orderByDesc('fecha_hora')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', (int) ($filtros['pagina'] ?? 1))
            ->withQueryString();

        return Inertia::render('graduacion/incidencias', [
            'incidencias' => [
                'data' => collect($incidencias->items())
                    ->map(fn (IncidenciaAccesoGraduacion $incidencia) => [
                        'id' => $incidencia->id,
                        'fecha' => $incidencia->fecha_hora->format('d/m/Y'),
                        'hora' => $incidencia->fecha_hora->format('h:i A'),
                        'codigo' => $incidencia->codigo,
                        'tipo' => $incidencia->tipo,
                        'mensaje' => $incidencia->mensaje,
                        'ceremonia' => $incidencia->ceremonia?->nombre,
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
    public function destroy(IncidenciaAccesoGraduacion $incidencia): RedirectResponse
    {
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
        return IncidenciaAccesoGraduacion::query()
            ->when(
                $filtros['buscar'] ?? null,
                fn ($query, $buscar) => $query->where(fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$buscar}%")
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
