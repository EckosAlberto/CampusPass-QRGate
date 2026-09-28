<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Mail\EventoUrlCompartida;
use App\Models\EventoTutorias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MisEventosController extends Controller
{
    private const POR_PAGINA = 10;

    /** Pantalla "Mis eventos": solo los eventos creados por el usuario actual. */
    public function index(Request $request): Response
    {
        $eventos = EventoTutorias::query()
            ->where('creado_por', $request->user('eventos')->id)
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('eventos/mis-eventos', [
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
        ]);
    }

    /** Botón "Generar URL de entrada/salida": crea el enlace firmado del kiosco (solo si ya está en su ventana de tiempo). */
    public function generarUrl(Request $request, EventoTutorias $evento): RedirectResponse
    {
        abort_unless($evento->creado_por === $request->user('eventos')->id, 403);

        $datos = $request->validate([
            'tipo' => ['required', 'in:entrada,salida'],
        ]);

        if ($datos['tipo'] === 'entrada' && ! $evento->entradaHabilitada()) {
            throw ValidationException::withMessages([
                'tipo' => 'La URL de entrada se habilita '.EventoTutorias::MINUTOS_ANTES_PARA_HABILITAR_ENTRADA.' minutos antes de que inicie el evento.',
            ]);
        }

        if ($datos['tipo'] === 'salida' && ! $evento->salidaHabilitada()) {
            throw ValidationException::withMessages([
                'tipo' => 'La URL de salida se habilita '.EventoTutorias::MINUTOS_ANTES_PARA_HABILITAR_SALIDA.' minutos antes de que termine el evento.',
            ]);
        }

        $campo = $datos['tipo'] === 'entrada' ? 'entrada_generada_en' : 'salida_generada_en';

        $evento->update([$campo => now()]);

        return back();
    }

    /** Botón "Compartir": manda por correo la URL del kiosco a quien la vaya a operar. */
    public function compartir(Request $request, EventoTutorias $evento): RedirectResponse
    {
        abort_unless($evento->creado_por === $request->user('eventos')->id, 403);

        $datos = $request->validate([
            'tipo' => ['required', 'in:entrada,salida'],
            'correo' => ['required', 'email'],
        ]);

        $url = $datos['tipo'] === 'entrada'
            ? $this->urlSiVigente($evento, 'entrada')
            : $this->urlSiVigente($evento, 'salida');

        if (! $url) {
            throw ValidationException::withMessages([
                'tipo' => 'Esta URL ya caducó. Genera una nueva antes de compartirla.',
            ]);
        }

        Mail::to($datos['correo'])->send(new EventoUrlCompartida($evento, $datos['tipo'], $url));

        return back()->with('success', "URL enviada a {$datos['correo']}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(EventoTutorias $evento): array
    {
        return [
            'id' => $evento->id,
            'nombre' => $evento->nombre,
            'fecha' => $evento->fecha?->format('d/m/Y'),
            'fechaFin' => $evento->fecha_fin?->format('d/m/Y'),
            'horaInicio' => $evento->hora_inicio,
            'horaFin' => $evento->hora_fin,
            'vigente' => $evento->estaVigenteHoy(),
            'entradaHabilitada' => $evento->entradaHabilitada(),
            'salidaHabilitada' => $evento->salidaHabilitada(),
            'urlEntrada' => $this->urlSiVigente($evento, 'entrada'),
            'urlSalida' => $this->urlSiVigente($evento, 'salida'),
        ];
    }

    private function urlSiVigente(EventoTutorias $evento, string $tipo): ?string
    {
        $vigente = $tipo === 'entrada' ? $evento->urlEntradaVigente() : $evento->urlSalidaVigente();

        if (! $vigente) {
            return null;
        }

        $generadaEn = $tipo === 'entrada' ? $evento->entrada_generada_en : $evento->salida_generada_en;

        return URL::temporarySignedRoute(
            'eventos.registro',
            $generadaEn->copy()->addMinutes(EventoTutorias::MINUTOS_DURACION_URL),
            ['evento' => $evento->id, 'tipo' => $tipo],
        );
    }
}
