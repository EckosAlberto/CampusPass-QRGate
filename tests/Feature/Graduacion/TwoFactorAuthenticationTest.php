<?php

use App\Models\GraduacionUsuario;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;

function habilitarDosFactoresGraduacion(GraduacionUsuario $usuario): string
{
    $secreto = app(Google2FA::class)->generateSecretKey();

    $usuario->forceFill([
        'two_factor_secret' => encrypt($secreto),
        'two_factor_recovery_codes' => encrypt(json_encode(['codigo-uno', 'codigo-dos'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $secreto;
}

test('logging in with 2fa enabled does not authenticate immediately and redirects to the challenge', function () {
    $usuario = GraduacionUsuario::factory()->create();
    habilitarDosFactoresGraduacion($usuario);

    $response = $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertGuest('graduacion');
    $response->assertRedirect(route('graduacion.two-factor.login'));
});

test('a valid totp code completes the login', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $secreto = habilitarDosFactoresGraduacion($usuario);

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $codigo = app(Google2FA::class)->getCurrentOtp($secreto);

    $response = $this->post(route('graduacion.two-factor.login.store'), [
        'code' => $codigo,
    ]);

    $this->assertAuthenticatedAs($usuario, 'graduacion');
    $response->assertRedirect(route('graduacion.panel', absolute: false));
});

test('an invalid totp code does not complete the login', function () {
    $usuario = GraduacionUsuario::factory()->create();
    habilitarDosFactoresGraduacion($usuario);

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('graduacion.two-factor.login.store'), [
        'code' => '000000',
    ]);

    $this->assertGuest('graduacion');
    $response->assertSessionHasErrors('code');
});

test('a recovery code completes the login and can only be used once', function () {
    $usuario = GraduacionUsuario::factory()->create();
    habilitarDosFactoresGraduacion($usuario);

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('graduacion.two-factor.login.store'), [
        'recovery_code' => 'codigo-uno',
    ]);

    $this->assertAuthenticatedAs($usuario, 'graduacion');
    $response->assertRedirect(route('graduacion.panel', absolute: false));

    expect($usuario->fresh()->recoveryCodes())->not->toContain('codigo-uno');
});

test('the two factor challenge page cannot be visited without a pending login', function () {
    $response = $this->get(route('graduacion.two-factor.login'));

    $response->assertRedirect(route('graduacion.login'));
});

test('the two factor challenge page renders when a login is pending', function () {
    $usuario = GraduacionUsuario::factory()->create();
    habilitarDosFactoresGraduacion($usuario);

    $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->get(route('graduacion.two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('graduacion/two-factor-challenge'),
        );
});

test('logging in without 2fa enabled authenticates immediately', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $response = $this->post(route('graduacion.login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($usuario, 'graduacion');
    $response->assertRedirect(route('graduacion.panel', absolute: false));
});
