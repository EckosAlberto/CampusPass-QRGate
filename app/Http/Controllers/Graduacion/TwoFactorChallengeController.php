<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\GraduacionUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Controlador para manejar el desafío de autenticación de dos factores (2FA) para usuarios de Graduación.
 */
class TwoFactorChallengeController extends Controller
{
    
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('graduacion.2fa.login.id')) {
            return redirect()->route('graduacion.login');
        }

        return Inertia::render('graduacion/two-factor-challenge');
    }

    
    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('graduacion.2fa.login.id');
        $user = $userId ? GraduacionUsuario::find($userId) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => trans('auth.failed'),
            ]);
        }

        $codigoValido = ! empty($validated['code'])
            && $user->two_factor_secret
            && $provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $validated['code']);

        $codigoRecuperacion = null;

        if (! $codigoValido && ! empty($validated['recovery_code'])) {
            $codigoRecuperacion = collect($user->recoveryCodes())
                ->first(fn (string $codigo) => hash_equals($codigo, $validated['recovery_code']));
        }

        if (! $codigoValido && ! $codigoRecuperacion) {
            throw ValidationException::withMessages([
                'code' => 'El código de autenticación proporcionado no es válido.',
            ]);
        }

        if ($codigoRecuperacion) {
            $user->replaceRecoveryCode($codigoRecuperacion);
        }

        $remember = $request->session()->pull('graduacion.2fa.login.remember', false);
        $request->session()->forget('graduacion.2fa.login.id');

        Auth::guard('graduacion')->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('graduacion.panel'));
    }
}
