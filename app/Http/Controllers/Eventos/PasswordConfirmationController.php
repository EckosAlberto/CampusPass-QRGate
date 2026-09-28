<?php

namespace App\Http\Controllers\Eventos;

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
    /** Muestra el formulario que pide la contraseña otra vez antes de entrar a Seguridad. */
    public function show(): Response
    {
        return Inertia::render('eventos/confirmar-password');
    }

    /** Revalida la contraseña actual y, si es correcta, deja pasar a Seguridad por un rato. */
    public function store(Request $request, ConfirmPassword $confirm): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $confirmado = $confirm(Auth::guard('eventos'), $request->user(), $request->input('password'));

        if (! $confirmado) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña proporcionada no es correcta.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', now()->unix());

        return redirect()->intended(route('eventos.seguridad.edit'));
    }
}
