<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /** Muestra el formulario de inicio de sesión de Eventos Académicos. */
    public function create(): Response
    {
        return Inertia::render('eventos/login');
    }

    /** Procesa el inicio de sesión de Eventos Académicos. */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Se obtiene el proveedor de usuarios del guardia 'eventos' para validar las credenciales.
        $provider = Auth::guard('eventos')->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user || ! $provider->validateCredentials($user, ['password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        
        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'eventos.2fa.login.id' => $user->getKey(),
                'eventos.2fa.login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('eventos.two-factor.login');
        }

        Auth::guard('eventos')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('eventos.panel'));
    }

    /** Cierra sesión y borra el historial de navegación cifrado. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('eventos')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        
        Inertia::clearHistory();

        return redirect()->route('home');
    }
}
