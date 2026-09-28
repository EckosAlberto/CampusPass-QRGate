<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduacion\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SeguridadController extends Controller
{
    /** Pantalla de Seguridad */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        return Inertia::render('graduacion/seguridad', [
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }

    /** Actualiza la contraseña del usuario de Graduación. */
    public function actualizarPassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada.']);

        return back();
    }
}
