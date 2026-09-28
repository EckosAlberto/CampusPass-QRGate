<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EstadisticasController extends Controller
{
    /** Pantalla de Estadísticas: gráficas de accesos de las ceremonias. */
    public function index(Request $request): Response
    {
        $datos = $request->validate([
            'ceremonia' => ['nullable', 'uuid', 'exists:ceremonias_graduacion,id'],
        ]);

        $ceremonias = CeremoniaGraduacion::query()->orderByDesc('fecha_inicio')->get();

        $comparativa = $ceremonias->map(fn (CeremoniaGraduacion $ceremonia) => [
            'id' => $ceremonia->id,
            'nombre' => $ceremonia->nombre,
            'fecha' => $ceremonia->fecha_inicio->format('d/m/Y'),
            'egresados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_GRADUADO)->count(),
            'invitados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_INVITADO)->count(),
            'asistentes' => RegistroAccesoGraduacion::whereHas('boleto', fn ($q) => $q->where('fk_id_ceremonia', $ceremonia->id))->count(),
        ])->values()->all();

        $ceremoniaSeleccionada = isset($datos['ceremonia'])
            ? $ceremonias->firstWhere('id', $datos['ceremonia'])
            : ($ceremonias->firstWhere('estatus', true) ?? $ceremonias->first());

        $tendencia = [];

        if ($ceremoniaSeleccionada) {
            $registros = RegistroAccesoGraduacion::query()
                ->whereHas('boleto', fn ($q) => $q->where('fk_id_ceremonia', $ceremoniaSeleccionada->id))
                ->get();

            $tendencia = $registros
                ->groupBy(fn (RegistroAccesoGraduacion $registro) => $registro->fecha_hora->format('H:00'))
                ->map(fn (Collection $grupo, string $hora) => ['hora' => $hora, 'total' => $grupo->count()])
                ->sortBy('hora')
                ->values()
                ->all();
        }

        return Inertia::render('graduacion/estadisticas', [
            'ceremonias' => $ceremonias->map(fn (CeremoniaGraduacion $c) => ['value' => $c->id, 'label' => $c->nombre])->all(),
            'ceremoniaSeleccionada' => $ceremoniaSeleccionada?->id,
            'comparativa' => $comparativa,
            'tendencia' => $tendencia,
        ]);
    }
}
