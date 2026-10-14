<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\IncidenciaAccesoGraduacion;
use App\Models\RegistroAccesoGraduacion;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CeremoniaRegistroController extends Controller
{
    // Tiempo en segundos para considerar un doble escaneo (mismo boleto leído dos veces en pocos segundos)
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 3;

    // Muestra la pantalla de registro de acceso a la ceremonia de graduación y procesa el escaneo del código QR.
    public function handle(Request $request, CeremoniaGraduacion $ceremonia): Response|RedirectResponse
    {
        $enlaceInvalido = $this->validarEnlace($request, $ceremonia); // Valida el enlace y la vigencia de la ceremonia, devolviendo una respuesta si es inválido.

        if ($enlaceInvalido) { // Si el enlace es inválido, retorna la respuesta correspondiente (vista de error).
            return $enlaceInvalido;
        }

        if ($request->isMethod('post')) { // Si la solicitud es de tipo POST, procede a registrar el escaneo del código QR.
            return $this->registrarEscaneo($request, $ceremonia);
        }

        return Inertia::render('graduacion/registro', [ // Si la solicitud es de tipo GET, muestra la vista de registro de acceso a la ceremonia con la información de la ceremonia y el resultado del último escaneo (si existe).
            'ceremonia' => ['nombre' => $ceremonia->nombre],
            'urlActual' => $request->fullUrl(),
            'resultado' => $request->session()->get('resultado'),
        ]);
    }

    // Valida que el enlace de acceso a la ceremonia sea válido y que la ceremonia esté vigente.
    private function validarEnlace(Request $request, CeremoniaGraduacion $ceremonia): ?Response
    {
        if (! $request->hasValidSignature()) { // Verifica si el enlace tiene una firma válida (para prevenir accesos no autorizados).
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Este enlace ya no está disponible.',
            ]);
        }

        if (! $ceremonia->estaVigente()) { // Verifica si la ceremonia está vigente en la fecha actual.
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Esta ceremonia ya no está vigente.',
            ]);
        }

        if (now()->lessThan($ceremonia->pantallaHabilitadaDesde())) { // Verifica si la ceremonia aún no ha habilitado la pantalla de registro de acceso.
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'El registro de acceso a esta ceremonia aún no se ha habilitado.',
            ]);
        }

        return null;
    }

    // Registra el escaneo del código QR y maneja las diferentes validaciones y casos de error.
    private function registrarEscaneo(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $validated = $request->validate([ // Valida que el código QR sea requerido y que la fecha de captura sea opcional y válida.
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $codigo = trim($validated['codigo']); // Obtiene el código QR escaneado y lo limpia de espacios en blanco.
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        // Validación del formato del código QR: debe ser alfanumérico y tener entre 20 y 40 caracteres.
        if (! preg_match('/^[A-Za-z0-9]{20,40}$/', $codigo)) {
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::QR_INVALIDO, "Código escaneado con formato inválido: \"{$codigo}\"", $fechaHora); // Registra una incidencia de acceso indicando que el código QR escaneado tiene un formato inválido.

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el código QR escaneado no es válido.
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido. Intenta nuevamente.',
            ]));
        }

        // ¿Boleto registrado?
        $boleto = BoletoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->where('codigo', $codigo)->first();

        if (! $boleto) { // Si no se encuentra un boleto registrado para el código escaneado, se registra una incidencia de acceso indicando que no se encontró un boleto y se devuelve un mensaje de error.
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::NO_REGISTRADO, "Boleto no encontrado para el código: {$codigo}", $fechaHora); // Registra una incidencia de acceso indicando que no se encontró un boleto registrado para el código escaneado.

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que no se encontró un boleto registrado para el código escaneado.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró un boleto registrado para este código.',
            ]));
        }

        $alumno = $boleto->alumno;

        // ¿Alumno activo?
        if (! $alumno || ! $alumno->estaActivo()) { // Si no se encuentra un alumno asociado al boleto o el alumno no está activo, se registra una incidencia de acceso indicando que el alumno no está activo y se devuelve un mensaje de error.
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::INACTIVO, "Alumno asociado con estatus '{$alumno?->estatus_alumno}' intentó acceder", $fechaHora); // Registra una incidencia de acceso indicando que el alumno asociado al boleto no está activo.

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el alumno asociado al boleto no está activo y no puede acceder a la ceremonia.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'El estatus actual del egresado no permite el acceso a esta ceremonia.',
            ]));
        }

        // ¿Horario corresponde al tipo de acceso de este boleto? 
        if (! $ceremonia->accesoPermitido($boleto->tipo, $fechaHora)) { // Si el horario actual no corresponde al tipo de acceso del boleto (egresado o invitado), se registra una incidencia de acceso indicando que el escaneo está fuera del horario permitido y se devuelve un mensaje de error.
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::CEREMONIA_NO_VIGENTE, 'Escaneo fuera del horario permitido para este tipo de boleto', $fechaHora); // Registra una incidencia de acceso indicando que el escaneo del boleto está fuera del horario permitido para su tipo.

            return back()->with('resultado', $this->conId([ // Devuelve un mensaje de error indicando que el boleto está fuera del horario de acceso permitido para su tipo.
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Este boleto está fuera de su horario de acceso permitido.',
            ]));
        }

        // Bloqueo para evitar doble escaneo simultáneo del mismo boleto
        try {
            $resultado = Cache::lock("registro-acceso:graduacion:{$boleto->id}", 10)
                ->block(5, function () use ($codigo, $ceremonia, $boleto, $alumno, $fechaHora) {
                    $ultimoRegistro = RegistroAccesoGraduacion::where('fk_id_boleto', $boleto->id)
                        ->latest('fecha_hora')
                        ->first();

                    // Doble escaneo / muy rápido: el mismo boleto leído dos
                    // veces en segundos, no dos invitados distintos.
                    if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) { // Si el último registro de acceso del boleto existe y la diferencia de tiempo entre el último registro y el nuevo escaneo es menor a la ventana definida, se considera un doble escaneo.
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::DOBLE_ESCANEO, 'Escaneo duplicado en menos de '.self::VENTANA_DOBLE_ESCANEO_SEGUNDOS.' segundos', $fechaHora); // Registra una incidencia de acceso indicando que el escaneo del boleto es duplicado en menos de la ventana definida.

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Ya se registró este acceso. Espera un momento antes de volver a escanear.',
                        ];
                    }

                    // ¿Invitaciones disponibles? (solo aplica a boletos de invitado)
                    if (! $boleto->invitacionesDisponibles()) { // Si el boleto es de tipo invitado y no tiene invitaciones disponibles, se registra una incidencia de acceso indicando que se agotaron las invitaciones autorizadas para este boleto y se devuelve un mensaje de error.
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::SIN_INVITACIONES, 'Se agotaron las invitaciones autorizadas para este boleto', $fechaHora);

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Ya se registraron todos los invitados autorizados para este boleto.',
                        ];
                    }

                    // ¿Ya utilizó este pase? (solo aplica a boletos de graduado, de un solo uso)
                    if ($boleto->yaUtilizado()) { // Si el boleto es de tipo graduado y ya ha registrado su entrada anteriormente, se registra una incidencia de acceso indicando que el boleto ya había registrado su entrada y se devuelve un mensaje de error.
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::YA_UTILIZADO, 'El boleto del egresado ya había registrado su entrada', $fechaHora);

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Este boleto ya registró su entrada anteriormente.',
                        ];
                    }

                    $registro = RegistroAccesoGraduacion::create([ // Crea un nuevo registro de acceso en la base de datos con el ID del boleto y la fecha y hora del escaneo.
                        'fk_id_boleto' => $boleto->id,
                        'fecha_hora' => $fechaHora,
                    ]);

                    // Obtiene el boleto de invitado correspondiente al alumno y la ceremonia, si existe, para poder mostrar información sobre los invitados autorizados y registrados.
                    $invitadoBoleto = BoletoGraduacion::query()
                        ->where('fk_id_ceremonia', $ceremonia->id)
                        ->where('no_de_control', $alumno->no_de_control)
                        ->where('tipo', BoletoGraduacion::TIPO_INVITADO)
                        ->first();
                    // Retorna un arreglo con la información del resultado del escaneo, incluyendo el estado, tipo de boleto, información del alumno, hora del registro, invitados autorizados y registrados, lugares disponibles y un mensaje de éxito según el tipo de boleto.
                    return [ 
                        'estado' => 'ok',
                        'tipoBoleto' => $boleto->tipo,
                        'alumno' => [
                            'nombre' => $alumno->nombreCompleto(),
                            'noDeControl' => $alumno->no_de_control,
                            'carrera' => $alumno->nombreCarrera(),
                        ],
                        'hora' => $registro->fecha_hora->format('h:i A'),
                        'invitadosAutorizados' => $invitadoBoleto?->invitados_autorizados,
                        'invitadosRegistrados' => $invitadoBoleto?->invitadosRegistrados(),
                        'lugaresDisponibles' => $invitadoBoleto
                            ? max(0, ($invitadoBoleto->invitados_autorizados ?? 0) - $invitadoBoleto->invitadosRegistrados())
                            : null,
                        'mensaje' => $boleto->tipo === BoletoGraduacion::TIPO_GRADUADO
                            ? '¡Felicidades! Tu entrada quedó registrada.'
                            : '¡Bienvenido! Tu entrada como invitado quedó registrada.',
                    ];
                });
        } catch (LockTimeoutException) { // Si ocurre un error de bloqueo (por ejemplo, si otro escaneo está siendo procesado al mismo tiempo), se devuelve un mensaje de error indicando que el sistema está ocupado y que se intente nuevamente más tarde.
            
            $resultado = [
                'estado' => 'error',
                'titulo' => 'Inténtalo de nuevo',
                'mensaje' => 'El sistema está ocupado procesando otro escaneo. Espera un momento e inténtalo de nuevo.',
            ];
        }

        return back()->with('resultado', $this->conId($resultado)); // Devuelve la respuesta final con el resultado del escaneo, ya sea exitoso o con algún error, incluyendo un ID único para identificar la respuesta.
    }

    // Registra una incidencia de acceso a la ceremonia de graduación en la base de datos, incluyendo el código escaneado, el ID de la ceremonia, el tipo de incidencia, un mensaje descriptivo y la fecha y hora del escaneo (si se proporciona).
    private function registrarIncidencia(?string $codigo, string $idCeremonia, string $tipo, string $mensaje, ?CarbonInterface $fechaHora = null): void
    {
        IncidenciaAccesoGraduacion::create([
            'fk_id_ceremonia' => $idCeremonia,
            'codigo' => $codigo !== null ? substr($codigo, 0, 40) : null,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'fecha_hora' => $fechaHora ?? now(),
        ]);
    }

    // Agrega un ID único a los datos proporcionados y devuelve un arreglo con el ID y los datos originales. Esto se utiliza para identificar de manera única cada resultado de escaneo en la respuesta.
    private function conId(array $datos): array
    {
        return ['id' => (string) Str::uuid(), ...$datos];
    }
}
