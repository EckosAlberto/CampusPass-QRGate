<?php

namespace App\Http\Controllers\Graduacion;

use App\Concerns\DatosPersonalesValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\GraduacionUsuario;
use App\Models\PermisoGraduacion;
use App\Models\RolGraduacion;
use App\Support\RolesInstitucionales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    use DatosPersonalesValidationRules;
    use PasswordValidationRules;

    /** Pantalla de Configuración: usuarios del equipo, roles y su matriz de permisos. */
    public function index(Request $request): Response
    {
        $this->verificarPermiso($request);

        return Inertia::render('graduacion/configuracion', [
            'usuarios' => GraduacionUsuario::query()->with('rol')->orderBy('name')->get()
                ->map(fn (GraduacionUsuario $usuario) => $this->serializarUsuario($usuario))->all(),
            'roles' => $this->serializarRoles(),
            'permisos' => $this->serializarMatrizPermisos(),
        ]);
    }

    /** Botón "+ Nuevo usuario": da de alta a un miembro del equipo de Graduación. */
    public function storeUsuario(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios_graduacion,email'],
            'rol' => ['required', Rule::in(RolesInstitucionales::nombres())],
            'password' => $this->passwordRules(),
            ...$this->datosPersonalesRules(),
        ]);

        $datos = $this->normalizarDatosPersonales($datos);
        $rol = RolGraduacion::porNombre($datos['rol']);

        GraduacionUsuario::create([
            'name' => trim("{$datos['name']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
            'apellido_paterno' => $datos['apellido_paterno'],
            'apellido_materno' => $datos['apellido_materno'],
            'email' => $datos['email'],
            'rol_id' => $rol->id,
            'password' => Hash::make($datos['password']),
            'activo' => true,
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'curp' => $datos['curp'],
            'rfc' => $datos['rfc'],
            'telefono' => $datos['telefono'],
        ]);

        return back()->with('success', 'Usuario creado.');
    }

    
    public function updateUsuario(Request $request, GraduacionUsuario $usuario): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'apellido_paterno' => ['sometimes', 'string', 'max:100'],
            'apellido_materno' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('usuarios_graduacion', 'email')->ignore($usuario->id)],
            'rol' => ['sometimes', Rule::in(RolesInstitucionales::nombres())],
            'activo' => ['sometimes', 'boolean'],
            ...$this->datosPersonalesRules(requerido: false),
        ]);

        if (array_key_exists('activo', $datos) && ! $datos['activo'] && $usuario->id === $request->user('graduacion')->id) {
            throw ValidationException::withMessages([
                'activo' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        $datos = $this->normalizarDatosPersonales($datos);

        if (array_key_exists('rol', $datos)) {
            $datos['rol_id'] = RolGraduacion::porNombre($datos['rol'])->id;
            unset($datos['rol']);
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

    /** Elimina a un usuario del equipo */
    public function destroyUsuario(Request $request, GraduacionUsuario $usuario): RedirectResponse
    {
        $this->verificarPermiso($request);

        if ($usuario->id === $request->user('graduacion')->id) {
            abort(403, 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    /** Guarda la tabla de permisos por rol. */
    public function actualizarPermisos(Request $request): RedirectResponse
    {
        $this->verificarPermiso($request);

        $datos = $request->validate([
            'matriz' => ['required', 'array'],
        ]);

        $idsValidos = PermisoGraduacion::query()->pluck('id');

        foreach ($datos['matriz'] as $permisoId => $porRol) {
            if (! $idsValidos->contains($permisoId) || ! is_array($porRol)) {
                continue;
            }

            foreach ($porRol as $rolId => $concedido) {
                $rol = RolGraduacion::find($rolId);

                if (! $rol) {
                    continue;
                }

                if ($concedido) {
                    $rol->permisos()->syncWithoutDetaching([$permisoId]);
                } else {
                    $rol->permisos()->detach($permisoId);
                }
            }
        }

        return back()->with('success', 'Permisos actualizados.');
    }

    private function verificarPermiso(Request $request): void
    {
        abort_unless($request->user('graduacion')->tienePermiso('configurar-sistema'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarUsuario(GraduacionUsuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre' => $usuario->name,
            'nombrePila' => $this->nombrePila($usuario),
            'apellidoPaterno' => $usuario->apellido_paterno,
            'apellidoMaterno' => $usuario->apellido_materno,
            'correo' => $usuario->email,
            'rol' => $usuario->rol?->nombre,
            'activo' => $usuario->activo,
            'fechaNacimiento' => $usuario->fecha_nacimiento?->format('Y-m-d'),
            'curp' => $usuario->curp,
            'rfc' => $usuario->rfc,
            'telefono' => $usuario->telefono,
        ];
    }

    
    private function nombrePila(GraduacionUsuario $usuario): string
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
        return RolGraduacion::query()->withCount('permisos')->orderBy('nombre')->get()
            ->map(fn (RolGraduacion $rol) => [
                'clave' => $rol->id,
                'nombre' => $rol->nombre,
                'permisos' => $rol->permisos_count,
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializarMatrizPermisos(): array
    {
        $roles = RolGraduacion::query()->with('permisos')->get();

        return PermisoGraduacion::query()->get()->map(fn (PermisoGraduacion $permiso) => [
            'id' => $permiso->id,
            'etiqueta' => $permiso->etiqueta,
            'roles' => $roles->mapWithKeys(fn (RolGraduacion $rol) => [
                $rol->id => $rol->permisos->contains('id', $permiso->id),
            ])->all(),
        ])->all();
    }
}
