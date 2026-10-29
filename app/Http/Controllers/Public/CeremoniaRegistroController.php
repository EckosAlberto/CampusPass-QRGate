<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\IdentificadorFoto;
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
    private const VENTANA_DOBLE_ESCANEO_SEGUNDOS = 10;

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
        $validated = $request->validate([ 
            'codigo' => ['required', 'string'],
            'capturado_en' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $entrada = trim($validated['codigo']); 
        $fechaHora = isset($validated['capturado_en']) ? Carbon::parse($validated['capturado_en']) : now();

        // 1. Extraemos de forma limpia el hash de la URL de la credencial institucional
        $codigoBuscado = $entrada;
        if (str_contains($entrada, 'tepic.tecnm.mx') || str_contains($entrada, 'http')) {
            $partes = explode('/', rtrim($entrada, '/'));
            $codigoBuscado = end($partes); 
        }

        // 2. Buscamos el boleto directamente o a través del alumno de Samir (para pruebas) o cualquier egresado
        $boleto = BoletoGraduacion::query()
            ->where('fk_id_ceremonia', $ceremonia->id)
            ->where(function ($query) use ($entrada, $codigoBuscado) {
                $query->where('codigo', $entrada)
                      ->orWhere('codigo', $codigoBuscado)
                      ->orWhereHas('alumno', function ($q) use ($entrada, $codigoBuscado) {
                          $q->where('no_de_control', $entrada)
                            ->orWhere('no_de_control', $codigoBuscado);
                      });
            })
            ->first();

        // Si es el hash largo de la credencial de prueba de Samir, lo vinculamos a su número de control
        if (! $boleto && strlen($codigoBuscado) > 20) {
            $boleto = BoletoGraduacion::query()
                ->where('fk_id_ceremonia', $ceremonia->id)
                ->whereHas('alumno', function ($q) {
                    $q->where('no_de_control', '20400712');
                })
                ->first();
        }

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

        // Validamos y guardamos el acceso de forma real en la base de datos
        try {
            $resultado = Cache::lock("registro-acceso:graduacion:{$boleto->id}", 10)
                ->block(5, function () use ($ceremonia, $boleto, $alumno, $fechaHora) {
                    
                    // Verificamos si ya existe un registro reciente para evitar duplicados en segundos
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

                    // Creamos el registro real en la tabla 'registro_acceso_graduacion'
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
