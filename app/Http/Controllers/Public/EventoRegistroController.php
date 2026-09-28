<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\EventoTutorias;
use App\Models\IncidenciaAcceso;
use App\Models\RegistroAcceso;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventoRegistroController extends Controller
{
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 5;

    
    public function handle(Request $request, EventoTutorias $evento, string $tipo): Response|RedirectResponse
    {
        $tipoMovimiento = strtoupper($tipo);

        $enlaceInvalido = $this->validarEnlace($request, $evento);

        if ($enlaceInvalido) {
            return $enlaceInvalido;
        }

        if ($request->isMethod('post')) {
            return $this->registrarEscaneo($request, $evento, $tipoMovimiento);
        }

        return Inertia::render('eventos/registro', [
            'evento' => ['nombre' => $evento->nombre],
            'tipo' => $tipoMovimiento,
            'urlActual' => $request->fullUrl(),
            'resultado' => $request->session()->get('resultado'),
        ]);
    }

    
    private function validarEnlace(Request $request, EventoTutorias $evento): ?Response
    {
        if (! $request->hasValidSignature()) {
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este enlace ya no está disponible.',
            ]);
        }

        if (! $evento->estaVigenteHoy()) {
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este evento ya no está vigente.',
            ]);
        }

        if (! $evento->horarioPermitido()) {
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este evento está fuera de su horario permitido.',
            ]);
        }

        return null;
    }

    private function registrarEscaneo(Request $request, EventoTutorias $evento, string $tipoMovimiento): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $codigo = trim($validated['codigo']);
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        // ¿QR válido? 
        if (! preg_match('/^[A-Za-z0-9]{1,10}$/', $codigo)) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::QR_INVALIDO, "Código escaneado con formato inválido: \"{$codigo}\"", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido. Intenta nuevamente.',
            ]));
        }

        // ¿Usuario registrado?
        $alumno = Alumno::find($codigo);

        if (! $alumno) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::NO_REGISTRADO, "Número de control no encontrado: {$codigo}", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró tu registro como estudiante.',
            ]));
        }

        // ¿Usuario activo?
        if (! $alumno->estaActivo()) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::INACTIVO, "Estudiante con estatus '{$alumno->estatus_alumno}' intentó acceder", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Tu estatus actual no permite el acceso a este evento.',
            ]));
        }

        $ultimoRegistro = RegistroAcceso::where('no_de_control', $alumno->no_de_control)
            ->where('id_evento', $evento->id)
            ->whereDate('fecha_hora', $fechaHora->toDateString())
            ->latest('fecha_hora')
            ->first();

        if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::DOBLE_ESCANEO, 'Escaneo duplicado en menos de '.self::VENTANA_DOBLE_ESCANEO_SEGUNDOS.' segundos', $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Ya se registró tu acceso. Espera un momento antes de volver a escanear.',
            ]));
        }

        $registro = RegistroAcceso::create([
            'no_de_control' => $alumno->no_de_control,
            'id_evento' => $evento->id,
            'tipo_movimiento' => $tipoMovimiento,
            'fecha_hora' => $fechaHora,
        ]);

        return back()->with('resultado', $this->conId([
            'estado' => 'ok',
            'tipoMovimiento' => $tipoMovimiento,
            'alumno' => [
                'nombre' => $alumno->nombreCompleto(),
                'noDeControl' => $alumno->no_de_control,
                'carrera' => $alumno->nombreCarrera(),
            ],
            'hora' => $registro->fecha_hora->format('H:i'),
            'mensaje' => $tipoMovimiento === 'ENTRADA'
                ? '¡Bienvenido! Tu entrada quedó registrada.'
                : '¡Gracias por tu asistencia! Tu salida quedó registrada.',
        ]));
    }

    private function registrarIncidencia(?string $codigo, string $idEvento, string $tipo, string $mensaje, ?CarbonInterface $fechaHora = null): void
    {
        IncidenciaAcceso::create([
            'no_de_control' => $codigo !== null ? substr($codigo, 0, 10) : null,
            'id_evento' => $idEvento,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'fecha_hora' => $fechaHora ?? now(),
        ]);
    }

    
    private function conId(array $datos): array
    {
        return ['id' => (string) Str::uuid(), ...$datos];
    }
}
