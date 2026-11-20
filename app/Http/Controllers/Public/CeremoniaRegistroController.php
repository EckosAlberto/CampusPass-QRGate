<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\IdentificadorFoto;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\IncidenciaAccesoGraduacion;
use App\Models\RegistroAccesoGraduacion;
use App\Services\IdentificadorAcceso;
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
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 8;

    public function handle(Request $request, CeremoniaGraduacion $ceremonia, IdentificadorAcceso $resolver): Response|RedirectResponse
    {
        $enlaceInvalido = $this->validarEnlace($request, $ceremonia); 

        if ($enlaceInvalido) { 
            return $enlaceInvalido;
        }

        if ($request->isMethod('post')) { 
            return $this->registrarEscaneo($request, $ceremonia, $resolver);
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

    private function registrarEscaneo(Request $request, CeremoniaGraduacion $ceremonia, IdentificadorAcceso $resolver): RedirectResponse
    {
        $validated = $request->validate([ 
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $entrada = trim($validated['codigo']); 
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        $codigoBuscado = $entrada;
        if (preg_match('/^https?:\/\//i', $entrada)) {
            $partes = explode('/', (string) parse_url($entrada, PHP_URL_PATH));
            $codigoBuscado = rawurldecode((string) end($partes));
        }

        $alumnoIdentificado = $resolver->resolver($codigoBuscado)['alumno'];

        $boleto = BoletoGraduacion::query()
            ->where('fk_id_ceremonia', $ceremonia->id)
            ->where(function ($query) use ($entrada, $codigoBuscado, $alumnoIdentificado) {
                $query->where('codigo', $entrada)
                      ->orWhere('codigo', $codigoBuscado)
                      ->orWhereHas('alumno', function ($q) use ($entrada, $codigoBuscado) {
                          $q->where('no_de_control', $entrada)
                            ->orWhere('no_de_control', $codigoBuscado);
                      })
                      ->when($alumnoIdentificado, fn ($q) => $q->orWhereHas('alumno', fn ($alumnoQuery) => $alumnoQuery->where('no_de_control', $alumnoIdentificado->no_de_control)));
            })
            ->first();

        if (! $boleto) { 
            $this->registrarIncidencia($codigoBuscado, $ceremonia->id, IncidenciaAccesoGraduacion::NO_REGISTRADO, "Boleto o alumno no encontrado para el código: {$codigoBuscado}", $fechaHora); 

            return back()->with('resultado', $this->conId([ 
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró un boleto registrado para esta credencial.',
            ]));
        }

        $alumno = $boleto->alumno;

        if (! $alumno || ! $alumno->estaActivo()) { 
            $this->registrarIncidencia($codigoBuscado, $ceremonia->id, IncidenciaAccesoGraduacion::INACTIVO, "Alumno con estatus inactivo", $fechaHora); 

            return back()->with('resultado', $this->conId([ 
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'El estatus actual del egresado no permite el acceso.',
            ]));
        }

        try {
            $resultado = Cache::lock("registro-acceso:graduacion:{$boleto->id}", 10)
                ->block(5, function () use ($ceremonia, $boleto, $alumno, $fechaHora) {
                    
                    $ultimoRegistro = RegistroAccesoGraduacion::where('fk_id_boleto', $boleto->id)
                        ->latest('fecha_hora')
                        ->first();

                    if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) { 
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
                        ->where('tipo', BoletoGraduacion::TIPO_INVITADO)
                        ->whereHas('alumno', function ($q) use ($alumno) {
                            $q->where('no_de_control', $alumno->no_de_control);
                        })
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
                'mensaje' => 'El sistema está ocupado procesando otro escaneo.',
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
