<?php

use App\Models\GraduacionUsuario;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('graduacion.login'));

    $response->assertOk();
});

test('graduacion users can authenticate using the login screen', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $response = $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('graduacion');
    $response->assertRedirect(route('graduacion.panel', absolute: false));
});

test('graduacion users can not authenticate with invalid password', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('graduacion');
});

test('graduacion users can logout', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $response = $this->actingAs($usuario, 'graduacion')->post(route('graduacion.logout'));

    $this->assertGuest('graduacion');
    $response->assertRedirect(route('home'));
});

test('logging into graduacion does not authenticate the biblioteca or eventos guards', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('graduacion');
    $this->assertGuest('web');
    $this->assertGuest('eventos');
});

test('guests are redirected away from the graduacion panel', function () {
    $response = $this->get(route('graduacion.panel'));

    $response->assertRedirect(route('graduacion.login'));
});

test('biblioteca users cannot access the graduacion panel', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('graduacion.panel'));

    $response->assertRedirect(route('graduacion.login'));
});
