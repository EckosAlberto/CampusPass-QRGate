<?php

use App\Models\EventosUsuario;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('eventos.login'));

    $response->assertOk();
});

test('eventos users can authenticate using the login screen', function () {
    $usuario = EventosUsuario::factory()->create();

    $response = $this->post(route('eventos.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('eventos');
    $response->assertRedirect(route('eventos.panel', absolute: false));
});

test('eventos users can not authenticate with invalid password', function () {
    $usuario = EventosUsuario::factory()->create();

    $this->post(route('eventos.login.store'), [
        'email' => $usuario->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('eventos');
});

test('eventos users can logout', function () {
    $usuario = EventosUsuario::factory()->create();

    $response = $this->actingAs($usuario, 'eventos')->post(route('eventos.logout'));

    $this->assertGuest('eventos');
    $response->assertRedirect(route('home'));
});

test('logging into eventos does not authenticate the biblioteca or graduacion guards', function () {
    $usuario = EventosUsuario::factory()->create();

    $this->post(route('eventos.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('eventos');
    $this->assertGuest('web');
    $this->assertGuest('graduacion');
});

test('guests are redirected away from the eventos panel', function () {
    $response = $this->get(route('eventos.panel'));

    $response->assertRedirect(route('eventos.login'));
});

test('biblioteca users cannot access the eventos panel', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('eventos.panel'));

    $response->assertRedirect(route('eventos.login'));
});
