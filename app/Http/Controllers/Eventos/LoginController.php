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
    public function create(): Response
    {
        return Inertia::render('eventos/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

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

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('eventos')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        
        Inertia::clearHistory();

        return redirect()->route('home');
    }
}
