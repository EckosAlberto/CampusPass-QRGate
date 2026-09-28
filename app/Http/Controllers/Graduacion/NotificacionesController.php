<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\IncidenciaAccesoGraduacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    private const POR_LISTAR = 10;

    private const TIPOS = [
        IncidenciaAccesoGraduacion::QR_INVALIDO => 'Código QR inválido',
        IncidenciaAccesoGraduacion::NO_REGISTRADO => 'No registrado',
        IncidenciaAccesoGraduacion::INACTIVO => 'Estatus inactivo',
        IncidenciaAccesoGraduacion::CEREMONIA_NO_VIGENTE => 'Ceremonia no vigente',
        IncidenciaAccesoGraduacion::SIN_INVITACIONES => 'Sin invitaciones disponibles',
        IncidenciaAccesoGraduacion::YA_UTILIZADO => 'Boleto ya utilizado',
    ];

    /** Datos que consulta la campanita */
    public function index(Request $request): JsonResponse
    {
        $incidencias = IncidenciaAccesoGraduacion::query()
            ->latest('fecha_hora')
            ->take(self::POR_LISTAR)
            ->get();

        $vistasEn = $request->user()->notificaciones_vistas_en;

        return response()->json([
            'items' => $incidencias->map(fn (IncidenciaAccesoGraduacion $incidencia) => [
                'id' => $incidencia->id,
                'tipoLabel' => self::TIPOS[$incidencia->tipo] ?? $incidencia->tipo,
                'mensaje' => $incidencia->mensaje,
                'fecha' => $incidencia->fecha_hora->format('d/m/Y'),
                'hora' => $incidencia->fecha_hora->format('h:i A'),
            ])->all(),
            'noVistas' => IncidenciaAccesoGraduacion::query()
                ->when($vistasEn, fn ($query) => $query->where('fecha_hora', '>', $vistasEn))
                ->count(),
        ]);
    }

    
    public function marcarVistas(Request $request): JsonResponse
    {
        
        $request->user()->forceFill(['notificaciones_vistas_en' => now()])->save();

        return response()->json(['noVistas' => 0]);
    }
}
