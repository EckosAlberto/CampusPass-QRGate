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

// Controlador para manejar el registro de acceso a eventos de tutorías mediante escaneo de códigos QR.
class EventoRegistroController extends Controller
{
    // Tiempo mínimo en segundos para permitir un nuevo escaneo del mismo código QR (previene doble escaneo).
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 5;

    // Maneja la solicitud de registro de acceso a un evento de tutorías, validando el enlace, el código QR y registrando el acceso o incidencias según corresponda.
    public function handle(Request $request, EventoTutorias $evento, string $tipo): Response|RedirectResponse
    {
        $tipoMovimiento = strtoupper($tipo); // Convierte el tipo de movimiento a mayúsculas para estandarizarlo.

        $enlaceInvalido = $this->validarEnlace($request, $evento); // Valida el enlace y el estado del evento, devolviendo una respuesta si es inválido.

        // Si el enlace es inválido, retorna la respuesta correspondiente (vista de error).
        if ($enlaceInvalido) {
            return $enlaceInvalido;
        }

        // Si la solicitud es de tipo POST, procede a registrar el escaneo del código QR.
        if ($request->isMethod('post')) {
            return $this->registrarEscaneo($request, $evento, $tipoMovimiento);
        }

        // Si la solicitud es de tipo GET, muestra la vista de registro de acceso al evento con la información del evento y el tipo de movimiento.
        return Inertia::render('eventos/registro', [
            'evento' => ['nombre' => $evento->nombre],
            'tipo' => $tipoMovimiento,
            'urlActual' => $request->fullUrl(),
            'resultado' => $request->session()->get('resultado'),
        ]);
    }

    // Valida que el enlace de acceso al evento sea válido, que el evento esté vigente y que esté dentro del horario permitido. Devuelve una respuesta de error si alguna validación falla.
    private function validarEnlace(Request $request, EventoTutorias $evento): ?Response
    {
        if (! $request->hasValidSignature()) { // Verifica si el enlace tiene una firma válida (para prevenir accesos no autorizados).
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este enlace ya no está disponible.',
            ]);
        }

        if (! $evento->estaVigenteHoy()) { // Verifica si el evento está vigente en la fecha actual.
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este evento ya no está vigente.',
            ]);
        }

        if (! $evento->horarioPermitido()) { // Verifica si el evento está dentro del horario permitido para el registro de acceso.
            return Inertia::render('eventos/enlace-no-disponible', [
                'mensaje' => 'Este evento está fuera de su horario permitido.',
            ]);
        }

        return null;
    }

    // Registra el escaneo del código QR, validando el formato del código, la existencia y estado del alumno, y previniendo doble escaneo. Si todo es válido, registra el acceso y devuelve una respuesta con los detalles del registro.
    private function registrarEscaneo(Request $request, EventoTutorias $evento, string $tipoMovimiento): RedirectResponse
    {
        $validated = $request->validate([ // Valida que el código QR sea requerido y que sea una cadena de texto, y que la fecha de captura sea opcional, una fecha válida y no futura.
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $codigo = trim($validated['codigo']); // Limpia el código QR recibido eliminando espacios en blanco al inicio y al final.
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        // ¿QR válido? 
        if (! preg_match('/^[A-Za-z0-9]{1,10}$/', $codigo)) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::QR_INVALIDO, "Código escaneado con formato inválido: \"{$codigo}\"", $fechaHora);

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el código QR escaneado no es válido.
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido. Intenta nuevamente.',
            ]));
        }

        // ¿Usuario registrado?
        $alumno = Alumno::find($codigo);

        if (! $alumno) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::NO_REGISTRADO, "Número de control no encontrado: {$codigo}", $fechaHora);

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que no se encontró el registro del estudiante con el número de control escaneado.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró tu registro como estudiante.',
            ]));
        }

        // ¿Usuario activo?
        if (! $alumno->estaActivo()) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::INACTIVO, "Estudiante con estatus '{$alumno->estatus_alumno}' intentó acceder", $fechaHora);

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el estudiante no tiene un estatus activo y no puede acceder al evento.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Tu estatus actual no permite el acceso a este evento.',
            ]));
        }

        // Previene doble escaneo: verifica si el último registro de acceso del estudiante para el evento en la misma fecha fue realizado en un intervalo de tiempo menor al definido (5 segundos). Si es así, registra una incidencia de doble escaneo y devuelve un mensaje de error.
        $ultimoRegistro = RegistroAcceso::where('no_de_control', $alumno->no_de_control)
            ->where('id_evento', $evento->id)
            ->whereDate('fecha_hora', $fechaHora->toDateString())
            ->latest('fecha_hora')
            ->first();

        // Si el último registro existe y la diferencia de tiempo entre el último registro y el nuevo escaneo es menor a la ventana definida, se considera un doble escaneo.
        if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::DOBLE_ESCANEO, 'Escaneo duplicado en menos de '.self::VENTANA_DOBLE_ESCANEO_SEGUNDOS.' segundos', $fechaHora);

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el acceso ya fue registrado recientemente y que debe esperar antes de volver a escanear.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Ya se registró tu acceso. Espera un momento antes de volver a escanear.',
            ]));
        }

        // Registra el acceso del estudiante al evento, creando un nuevo registro en la base de datos con el número de control del estudiante, el ID del evento, el tipo de movimiento (entrada o salida) y la fecha y hora del escaneo.
        $registro = RegistroAcceso::create([
            'no_de_control' => $alumno->no_de_control,
            'id_evento' => $evento->id,
            'tipo_movimiento' => $tipoMovimiento,
            'fecha_hora' => $fechaHora,
        ]);

        return back()->with('resultado', $this->conId([ // Devuelve un mensaje de éxito indicando que el acceso del estudiante fue registrado correctamente, incluyendo detalles del estudiante y el tipo de movimiento.
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

    // Registra una incidencia de acceso en la base de datos, incluyendo el código QR escaneado, el ID del evento, el tipo de incidencia, un mensaje descriptivo y la fecha y hora del escaneo (si se proporciona).
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
        return ['id' => (string) Str::uuid(), ...$datos]; // Agrega un ID único a los datos para identificar cada resultado de manera individual.
    }
}
