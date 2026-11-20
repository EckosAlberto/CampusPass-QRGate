<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eventos\StoreEventoRequest;
use App\Http\Requests\Eventos\UpdateEventoRequest;
use App\Models\EventoTutorias;
use App\Models\PeriodoTutorias;
use App\Models\RegistroAcceso;
use App\Models\TipoEvento;
use App\Models\Tutor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends Controller
{
    private const POR_PAGINA = 10;

    public function index(Request $request): Response
    {
        $eventos = EventoTutorias::query()
            ->with(['tipoEvento', 'periodoTutorias', 'tutor.personal'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('eventos/gestion-eventos', [
            'usuarioId' => $request->user('eventos')->id,
            'eventos' => [
                'data' => collect($eventos->items())->map(fn (EventoTutorias $evento) => $this->serializar($evento))->all(),
                'meta' => [
                    'paginaActual' => $eventos->currentPage(),
                    'ultimaPagina' => $eventos->lastPage(),
                    'total' => $eventos->total(),
                    'desde' => $eventos->firstItem(),
                    'hasta' => $eventos->lastItem(),
                ],
            ],
            'catalogos' => [
                'tiposEvento' => TipoEvento::query()->orderBy('nombre')->get()
                    ->map(fn (TipoEvento $tipo) => ['value' => $tipo->id, 'label' => $tipo->nombre])->all(),
                'periodosTutorias' => PeriodoTutorias::query()->orderByDesc('fecha_inicio')->get()
                    ->map(fn (PeriodoTutorias $periodo) => ['value' => $periodo->id, 'label' => $periodo->clave])->all(),
                'tutores' => Tutor::query()->with('personal')->get()
                    ->map(fn (Tutor $tutor) => ['value' => $tutor->id, 'label' => $tutor->nombreCompleto() ?? $tutor->clave_grupo])->all(),
            ],
        ]);
    }

    public function store(StoreEventoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $inicio = \Carbon\Carbon::parse($datos['hora_inicio']);
        $fin = \Carbon\Carbon::parse($datos['hora_fin']);
        $datos['horas'] = round($inicio->diffInMinutes($fin) / 60, 2);

            EventoTutorias::create([
                ...$datos,
                'estatus' => true,
                'creado_por' => $request->user('eventos')->id,
            ]);

        return back();
    }

    public function update(UpdateEventoRequest $request, EventoTutorias $evento): RedirectResponse
    {
        $datos = $request->validated();

        $inicio = \Carbon\Carbon::parse($datos['hora_inicio']);
        $fin = \Carbon\Carbon::parse($datos['hora_fin']);
        $datos['horas'] = round($inicio->diffInMinutes($fin) / 60, 2);

        $evento->update($datos);

        return back();
    }

    public function actualizarEstatus(Request $request, EventoTutorias $evento): RedirectResponse
    {
        $datos = $request->validate([
            'estatus' => ['required', 'boolean'],
        ]);

        $evento->update($datos);

        return back();
    }

    public function destroy(Request $request, EventoTutorias $evento): RedirectResponse
    {
        if ($evento->creado_por !== $request->user('eventos')->id) {
            abort(403, 'Solo la persona que creó el evento puede eliminarlo.');
        }

        if ($evento->estatus) {
            throw ValidationException::withMessages([
                'evento' => 'No se puede eliminar: el evento está activo. Desactívalo primero.',
            ]);
        }
        
        if (RegistroAcceso::where('id_evento', $evento->id)->exists()) {
            throw ValidationException::withMessages([
                'evento' => 'No se puede eliminar: este evento ya tiene accesos registrados. Desactívalo en su lugar.',
            ]);
        }

        $evento->delete();

        return back();
    }

    private function serializar(EventoTutorias $evento): array
    {
        return [
            'id' => $evento->id,
            'nombre' => $evento->nombre,
            'fecha' => $evento->fecha?->format('Y-m-d'),
            'fechaFin' => $evento->fecha_fin?->format('Y-m-d'),
            'horaInicio' => $evento->hora_inicio,
            'horaFin' => $evento->hora_fin,
            'horas' => $evento->horas,
            'tipoEvento' => $evento->tipoEvento ? ['value' => $evento->tipoEvento->id, 'label' => $evento->tipoEvento->nombre] : null,
            'periodoTutorias' => $evento->periodoTutorias ? ['value' => $evento->periodoTutorias->id, 'label' => $evento->periodoTutorias->clave] : null,
            'tutor' => $evento->tutor ? ['value' => $evento->tutor->id, 'label' => $evento->tutor->nombreCompleto() ?? $evento->tutor->clave_grupo] : null,
            'estatus' => $evento->estatus,
            'creadoPor' => $evento->creado_por,
        ];
    }
}
