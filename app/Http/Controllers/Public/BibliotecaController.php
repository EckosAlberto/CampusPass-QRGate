<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\IncidenciaAcceso;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BibliotecaController extends Controller
{
    // Ventana de tiempo en segundos para considerar un escaneo como duplicado.
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 5;

    /**
     * Muestra la página de registro de acceso a la biblioteca.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('biblioteca/registro', [
            'resultado' => $request->session()->get('resultado'),
        ]);
    }

    /**
     * Procesa el escaneo de un código QR y registra la entrada o salida del
     * usuario en la biblioteca.
     */
    public function store(Request $request): RedirectResponse 
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string'],
        ]);

        $codigo = trim($validated['codigo']);

        $ubicacion = Ubicacion::firstOrCreate(
            ['nombre' => 'Biblioteca'],
            ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true]
        );

        // ¿QR/código válido? Puede ser el número de control de un alumno
        // (hasta 10 caracteres) o el RFC de personal (13 caracteres).
        if (! preg_match('/^[A-Za-z0-9]{1,13}$/', $codigo)) {
            $this->registrarIncidencia($codigo, $ubicacion->id, IncidenciaAcceso::QR_INVALIDO, "Código escaneado con formato inválido: \"{$codigo}\"");

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Código incorrecto',
                'mensaje' => 'El código QR escaneado no es válido. Intenta nuevamente.',
            ]));
        }

        // ¿Usuario registrado? Primero se busca como alumno, luego como personal.
        $alumno = Alumno::find($codigo);
        $personal = $alumno ? null : Personal::find(strtoupper($codigo));

        if (! $alumno && ! $personal) {
            $this->registrarIncidencia($codigo, $ubicacion->id, IncidenciaAcceso::NO_REGISTRADO, "Código no encontrado: {$codigo}");

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'No se encontró tu registro como estudiante o personal.',
            ]));
        }

        // ¿Alumno activo? Solo se permite el acceso a alumnos con estatus activo.
        if ($alumno && ! $alumno->estaActivo()) {
            $this->registrarIncidencia($codigo, $ubicacion->id, IncidenciaAcceso::INACTIVO, "Estudiante con estatus '{$alumno->estatus_alumno}' intentó acceder");

            return back()->with('resultado', $this->conId([
                'estado' => 'error',
                'titulo' => 'Acceso denegado',
                'mensaje' => 'Tu estatus actual no permite el acceso a la biblioteca.',
            ]));
        }

        $columnaIdentificador = $alumno ? 'no_de_control' : 'rfc_personal';
        $identificador = $alumno ? $alumno->no_de_control : $personal->rfc;

        
        try {
            $resultado = Cache::lock("registro-acceso:biblioteca:{$identificador}", 10)
                ->block(5, function () use ($columnaIdentificador, $identificador, $ubicacion, $codigo, $alumno, $personal) {
                    $ultimoRegistro = RegistroAcceso::where($columnaIdentificador, $identificador)
                        ->where('id_ubicacion', $ubicacion->id)
                        ->whereDate('fecha_hora', today())
                        ->latest('fecha_hora')
                        ->first();

                    // Doble escaneo / muy rápido.
                    if ($ultimoRegistro && $ultimoRegistro->fecha_hora->diffInSeconds(now()) < self::VENTANA_DOBLE_ESCANEO_SEGUNDOS) {
                        $this->registrarIncidencia($codigo, $ubicacion->id, IncidenciaAcceso::DOBLE_ESCANEO, 'Escaneo duplicado en menos de '.self::VENTANA_DOBLE_ESCANEO_SEGUNDOS.' segundos');

                        return [
                            'estado' => 'error',
                            'titulo' => 'Acceso denegado',
                            'mensaje' => 'Ya se registró tu acceso. Espera un momento antes de volver a escanear.',
                        ];
                    }

                    // Determina Entrada/Salida según el último movimiento del día.
                    $tipoMovimiento = $ultimoRegistro?->tipo_movimiento === 'ENTRADA' ? 'SALIDA' : 'ENTRADA';

                    $registro = RegistroAcceso::create([
                        $columnaIdentificador => $identificador,
                        'id_ubicacion' => $ubicacion->id,
                        'tipo_movimiento' => $tipoMovimiento,
                        'fecha_hora' => now(),
                    ]);

                    return [
                        'estado' => 'ok',
                        'tipoMovimiento' => $tipoMovimiento,
                        'persona' => $alumno ? [
                            'tipo' => 'ALUMNO',
                            'nombre' => $alumno->nombreCompleto(),
                            'identificador' => $alumno->no_de_control,
                            'detalle' => $alumno->nombreCarrera(),
                        ] : [
                            'tipo' => 'PERSONAL',
                            'nombre' => $personal->nombreCompleto(),
                            'identificador' => $personal->rfc,
                            'detalle' => null,
                        ],
                        'hora' => $registro->fecha_hora->format('H:i'),
                        'mensaje' => $tipoMovimiento === 'ENTRADA'
                            ? '¡Bienvenido al Centro de Información! No olvides registrar tu salida antes de retirarte.'
                            : '¡Gracias por visitarnos! Te esperamos nuevamente.',
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

    // Registra una incidencia de acceso en la base de datos.
    private function registrarIncidencia(?string $codigo, ?int $idUbicacion, string $tipo, string $mensaje): void
    {
        IncidenciaAcceso::create([
            
            'no_de_control' => $codigo !== null ? substr($codigo, 0, 10) : null,
            'id_ubicacion' => $idUbicacion,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'fecha_hora' => now(),
        ]);
    }

    
    private function conId(array $datos): array
    {
        return ['id' => (string) Str::uuid(), ...$datos];
    }
}
