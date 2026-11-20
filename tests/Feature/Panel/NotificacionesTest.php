<?php

use App\Models\IncidenciaAcceso;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('notificaciones.index'));

    $response->assertRedirect(route('login'));
});

test('only lists incidencias without an evento (biblioteca), not eventos ones', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

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

    $response = $this->getJson(route('notificaciones.index'));

    $response->assertOk();
    expect($response->json('items'))->toHaveCount(1);
    expect($response->json('items.0.mensaje'))->toBe('De biblioteca');
    expect($response->json('noVistas'))->toBe(1);
});

test('marcarVistas resets the unseen count to 0', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    IncidenciaAcceso::create([
        'no_de_control' => '20010001',
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    $this->postJson(route('notificaciones.marcar-vistas'))
        ->assertOk()
        ->assertJson(['noVistas' => 0]);

    expect($usuario->fresh()->notificaciones_vistas_en)->not->toBeNull();

    $response = $this->getJson(route('notificaciones.index'));
    expect($response->json('noVistas'))->toBe(0);
});

test('rol_label is present on the shared auth user', function () {
    $usuario = crearUsuarioBiblioteca();
    $this->actingAs($usuario);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.rol_label', 'Administrador'),
    );
});
