<?php

use App\Models\ConfiguracionBiblioteca;
use App\Models\ExcepcionHorarioBiblioteca;
use App\Models\HorarioBiblioteca;
use App\Models\NotificacionEventoBiblioteca;
use App\Models\PermisoBiblioteca;
use App\Models\TipoUsuario;
use App\Models\User;
use App\Support\RolesInstitucionales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

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

function sembrarCatalogosConfiguracion(): void
{
    crearTiposUsuarioBiblioteca();

    PermisoBiblioteca::query()->firstOrCreate(['id' => 'configurar-sistema'], ['etiqueta' => 'Configurar el sistema']);
    PermisoBiblioteca::query()->firstOrCreate(['id' => 'ver-accesos'], ['etiqueta' => 'Ver registros de acceso']);

    foreach (['configurar-sistema', 'ver-accesos'] as $permisoId) {
        DB::table('tipo_usuario_permiso')->updateOrInsert([
            'tipo_usuario' => TipoUsuario::ADMINISTRADOR,
            'permiso_id' => $permisoId,
        ]);
    }

    foreach (HorarioBiblioteca::DIAS as $dia) {
        HorarioBiblioteca::query()->firstOrCreate(
            ['dia' => $dia],
            ['abierto' => $dia !== 'Domingo', 'apertura' => '08:00', 'cierre' => '19:00'],
        );
    }

    foreach ([
        NotificacionEventoBiblioteca::INCIDENCIA,
        NotificacionEventoBiblioteca::REPORTE,
        NotificacionEventoBiblioteca::ACCESO_DENEGADO,
        NotificacionEventoBiblioteca::CAPACIDAD_MAXIMA,
        NotificacionEventoBiblioteca::RESUMEN_DIARIO,
    ] as $evento) {
        NotificacionEventoBiblioteca::query()->firstOrCreate(['evento' => $evento]);
    }
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('configuracion'));

    $response->assertRedirect(route('login'));
});

test('a user without the configurar-sistema permission is forbidden', function () {
    sembrarCatalogosConfiguracion();
    $usuario = crearUsuarioBiblioteca(RolesInstitucionales::DOCENTE);

    $response = $this->actingAs($usuario)->get(route('configuracion'));

    $response->assertForbidden();
});

test('an administrador can render the configuracion screen with real data', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->get(route('configuracion'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/configuracion')
        ->has('ajustes')
        ->has('usuarios')
        ->has('roles', 6)
        ->has('permisos')
        ->has('horarios', 7)
        ->has('notificacionesEventos', 5)
    );
});

test('saving ajustes persists to the database', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->patch(route('configuracion.ajustes'), [
        'nombre_biblioteca' => 'Biblioteca de Prueba',
        'capacidad_maxima' => 250,
    ]);

    $response->assertRedirect();
    expect(ConfiguracionBiblioteca::actual()->nombre_biblioteca)->toBe('Biblioteca de Prueba');
    expect(ConfiguracionBiblioteca::actual()->capacidad_maxima)->toBe(250);
});

test('an admin can create a user with a role', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->post(route('configuracion.usuarios.store'), [
        'name' => 'Nuevo Docente',
        'email' => 'nuevo@campuspass.test',
        'tipo' => 'Docente',
        ...datosPersonalesDePrueba(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'email' => 'nuevo@campuspass.test',
        'name' => 'Nuevo Docente Pérez García',
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'García',
        'tipo' => RolesInstitucionales::DOCENTE,
        'activo' => true,
        'curp' => 'PEGJ900514HDFRRN09',
        'rfc' => 'PEGJ900514AB1',
        'telefono' => '6621234567',
    ]);

    $usuario = User::where('email', 'nuevo@campuspass.test')->firstOrFail();
    expect(Hash::check('Contrasena-Segura1!', $usuario->password))->toBeTrue();
});

test('creating a user with a role outside the closed catalog is rejected', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->post(route('configuracion.usuarios.store'), [
        'name' => 'Encargado de Turno',
        'email' => 'turno@campuspass.test',
        'tipo' => 'Encargado de Turno Nocturno',
        ...datosPersonalesDePrueba(),
    ]);

    $response->assertSessionHasErrors('tipo');
    $this->assertDatabaseMissing('users', ['email' => 'turno@campuspass.test']);
});

