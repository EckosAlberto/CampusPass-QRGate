<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\EventoTutorias;
use App\Models\IncidenciaAcceso;
use App\Models\RegistroAcceso;
use App\Services\IdentificadorAcceso;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventoRegistroController extends Controller
{
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 8;

    public function handle(Request $request, EventoTutorias $evento, string $tipo, IdentificadorAcceso $resolver): Response|RedirectResponse
    {
        $tipoMovimiento = strtoupper($tipo);

        $enlaceInvalido = $this->validarEnlace($request, $evento); 

        if ($enlaceInvalido) {
            return $enlaceInvalido;
        }

        if ($request->isMethod('post')) {
            return $this->registrarEscaneo($request, $evento, $tipoMovimiento, $resolver);
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

    private function registrarEscaneo(Request $request, EventoTutorias $evento, string $tipoMovimiento, IdentificadorAcceso $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $codigoCrudo = trim($validated['codigo']);
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        $codigo = $codigoCrudo;

        if (preg_match('/^https?:\/\//i', $codigoCrudo)) {
            $partes = explode('/', (string) parse_url($codigoCrudo, PHP_URL_PATH));
            $segmento = rawurldecode((string) end($partes));
            if ($segmento !== '') {
                $codigo = $segmento;
            }
        }

        if (! preg_match('/^[A-Za-z0-9]{1,64}$/', $codigo)) {
            $this->registrarIncidencia($codigoCrudo, $evento->id, IncidenciaAcceso::QR_INVALIDO, "Código escaneado con formato inválido.", $fechaHora);

            return back()->with('resultado', $this->conId([ 
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido.',
            ]));
        }

        ['alumno' => $alumno, 'personal' => $personal] = $resolver->resolver($codigo);

        if (! $alumno && ! $personal) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::NO_REGISTRADO, "Identificador no encontrado: {$codigo}", $fechaHora);

            return back()->with('resultado', $this->conId([ 
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró tu registro como estudiante o personal.',
            ]));
        }

        if ($alumno && ! $alumno->estaActivo()) {
            $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::INACTIVO, "Estudiante inactivo intentó acceder", $fechaHora);

            return back()->with('resultado', $this->conId([ 
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Tu estatus actual no permite el acceso a este evento.',
            ]));
        }

        $columnaIdentificador = $alumno ? 'no_de_control' : 'rfc_personal';
        $identificador = $alumno ? $alumno->no_de_control : $personal->rfc;

        return DB::transaction(function () use ($alumno, $personal, $evento, $tipoMovimiento, $fechaHora, $codigo, $columnaIdentificador, $identificador) {
            
            $ultimoRegistro = RegistroAcceso::where($columnaIdentificador, $identificador)
                ->where('id_evento', $evento->id)
                ->latest('fecha_hora')
                ->lockForUpdate()
                ->first();

            if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds($fechaHora) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) {
                $this->registrarIncidencia($codigo, $evento->id, IncidenciaAcceso::DOBLE_ESCANEO, 'Escaneo duplicado por velocidad.', $fechaHora);

                return back()->with('resultado', $this->conId([ 
                    'estado' => 'error',
                    'titulo' => 'Acceso denegado',
                    'mensaje' => 'Ya se registró tu acceso hace un momento. Espera un instante.',
                ]));
            }

            if ($ultimoRegistro && $ultimoRegistro->tipo_movimiento === $tipoMovimiento) {
                $accionContraria = $tipoMovimiento === 'ENTRADA' ? 'una SALIDA' : 'una ENTRADA';
                
                $this->registrarIncidencia($identificador, $evento->id, IncidenciaAcceso::DOBLE_ESCANEO, "Intento de {$tipoMovimiento} consecutiva.", $fechaHora);

                return back()->with('resultado', $this->conId([ 
                    'estado' => 'error',
                    'titulo' => 'Acceso denegado',
                    'mensaje' => "El alumno ya cuenta con un registro previo de {$tipoMovimiento}. Debes registrar {$accionContraria} primero.",
                ]));
            }

            $registro = RegistroAcceso::create([
                $columnaIdentificador => $identificador,
                'id_evento' => $evento->id,
                'tipo_movimiento' => $tipoMovimiento,
                'fecha_hora' => $fechaHora,
            ]);

            return back()->with('resultado', $this->conId([ 
                'estado' => 'ok',
                'tipoMovimiento' => $tipoMovimiento,
                'alumno' => $alumno ? [
                    'tipo' => 'ALUMNO',
                    'nombre' => $alumno->nombreCompleto(),
                    'identificador' => $alumno->no_de_control,
                    'etiqueta' => 'No. de control',
                    'carrera' => $alumno->nombreCarrera(),
                ] : [
                    'tipo' => 'PERSONAL',
                    'nombre' => $personal->nombreCompleto(),
                    'identificador' => $personal->rfc,
                    'etiqueta' => 'RFC',
                    'carrera' => null,
                ],
                'hora' => $registro->fecha_hora->format('H:i'),
                'mensaje' => $tipoMovimiento === 'ENTRADA'
                    ? '¡Entrada registrada con éxito!'
                    : '¡Salida registrada con éxito!',
            ]));
        });
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
