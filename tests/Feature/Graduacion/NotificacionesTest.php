<?php

use App\Models\GraduacionUsuario;
use App\Models\IncidenciaAccesoGraduacion;
use App\Models\RolGraduacion;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('graduacion.notificaciones.index'));

    $response->assertRedirect(route('graduacion.login'));
});

test('lists recent incidencias with the unseen count', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $this->actingAs($usuario, 'graduacion');

    $ceremonia = crearCeremoniaVigente();

    IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'no-existe',
        'tipo' => IncidenciaAccesoGraduacion::NO_REGISTRADO,
        'mensaje' => 'Código no encontrado',
        'fecha_hora' => now(),
    ]);

    $response = $this->getJson(route('graduacion.notificaciones.index'));

    $response->assertOk();
    expect($response->json('items'))->toHaveCount(1);
    expect($response->json('items.0.mensaje'))->toBe('Código no encontrado');
    expect($response->json('noVistas'))->toBe(1);
});

test('marcarVistas resets the unseen count to 0', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $this->actingAs($usuario, 'graduacion');

    $ceremonia = crearCeremoniaVigente();

    IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'no-existe',
        'tipo' => IncidenciaAccesoGraduacion::NO_REGISTRADO,
        'mensaje' => 'Código no encontrado',
        'fecha_hora' => now(),
    ]);

    $this->postJson(route('graduacion.notificaciones.marcar-vistas'))
        ->assertOk()
        ->assertJson(['noVistas' => 0]);

    expect($usuario->fresh()->notificaciones_vistas_en)->not->toBeNull();

    $response = $this->getJson(route('graduacion.notificaciones.index'));
    expect($response->json('noVistas'))->toBe(0);
});

test('rol_label is present on the shared auth user', function () {
    $rol = RolGraduacion::create(['nombre' => 'Coordinador de Graduación']);
    $usuario = GraduacionUsuario::factory()->create(['rol_id' => $rol->id]);
    $this->actingAs($usuario, 'graduacion');

    $response = $this->get(route('graduacion.panel'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.rol_label', 'Coordinador de Graduación'),
    );
});
