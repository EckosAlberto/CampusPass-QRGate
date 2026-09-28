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
    
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 3;

    
    public function handle(Request $request, CeremoniaGraduacion $ceremonia): Response|RedirectResponse
    {
        $enlaceInvalido = $this->validarEnlace($request, $ceremonia);

        if ($enlaceInvalido) {
            return $enlaceInvalido;
        }

        if ($request->isMethod('post')) {
            return $this->registrarEscaneo($request, $ceremonia);
        }

        return Inertia::render('graduacion/registro', [
            'ceremonia' => ['nombre' => $ceremonia->nombre],
            'urlActual' => $request->fullUrl(),
            'resultado' => $request->session()->get('resultado'),
        ]);
    }

    private function validarEnlace(Request $request, CeremoniaGraduacion $ceremonia): ?Response
    {
        if (! $request->hasValidSignature()) {
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Este enlace ya no está disponible.',
            ]);
        }

        if (! $ceremonia->estaVigente()) {
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Esta ceremonia ya no está vigente.',
            ]);
        }

        if (now()->lessThan($ceremonia->pantallaHabilitadaDesde())) {
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'El registro de acceso a esta ceremonia aún no se ha habilitado.',
            ]);
        }

        return null;
    }

    private function registrarEscaneo(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $codigo = trim($validated['codigo']);
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        
        if (! preg_match('/^[A-Za-z0-9]{20,40}$/', $codigo)) {
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::QR_INVALIDO, "Código escaneado con formato inválido: \"{$codigo}\"", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido. Intenta nuevamente.',
            ]));
        }

        // ¿Boleto registrado?
        $boleto = BoletoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->where('codigo', $codigo)->first();

        if (! $boleto) {
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::NO_REGISTRADO, "Boleto no encontrado para el código: {$codigo}", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró un boleto registrado para este código.',
            ]));
        }

        $alumno = $boleto->alumno;

        // ¿Alumno activo?
        if (! $alumno || ! $alumno->estaActivo()) {
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::INACTIVO, "Alumno asociado con estatus '{$alumno?->estatus_alumno}' intentó acceder", $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'El estatus actual del egresado no permite el acceso a esta ceremonia.',
            ]));
        }

        // ¿Horario corresponde al tipo de acceso de este boleto? 
        if (! $ceremonia->accesoPermitido($boleto->tipo, $fechaHora)) {
            $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::CEREMONIA_NO_VIGENTE, 'Escaneo fuera del horario permitido para este tipo de boleto', $fechaHora);

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Este boleto está fuera de su horario de acceso permitido.',
            ]));
        }

        
        try {
            $resultado = Cache::lock("registro-acceso:graduacion:{$boleto->id}", 10)
                ->block(5, function () use ($codigo, $ceremonia, $boleto, $alumno, $fechaHora) {
                    $ultimoRegistro = RegistroAccesoGraduacion::where('fk_id_boleto', $boleto->id)
                        ->latest('fecha_hora')
                        ->first();

                    // Doble escaneo / muy rápido: el mismo boleto leído dos
                    // veces en segundos, no dos invitados distintos.
                    if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) {
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::DOBLE_ESCANEO, 'Escaneo duplicado en menos de '.self::VENTANA_DOBLE_ESCANEO_SEGUNDOS.' segundos', $fechaHora);

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Ya se registró este acceso. Espera un momento antes de volver a escanear.',
                        ];
                    }

                    // ¿Invitaciones disponibles? (solo aplica a boletos de invitado)
                    if (! $boleto->invitacionesDisponibles()) {
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::SIN_INVITACIONES, 'Se agotaron las invitaciones autorizadas para este boleto', $fechaHora);

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Ya se registraron todos los invitados autorizados para este boleto.',
                        ];
                    }

                    // ¿Ya utilizó este pase? (solo aplica a boletos de graduado, de un solo uso)
                    if ($boleto->yaUtilizado()) {
                        $this->registrarIncidencia($codigo, $ceremonia->id, IncidenciaAccesoGraduacion::YA_UTILIZADO, 'El boleto del egresado ya había registrado su entrada', $fechaHora);

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Este boleto ya registró su entrada anteriormente.',
                        ];
                    }

                    $registro = RegistroAccesoGraduacion::create([
                        'fk_id_boleto' => $boleto->id,
                        'fecha_hora' => $fechaHora,
                    ]);

                    $invitadoBoleto = BoletoGraduacion::query()
                        ->where('fk_id_ceremonia', $ceremonia->id)
                        ->where('no_de_control', $alumno->no_de_control)
                        ->where('tipo', BoletoGraduacion::TIPO_INVITADO)
                        ->first();

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
        } catch (LockTimeoutException) {
            
            $resultado = [
                'estado' => 'error',
                'titulo' => 'Inténtalo de nuevo',
                'mensaje' => 'El sistema está ocupado procesando otro escaneo. Espera un momento e inténtalo de nuevo.',
            ];
        }

        return back()->with('resultado', $this->conId($resultado));
    }

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

    
    private function conId(array $datos): array
    {
        return ['id' => (string) Str::uuid(), ...$datos];
    }
}
