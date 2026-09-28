<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /** Muestra el formulario de inicio de sesión de Graduación. */
    public function create(): Response
    {
        return Inertia::render('graduacion/login');
    }

    
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        
        $provider = Auth::guard('graduacion')->getProvider();
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
                'graduacion.2fa.login.id' => $user->getKey(),
                'graduacion.2fa.login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('graduacion.two-factor.login');
        }

        Auth::guard('graduacion')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('graduacion.panel'));
    }

    
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('graduacion')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        
        Inertia::clearHistory();

        return redirect()->route('home');
    }
}
