<?php

namespace App\Http\Controllers\Panel;

use App\Concerns\DatosPersonalesValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\ConfiguracionBiblioteca;
use App\Models\ExcepcionHorarioBiblioteca;
use App\Models\HorarioBiblioteca;
use App\Models\NotificacionEventoBiblioteca;
use App\Models\PermisoBiblioteca;
use App\Models\TipoUsuario;
use App\Models\User;
use App\Support\RolesInstitucionales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ConfiguracionController extends Controller
{
    use DatosPersonalesValidationRules;
    use PasswordValidationRules;

    private const LINEAS_REGISTRO = 200;

    /** Pantalla de Configuración con sus pestañas (general, usuarios, permisos, horarios, notificaciones, mantenimiento). */
    public function index(Request $request): Response
    {
        $this->verificarPermiso($request);

        $ajustes = ConfiguracionBiblioteca::actual();

        return Inertia::render('panel/configuracion', [
            'ajustes' => $this->serializarAjustes($ajustes),
            'usuarios' => User::query()->orderBy('name')->get()
                ->map(fn (User $usuario) => $this->serializarUsuario($usuario))->all(),
            'roles' => $this->serializarRoles(),
            'permisos' => $this->serializarMatrizPermisos(),
            'horarios' => HorarioBiblioteca::query()->get()
                ->sortBy(fn (HorarioBiblioteca $h) => array_search($h->dia, HorarioBiblioteca::DIAS))
                ->values()
                ->map(fn (HorarioBiblioteca $h) => [
                    'dia' => $h->dia,
                    'abierto' => $h->abierto,
                    // La columna es TIME (MySQL la devuelve como "H:i:s"):
                    // se recorta a "H:i" para que coincida con el value del
                    // <input type="time"> y con la validación al guardar.
                    'apertura' => substr((string) $h->apertura, 0, 5),
                    'cierre' => substr((string) $h->cierre, 0, 5),
                ])->all(),
            'excepciones' => ExcepcionHorarioBiblioteca::query()->orderBy('fecha')->get()
                ->map(fn (ExcepcionHorarioBiblioteca $e) => [
                    'id' => $e->id,
                    'fecha' => $e->fecha->format('Y-m-d'),
                    'fechaLabel' => $e->fecha->translatedFormat('j \d\e F'),
                    'motivo' => $e->motivo,
                    'detalle' => $e->detalle(),
                ])->all(),
            'notificacionesEventos' => NotificacionEventoBiblioteca::query()->get()
                ->map(fn (NotificacionEventoBiblioteca $n) => [
                    'evento' => $n->evento,
                    'correo' => $n->correo,
                    'push' => $n->push,
                ])->all(),
            'canalCorreoDisponible' => ! in_array(config('mail.default'), ['log', 'array', null], true),
        ]);
    }

    /** Guarda los ajustes generales del sistema (pestaña General). */
    public function actualizarAjustes(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'nombre_sistema' => ['sometimes', 'string', 'max:100'],
            'institucion' => ['sometimes', 'string', 'max:150'],
            'nombre_biblioteca' => ['sometimes', 'string', 'max:100'],
            'capacidad_maxima' => ['sometimes', 'integer', 'min:0'],
            'horario_apertura' => ['sometimes', 'date_format:H:i'],
            'horario_cierre' => ['sometimes', 'date_format:H:i', 'after:horario_apertura'],
            'qr_activo' => ['sometimes', 'boolean'],
            'qr_validez_minutos' => ['sometimes', 'integer', 'min:1'],
            'qr_permitir_reingreso' => ['sometimes', 'boolean'],
            'qr_notificar_correo' => ['sometimes', 'boolean'],
            'notif_incidencias' => ['sometimes', 'boolean'],
            'notif_reportes' => ['sometimes', 'boolean'],
            'notif_resumen_diario' => ['sometimes', 'boolean'],
            'notif_correo' => ['sometimes', 'boolean'],
            'sesion_inactividad_minutos' => ['sometimes', 'integer', 'min:1'],
            'sesion_cerrar_auto' => ['sometimes', 'boolean'],
            'sesion_mantener_activa' => ['sometimes', 'boolean'],
            'zona_horaria' => ['sometimes', 'string', 'max:60'],
            'idioma' => ['sometimes', 'in:es,en'],
            'formato_fecha' => ['sometimes', 'in:DD/MM/AAAA,MM/DD/AAAA'],
            'nivel_registro' => ['sometimes', 'in:error,warning,info,debug'],
            'modo_mantenimiento' => ['sometimes', 'boolean'],
            'canal_correo_activo' => ['sometimes', 'boolean'],
            'canal_push_activo' => ['sometimes', 'boolean'],
        ]);

        ConfiguracionBiblioteca::actual()->update($datos);

        return back()->with('success', 'Configuración guardada.');
    }

    /** Botón "+ Nuevo usuario": da de alta a un miembro del personal de Biblioteca. */
    public function storeUsuario(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'tipo' => ['required', Rule::in(RolesInstitucionales::nombres())],
            'password' => $this->passwordRules(),
            ...$this->datosPersonalesRules(),
        ]);

        $datos = $this->normalizarDatosPersonales($datos);
        $rol = TipoUsuario::porNombre($datos['tipo']);

        User::create([
            'name' => trim("{$datos['name']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
            'apellido_paterno' => $datos['apellido_paterno'],
            'apellido_materno' => $datos['apellido_materno'],
            'email' => $datos['email'],
            'tipo' => $rol->tipo_usuario,
            'password' => Hash::make($datos['password']),
            'activo' => true,
            'email_verified_at' => now(),
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'curp' => $datos['curp'],
            'rfc' => $datos['rfc'],
            'telefono' => $datos['telefono'],
        ]);

        return back()->with('success', 'Usuario creado.');
    }

    
    public function updateUsuario(Request $request, User $usuario): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'apellido_paterno' => ['sometimes', 'string', 'max:100'],
            'apellido_materno' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'tipo' => ['sometimes', Rule::in(RolesInstitucionales::nombres())],
            'activo' => ['sometimes', 'boolean'],
            ...$this->datosPersonalesRules(requerido: false),
        ]);

        if (array_key_exists('activo', $datos) && ! $datos['activo'] && $usuario->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        $datos = $this->normalizarDatosPersonales($datos);

        if (array_key_exists('tipo', $datos)) {
            $datos['tipo'] = TipoUsuario::porNombre($datos['tipo'])->tipo_usuario;
        }

        
        if (array_key_exists('name', $datos) || array_key_exists('apellido_paterno', $datos) || array_key_exists('apellido_materno', $datos)) {
            $nombrePila = $datos['name'] ?? $this->nombrePila($usuario);
            $apellidoPaterno = $datos['apellido_paterno'] ?? $usuario->apellido_paterno;
            $apellidoMaterno = $datos['apellido_materno'] ?? $usuario->apellido_materno;
            $datos['name'] = trim("{$nombrePila} {$apellidoPaterno} {$apellidoMaterno}");
        }

        $usuario->update($datos);

        return back()->with('success', 'Usuario actualizado.');
    }

    /** Elimina un usuario del panel (biblioteca) */
    public function destroyUsuario(Request $request, User $usuario): RedirectResponse
    {
        $this->verificarPermiso($request);

        if ($usuario->id === $request->user()->id) {
            abort(403, 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    
    public function actualizarPermisos(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'matriz' => ['required', 'array'],
        ]);

        $idsValidos = PermisoBiblioteca::query()->pluck('id');
        $rolesValidos = TipoUsuario::query()->pluck('tipo_usuario');

        foreach ($datos['matriz'] as $permisoId => $porRol) {
            if (! $idsValidos->contains($permisoId) || ! is_array($porRol)) {
                continue;
            }

            foreach ($porRol as $rolCodigo => $concedido) {
                if (! $rolesValidos->contains($rolCodigo)) {
                    continue;
                }

                if ($concedido) {
                    DB::table('tipo_usuario_permiso')->updateOrInsert([
                        'tipo_usuario' => $rolCodigo,
                        'permiso_id' => $permisoId,
                    ]);
                } else {
                    DB::table('tipo_usuario_permiso')
                        ->where('tipo_usuario', $rolCodigo)
                        ->where('permiso_id', $permisoId)
                        ->delete();
                }
            }
        }

        return back()->with('success', 'Permisos actualizados.');
    }

    /** Guarda el horario normal de apertura/cierre por día de la semana. */
    public function actualizarHorarioSemana(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'dias' => ['required', 'array'],
            'dias.*.dia' => ['required', Rule::in(HorarioBiblioteca::DIAS)],
            'dias.*.abierto' => ['required', 'boolean'],
            'dias.*.apertura' => ['required', 'date_format:H:i'],
            'dias.*.cierre' => ['required', 'date_format:H:i', 'after:dias.*.apertura'],
        ]);

        foreach ($datos['dias'] as $dia) {
            HorarioBiblioteca::query()->where('dia', $dia['dia'])->update([
                'abierto' => $dia['abierto'],
                'apertura' => $dia['apertura'],
                'cierre' => $dia['cierre'],
            ]);
        }

        return back()->with('success', 'Horario de atención guardado.');
    }

    /** Agrega un día especial (feriado, vacaciones) con horario reducido o cerrado. */
    public function storeExcepcionHorario(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:150'],
            'cerrado_todo_dia' => ['required', 'boolean'],
            'horario_apertura' => ['required_if:cerrado_todo_dia,false', 'nullable', 'date_format:H:i'],
            'horario_cierre' => ['required_if:cerrado_todo_dia,false', 'nullable', 'date_format:H:i'],
        ]);

        ExcepcionHorarioBiblioteca::create($datos);

        return back()->with('success', 'Excepción de horario agregada.');
    }

    /** Elimina un día especial de horario. */
    public function destroyExcepcionHorario(Request $request, ExcepcionHorarioBiblioteca $excepcion): RedirectResponse
    {
        $this->verificarPermiso($request);

        $excepcion->delete();

        return back()->with('success', 'Excepción eliminada.');
    }

    
    public function actualizarNotificacionesEventos(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'eventos' => ['required', 'array'],
            'eventos.*.correo' => ['required', 'boolean'],
            'eventos.*.push' => ['required', 'boolean'],
        ]);

        foreach ($datos['eventos'] as $evento => $valores) {
            NotificacionEventoBiblioteca::query()->where('evento', $evento)->update($valores);
        }

        return back()->with('success', 'Preferencias de notificaciones guardadas.');
    }

    /** Botón "Limpiar caché del sistema": corre cache:clear y config:clear de verdad. */
    public function limpiarCache(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');

        return back()->with('success', 'Caché del sistema limpiada.');
    }

    /** Botón "Ver registros del sistema": devuelve las últimas líneas del log real de Laravel. */
    public function registrosDelSistema(Request $request): JsonResponse
    {
        $this->verificarPermiso($request);

        $ruta = storage_path('logs/laravel.log');

        if (! File::exists($ruta)) {
            return response()->json(['lineas' => []]);
        }

        $lineas = collect(preg_split('/\r\n|\r|\n/', File::get($ruta)))
            ->filter()
            ->take(-self::LINEAS_REGISTRO)
            ->values();

        return response()->json(['lineas' => $lineas]);
    }

    
    public function respaldarBaseDeDatos(Request $request): StreamedResponse|RedirectResponse
    {
        $this->verificarPermiso($request);

        if (DB::connection()->getDriverName() !== 'mysql') {
            return back()->with('error', 'El respaldo solo está disponible con una base de datos MySQL.');
        }

        $nombreArchivo = 'respaldo-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () {
            $baseDeDatos = DB::connection()->getDatabaseName();
            $tablas = collect(DB::select('SHOW TABLES'))
                ->map(fn ($fila) => (array) $fila)
                ->map(fn (array $fila) => array_values($fila)[0]);

            echo "-- Respaldo de {$baseDeDatos} generado el ".now()->toDateTimeString()." por CampusPass\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tablas as $tabla) {
                $crear = DB::selectOne("SHOW CREATE TABLE `{$tabla}`");
                $sentenciaCreacion = $crear?->{'Create Table'};

                echo "-- Tabla: {$tabla}\n";
                echo "DROP TABLE IF EXISTS `{$tabla}`;\n";
                echo $sentenciaCreacion.";\n\n";

                foreach (DB::table($tabla)->get() as $fila) {
                    $valores = collect((array) $fila)->map(fn ($valor) => match (true) {
                        $valor === null => 'NULL',
                        is_int($valor) || is_float($valor) => (string) $valor,
                        default => DB::connection()->getPdo()->quote((string) $valor),
                    })->implode(', ');

                    echo "INSERT INTO `{$tabla}` VALUES ({$valores});\n";
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $nombreArchivo, ['Content-Type' => 'application/sql']);
    }

    private function verificarPermiso(Request $request): void
    {
        abort_unless($request->user()->tienePermiso('configurar-sistema'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarAjustes(ConfiguracionBiblioteca $ajustes): array
    {
        return [
            'nombreSistema' => $ajustes->nombre_sistema,
            'institucion' => $ajustes->institucion,
            'nombreBiblioteca' => $ajustes->nombre_biblioteca,
            'capacidadMaxima' => $ajustes->capacidad_maxima,
            'horarioApertura' => substr((string) $ajustes->horario_apertura, 0, 5),
            'horarioCierre' => substr((string) $ajustes->horario_cierre, 0, 5),
            'qrActivo' => $ajustes->qr_activo,
            'qrValidezMinutos' => $ajustes->qr_validez_minutos,
            'qrPermitirReingreso' => $ajustes->qr_permitir_reingreso,
            'qrNotificarCorreo' => $ajustes->qr_notificar_correo,
            'notifIncidencias' => $ajustes->notif_incidencias,
            'notifReportes' => $ajustes->notif_reportes,
            'notifResumenDiario' => $ajustes->notif_resumen_diario,
            'notifCorreo' => $ajustes->notif_correo,
            'sesionInactividadMinutos' => $ajustes->sesion_inactividad_minutos,
            'sesionCerrarAuto' => $ajustes->sesion_cerrar_auto,
            'sesionMantenerActiva' => $ajustes->sesion_mantener_activa,
            'zonaHoraria' => $ajustes->zona_horaria,
            'idioma' => $ajustes->idioma,
            'formatoFecha' => $ajustes->formato_fecha,
            'nivelRegistro' => $ajustes->nivel_registro,
            'modoMantenimiento' => $ajustes->modo_mantenimiento,
            'canalCorreoActivo' => $ajustes->canal_correo_activo,
            'canalPushActivo' => $ajustes->canal_push_activo,
            'version' => '1.0.0',
            'baseDeDatosConectada' => $this->baseDeDatosConectada(),
            'ultimaActualizacion' => $ajustes->updated_at?->format('d/m/y, h:i A'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->name,
            'nombrePila' => $this->nombrePila($usuario),
            'apellidoPaterno' => $usuario->apellido_paterno,
            'apellidoMaterno' => $usuario->apellido_materno,
            'correo' => $usuario->email,
            'rol' => $usuario->tipoUsuario?->descripcion_tipo,
            'activo' => $usuario->activo,
            'fechaNacimiento' => $usuario->fecha_nacimiento?->format('Y-m-d'),
            'curp' => $usuario->curp,
            'rfc' => $usuario->rfc,
            'telefono' => $usuario->telefono,
        ];
    }

    
    private function nombrePila(User $usuario): string
    {
        $sufijo = trim("{$usuario->apellido_paterno} {$usuario->apellido_materno}");

        if ($sufijo !== '' && str_ends_with($usuario->name, $sufijo)) {
            return trim(substr($usuario->name, 0, -strlen($sufijo)));
        }

        return $usuario->name;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializarRoles(): array
    {
        return TipoUsuario::query()->withCount('permisos')->orderBy('descripcion_tipo')->get()
            ->map(fn (TipoUsuario $tipo) => [
                'clave' => $tipo->tipo_usuario,
                'nombre' => $tipo->descripcion_tipo,
                'permisos' => $tipo->permisos_count,
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializarMatrizPermisos(): array
    {
        $roles = TipoUsuario::query()->pluck('tipo_usuario');
        $concedidos = DB::table('tipo_usuario_permiso')->get()
            ->groupBy('permiso_id')
            ->map(fn ($filas) => $filas->pluck('tipo_usuario')->all());

        return PermisoBiblioteca::query()->get()->map(fn (PermisoBiblioteca $permiso) => [
            'id' => $permiso->id,
            'etiqueta' => $permiso->etiqueta,
            'roles' => $roles->mapWithKeys(fn (string $rol) => [
                $rol => in_array($rol, $concedidos->get($permiso->id, []), true),
            ])->all(),
        ])->all();
    }

    private function baseDeDatosConectada(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
