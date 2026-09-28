<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eventos\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SeguridadController extends Controller
{
    /** Pantalla de Seguridad: dice si el usuario ya tiene 2FA activado. */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        return Inertia::render('eventos/seguridad', [
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }

    /** Actualiza la contraseña del usuario de Eventos Académicos. */
    public function actualizarPassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada.']);

        return back();
    }
}
