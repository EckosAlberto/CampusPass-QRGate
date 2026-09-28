<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Models\IncidenciaAcceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    private const POR_LISTAR = 10;

    private const TIPOS = [
        IncidenciaAcceso::QR_INVALIDO => 'Código QR inválido',
        IncidenciaAcceso::NO_REGISTRADO => 'No registrado',
        IncidenciaAcceso::INACTIVO => 'Estatus inactivo',
        IncidenciaAcceso::DOBLE_ESCANEO => 'Doble escaneo',
    ];

    /** Datos que consulta la campanita: las últimas incidencias de Eventos y cuántas no se han visto. */
    public function index(Request $request): JsonResponse
    {
        
        $incidencias = IncidenciaAcceso::query()
            ->whereNotNull('id_evento')
            ->latest('fecha_hora')
            ->take(self::POR_LISTAR)
            ->get();

        $vistasEn = $request->user()->notificaciones_vistas_en;

        return response()->json([
            'items' => $incidencias->map(fn (IncidenciaAcceso $incidencia) => [
                'id' => $incidencia->id,
                'tipoLabel' => self::TIPOS[$incidencia->tipo] ?? $incidencia->tipo,
                'mensaje' => $incidencia->mensaje,
                'fecha' => $incidencia->fecha_hora->format('d/m/Y'),
                'hora' => $incidencia->fecha_hora->format('h:i A'),
            ])->all(),
            'noVistas' => IncidenciaAcceso::query()
                ->whereNotNull('id_evento')
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
