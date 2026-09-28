<?php

use App\Models\GraduacionUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('graduacion password can be updated', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $response = $this
        ->actingAs($usuario, 'graduacion')
        ->from(route('graduacion.seguridad.edit'))
        ->put(route('graduacion.seguridad.password'), [
            'current_password' => 'password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('graduacion.seguridad.edit'));

    expect(Hash::check('New-Password1', $usuario->refresh()->password))->toBeTrue();
});

test('correct current password must be provided to update graduacion password', function () {
    $usuario = GraduacionUsuario::factory()->create();

    $response = $this
        ->actingAs($usuario, 'graduacion')
        ->from(route('graduacion.seguridad.edit'))
        ->put(route('graduacion.seguridad.password'), [
            'current_password' => 'wrong-password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasErrors('current_password');
});

test('a biblioteca users password does not satisfy the graduacion current password check', function () {

    User::factory()->create(['password' => 'shared-password']);
    $usuario = GraduacionUsuario::factory()->create(['password' => 'different-password']);

    $response = $this
        ->actingAs($usuario, 'graduacion')
        ->put(route('graduacion.seguridad.password'), [
            'current_password' => 'shared-password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('different-password', $usuario->refresh()->password))->toBeTrue();
});
