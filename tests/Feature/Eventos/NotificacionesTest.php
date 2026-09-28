<?php

use App\Models\EventosUsuario;
use App\Models\IncidenciaAcceso;
use App\Models\RolEventos;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('eventos.notificaciones.index'));

    $response->assertRedirect(route('eventos.login'));
});

test('only lists incidencias with an evento (eventos), not biblioteca ones', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    IncidenciaAcceso::create([
        'no_de_control' => '20010001',
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    $evento = crearEventoVigente();

    IncidenciaAcceso::create([
        'no_de_control' => '20010002',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    $response = $this->getJson(route('eventos.notificaciones.index'));

    $response->assertOk();
    expect($response->json('items'))->toHaveCount(1);
    expect($response->json('items.0.mensaje'))->toBe('De eventos');
    expect($response->json('noVistas'))->toBe(1);
});

test('marcarVistas resets the unseen count to 0', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    $evento = crearEventoVigente();

    IncidenciaAcceso::create([
        'no_de_control' => '20010002',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    $this->postJson(route('eventos.notificaciones.marcar-vistas'))
        ->assertOk()
        ->assertJson(['noVistas' => 0]);

    expect($usuario->fresh()->notificaciones_vistas_en)->not->toBeNull();

    $response = $this->getJson(route('eventos.notificaciones.index'));
    expect($response->json('noVistas'))->toBe(0);
});

test('rol_label is present on the shared auth user', function () {
    $rol = RolEventos::create(['nombre' => 'Coordinador de Eventos']);
    $usuario = EventosUsuario::factory()->create(['rol_id' => $rol->id]);
    $this->actingAs($usuario, 'eventos');

    $response = $this->get(route('eventos.panel'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.rol_label', 'Coordinador de Eventos'),
    );
});
