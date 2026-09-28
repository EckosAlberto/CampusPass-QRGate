<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmPassword;


class PasswordConfirmationController extends Controller
{
    
    public function show(): Response
    {
        return Inertia::render('graduacion/confirmar-password');
    }

    
    public function store(Request $request, ConfirmPassword $confirm): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $confirmado = $confirm(Auth::guard('graduacion'), $request->user(), $request->input('password'));

        if (! $confirmado) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña proporcionada no es correcta.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', now()->unix());

        return redirect()->intended(route('graduacion.seguridad.edit'));
    }
}