test('an admin can edit a users name, apellidos, email and datos personales', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();
    $docente = crearUsuarioBiblioteca(RolesInstitucionales::DOCENTE, ['email' => 'docente@campuspass.test']);

    $response = $this->actingAs($admin)->patch(route('configuracion.usuarios.update', $docente), [
        'name' => 'Juana',
        'apellido_paterno' => 'Ramírez',
        'apellido_materno' => 'Solís',
        'email' => 'juana.ramirez@campuspass.test',
        'tipo' => 'Recursos humanos',
        'fecha_nacimiento' => '1988-03-02',
        'curp' => 'RASJ880302MDFMLL02',
        'rfc' => 'RASJ880302XY2',
        'telefono' => '6629876543',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $docente->refresh();
    expect($docente->name)->toBe('Juana Ramírez Solís');
    expect($docente->apellido_paterno)->toBe('Ramírez');
    expect($docente->email)->toBe('juana.ramirez@campuspass.test');
    expect($docente->tipo)->toBe(RolesInstitucionales::RECURSOS_HUMANOS);
    expect($docente->curp)->toBe('RASJ880302MDFMLL02');
    expect(Hash::check('password', $docente->password))->toBeTrue();
});

test('editing a user with an email already taken by another user is rejected', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();
    crearUsuarioBiblioteca(RolesInstitucionales::DOCENTE, ['email' => 'ocupado@campuspass.test']);
    $otro = crearUsuarioBiblioteca(RolesInstitucionales::RECURSOS_HUMANOS, ['email' => 'libre@campuspass.test']);

    $response = $this->actingAs($admin)->patch(route('configuracion.usuarios.update', $otro), [
        'email' => 'ocupado@campuspass.test',
    ]);

    $response->assertSessionHasErrors('email');
    expect($otro->fresh()->email)->toBe('libre@campuspass.test');
});

test('deactivating a user prevents them from logging in', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();
    $docente = crearUsuarioBiblioteca(RolesInstitucionales::DOCENTE, ['email' => 'docente@campuspass.test']);

    $this->actingAs($admin)->patch(route('configuracion.usuarios.update', $docente), [
        'activo' => false,
    ])->assertRedirect();

    expect($docente->fresh()->activo)->toBeFalse();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'docente@campuspass.test',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('an admin cannot deactivate their own account', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->patch(route('configuracion.usuarios.update', $admin), [
        'activo' => false,
    ]);

    $response->assertSessionHasErrors('activo');
    expect($admin->fresh()->activo)->toBeTrue();
});

test('an admin cannot delete their own account', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->delete(route('configuracion.usuarios.destroy', $admin));

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('permission matrix updates persist', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $this->actingAs($admin)->patch(route('configuracion.permisos'), [
        'matriz' => [
            'ver-accesos' => [RolesInstitucionales::DOCENTE => true, RolesInstitucionales::RECURSOS_HUMANOS => true],
            'configurar-sistema' => [RolesInstitucionales::DOCENTE => false, RolesInstitucionales::RECURSOS_HUMANOS => false],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('tipo_usuario_permiso', [
        'tipo_usuario' => RolesInstitucionales::RECURSOS_HUMANOS,
        'permiso_id' => 'ver-accesos',
    ]);
});

test('weekly schedule updates persist', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $dias = collect(HorarioBiblioteca::DIAS)->map(fn (string $dia) => [
        'dia' => $dia,
        'abierto' => $dia !== 'Domingo',
        'apertura' => '09:00',
        'cierre' => '18:00',
    ])->all();

    $this->actingAs($admin)->patch(route('configuracion.horarios'), ['dias' => $dias])
        ->assertRedirect();

    expect(HorarioBiblioteca::query()->where('dia', 'Lunes')->value('apertura'))->toBe('09:00');
});

test('the horarios prop trims seconds so re-saving untouched days does not fail validation', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $pagina = $this->actingAs($admin)->get(route('configuracion'));

    $pagina->assertInertia(fn (Assert $page) => $page
        ->where('horarios.0.apertura', '08:00')
        ->where('horarios.0.cierre', '19:00')
    );

    $dias = json_decode(json_encode($pagina->viewData('page')), true)['props']['horarios'];

    $this->actingAs($admin)->patch(route('configuracion.horarios'), ['dias' => $dias])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

test('an admin can add and remove a schedule exception', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $this->actingAs($admin)->post(route('configuracion.horarios.excepciones.store'), [
        'fecha' => '2026-12-25',
        'motivo' => 'Navidad',
        'cerrado_todo_dia' => true,
    ])->assertRedirect();

    $excepcion = ExcepcionHorarioBiblioteca::query()->where('motivo', 'Navidad')->firstOrFail();

    $this->actingAs($admin)->delete(route('configuracion.horarios.excepciones.destroy', $excepcion))
        ->assertRedirect();

    $this->assertDatabaseMissing('excepciones_horario_biblioteca', ['id' => $excepcion->id]);
});

test('notification event preferences persist', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $this->actingAs($admin)->patch(route('configuracion.notificaciones'), [
        'eventos' => [
            'incidencia' => ['correo' => false, 'push' => false],
        ],
    ])->assertRedirect();

    expect(NotificacionEventoBiblioteca::query()->where('evento', 'incidencia')->value('correo'))->toBeFalsy();
});

test('limpiar cache responds successfully', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $this->actingAs($admin)->post(route('configuracion.limpiar-cache'))
        ->assertRedirect();
});

test('registros del sistema returns json', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    $response = $this->actingAs($admin)->getJson(route('configuracion.registros'));

    $response->assertOk();
    $response->assertJsonStructure(['lineas']);
});

test('respaldar base de datos declines gracefully outside mysql', function () {
    sembrarCatalogosConfiguracion();
    $admin = crearUsuarioBiblioteca();

    expect(DB::connection()->getDriverName())->toBe('sqlite');

    $response = $this->actingAs($admin)->get(route('configuracion.respaldo'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
