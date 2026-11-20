<?php

use App\Models\EventosUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('eventos password can be updated', function () {
    $usuario = EventosUsuario::factory()->create();

    $response = $this
        ->actingAs($usuario, 'eventos')
        ->from(route('eventos.seguridad.edit'))
        ->put(route('eventos.seguridad.password'), [
            'current_password' => 'password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('eventos.seguridad.edit'));

    expect(Hash::check('New-Password1', $usuario->refresh()->password))->toBeTrue();
});

test('correct current password must be provided to update eventos password', function () {
    $usuario = EventosUsuario::factory()->create();

    $response = $this
        ->actingAs($usuario, 'eventos')
        ->from(route('eventos.seguridad.edit'))
        ->put(route('eventos.seguridad.password'), [
            'current_password' => 'wrong-password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasErrors('current_password');
});

test('a biblioteca users password does not satisfy the eventos current password check', function () {

    User::factory()->create(['password' => 'shared-password']);
    $usuario = EventosUsuario::factory()->create(['password' => 'different-password']);

    $response = $this
        ->actingAs($usuario, 'eventos')
        ->put(route('eventos.seguridad.password'), [
            'current_password' => 'shared-password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('different-password', $usuario->refresh()->password))->toBeTrue();
});
