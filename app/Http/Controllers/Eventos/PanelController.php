<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Models\EventoTutorias;
use App\Models\RegistroAcceso;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    private const POR_PAGINA = 10;

    /** Pantalla de Inicio: evento activo del día y su resumen de asistencia. */
    public function index(Request $request): Response
    {
        $eventoActivo = EventoTutorias::query()
            ->where('estatus', true)
            ->whereDate('fecha', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->orderBy('hora_inicio')
            ->with(['tipoEvento', 'tutor.personal'])
            ->first();

        $registrosHoyBase = RegistroAcceso::whereNotNull('id_evento')
            ->whereDate('fecha_hora', today());

        $asistentesHoy = (clone $registrosHoyBase)->distinct('no_de_control')->count('no_de_control');
        $entradasHoy = (clone $registrosHoyBase)->where('tipo_movimiento', 'ENTRADA')->count();
        $salidasHoy = (clone $registrosHoyBase)->where('tipo_movimiento', 'SALIDA')->count();

        $asistencia = (clone $registrosHoyBase)
            ->with(['alumno', 'evento'])
            ->orderByDesc('fecha_hora')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('eventos/inicio', [
            'usuario' => $request->user('eventos'),
            'resumen' => [
                'eventoActivo' => $eventoActivo ? [
                    'id' => $eventoActivo->id,
                    'nombre' => $eventoActivo->nombre,
                    'tipoEvento' => $eventoActivo->tipoEvento?->nombre,
                    'tutor' => $eventoActivo->tutor?->nombreCompleto(),
                    'horaInicio' => $eventoActivo->hora_inicio,
                    'horaFin' => $eventoActivo->hora_fin,
                ] : null,
                'asistentesHoy' => $asistentesHoy,
                'entradasHoy' => $entradasHoy,
                'salidasHoy' => $salidasHoy,
                'pendientesSalida' => max(0, $entradasHoy - $salidasHoy),
            ],
            'asistencia' => [
                'data' => collect($asistencia->items())->map(fn (RegistroAcceso $registro) => [
                    'id' => $registro->id,
                    'fecha' => $registro->fecha_hora->format('d/m/Y'),
                    'hora' => $registro->fecha_hora->format('h:i A'),
                    'nombre' => $registro->alumno?->nombreCompleto() ?? 'Desconocido',
                    'noDeControl' => $registro->no_de_control,
                    'evento' => $registro->evento?->nombre,
                    'tipoMovimiento' => $registro->tipo_movimiento,
                ])->all(),
                'meta' => [
                    'paginaActual' => $asistencia->currentPage(),
                    'ultimaPagina' => $asistencia->lastPage(),
                    'total' => $asistencia->total(),
                    'desde' => $asistencia->firstItem(),
                    'hasta' => $asistencia->lastItem(),
                ],
            ],
        ]);
    }
}
