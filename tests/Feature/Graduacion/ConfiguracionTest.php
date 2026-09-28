<?php

use App\Models\GraduacionUsuario;
use App\Models\PermisoGraduacion;
use App\Models\RolGraduacion;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function crearRolGraduacionConPermiso(string $permisoId = 'configurar-sistema'): RolGraduacion
{
    PermisoGraduacion::query()->firstOrCreate(['id' => $permisoId], ['etiqueta' => 'Permiso de prueba']);

    $rol = RolGraduacion::query()->firstOrCreate(['nombre' => 'Administrador']);
    $rol->permisos()->syncWithoutDetaching([$permisoId]);

    return $rol;
}

/**
 * @return array<string, mixed>
 */
function datosPersonalesDePrueba(array $overrides = []): array
{
    return [
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'García',
        'fecha_nacimiento' => '1990-05-14',
        'curp' => 'PEGJ900514HDFRRN09',
        'rfc' => 'PEGJ900514AB1',
        'telefono' => '6621234567',
        'password' => 'Contrasena-Segura1!',
        'password_confirmation' => 'Contrasena-Segura1!',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('graduacion.configuracion'));

    $response->assertRedirect(route('graduacion.login'));
});

test('a user without the configurar-sistema permission is forbidden', function () {
    $rolSinPermiso = RolGraduacion::query()->create(['nombre' => 'Docente']);
    $usuario = GraduacionUsuario::factory()->create(['rol_id' => $rolSinPermiso->id]);

    $response = $this->actingAs($usuario, 'graduacion')->get(route('graduacion.configuracion'));

    $response->assertForbidden();
});

test('an administrador can render the configuracion screen', function () {
    $rol = crearRolGraduacionConPermiso();
    $usuario = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);

    $response = $this->actingAs($usuario, 'graduacion')->get(route('graduacion.configuracion'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('graduacion/configuracion')
        ->has('usuarios')
        ->has('roles')
        ->has('permisos')
    );
});

test('an admin can create a user with a role from the closed catalog', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    RolGraduacion::query()->firstOrCreate(['nombre' => 'Docente']);

    $response = $this->actingAs($admin, 'graduacion')->post(route('graduacion.configuracion.usuarios.store'), [
        'name' => 'Nueva Docente',
        'email' => 'docente@campuspass.test',
        'rol' => 'Docente',
        ...datosPersonalesDePrueba(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('usuarios_graduacion', [
        'email' => 'docente@campuspass.test',
        'name' => 'Nueva Docente Pérez García',
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'García',
        'activo' => true,
        'curp' => 'PEGJ900514HDFRRN09',
        'rfc' => 'PEGJ900514AB1',
        'telefono' => '6621234567',
    ]);

    $usuario = GraduacionUsuario::where('email', 'docente@campuspass.test')->firstOrFail();
    expect(Hash::check('Contrasena-Segura1!', $usuario->password))->toBeTrue();
});

test('creating a user with a role outside the closed catalog is rejected', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);

    $response = $this->actingAs($admin, 'graduacion')->post(route('graduacion.configuracion.usuarios.store'), [
        'name' => 'Auxiliar Nueva',
        'email' => 'auxiliar@campuspass.test',
        'rol' => 'Auxiliar de Graduación',
        ...datosPersonalesDePrueba(),
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertDatabaseMissing('usuarios_graduacion', ['email' => 'auxiliar@campuspass.test']);
});

test('an admin can edit a users name, apellidos, email and datos personales', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    RolGraduacion::query()->firstOrCreate(['nombre' => 'Docente']);
    $otro = GraduacionUsuario::factory()->create(['rol_id' => $rol->id, 'email' => 'otro@campuspass.test']);

    $response = $this->actingAs($admin, 'graduacion')->patch(route('graduacion.configuracion.usuarios.update', $otro), [
        'name' => 'Juana',
        'apellido_paterno' => 'Ramírez',
        'apellido_materno' => 'Solís',
        'email' => 'juana.ramirez@campuspass.test',
        'rol' => 'Docente',
        'fecha_nacimiento' => '1988-03-02',
        'curp' => 'RASJ880302MDFMLL02',
        'rfc' => 'RASJ880302XY2',
        'telefono' => '6629876543',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $otro->refresh();
    expect($otro->name)->toBe('Juana Ramírez Solís');
    expect($otro->email)->toBe('juana.ramirez@campuspass.test');
    expect($otro->curp)->toBe('RASJ880302MDFMLL02');
    // Editar no toca la contraseña.
    expect(Hash::check('password', $otro->password))->toBeTrue();
});

test('editing a user with an email already taken by another user is rejected', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    GraduacionUsuario::factory()->create(['rol_id' => $rol->id, 'email' => 'ocupado@campuspass.test']);
    $otro = GraduacionUsuario::factory()->create(['rol_id' => $rol->id, 'email' => 'libre@campuspass.test']);

    $response = $this->actingAs($admin, 'graduacion')->patch(route('graduacion.configuracion.usuarios.update', $otro), [
        'email' => 'ocupado@campuspass.test',
    ]);

    $response->assertSessionHasErrors('email');
    expect($otro->fresh()->email)->toBe('libre@campuspass.test');
});

test('deactivating a user prevents them from logging in', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    $otro = GraduacionUsuario::factory()->create(['rol_id' => $rol->id, 'email' => 'otro@campuspass.test']);

    $this->actingAs($admin, 'graduacion')->patch(route('graduacion.configuracion.usuarios.update', $otro), [
        'activo' => false,
    ])->assertRedirect();

    expect($otro->fresh()->activo)->toBeFalse();

    $this->post(route('graduacion.logout'));

    $this->post(route('graduacion.login.store'), [
        'email' => 'otro@campuspass.test',
        'password' => 'password',
    ]);

    $this->assertGuest('graduacion');
});

test('a user cannot deactivate their own account', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);

    $response = $this->actingAs($admin, 'graduacion')->patch(route('graduacion.configuracion.usuarios.update', $admin), [
        'activo' => false,
    ]);

    $response->assertSessionHasErrors('activo');
    expect($admin->fresh()->activo)->toBeTrue();
});

test('a user cannot delete their own account', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);

    $response = $this->actingAs($admin, 'graduacion')->delete(route('graduacion.configuracion.usuarios.destroy', $admin));

    $response->assertForbidden();
    $this->assertDatabaseHas('usuarios_graduacion', ['id' => $admin->id]);
});

test('permission matrix updates persist', function () {
    $rol = crearRolGraduacionConPermiso();
    $admin = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    PermisoGraduacion::query()->firstOrCreate(['id' => 'ver-estadisticas'], ['etiqueta' => 'Ver estadísticas']);

    $this->actingAs($admin, 'graduacion')->patch(route('graduacion.configuracion.permisos'), [
        'matriz' => [
            'ver-estadisticas' => [$rol->id => true],
        ],
    ])->assertRedirect();

    expect($rol->permisos()->where('permisos_graduacion.id', 'ver-estadisticas')->exists())->toBeTrue();
});
