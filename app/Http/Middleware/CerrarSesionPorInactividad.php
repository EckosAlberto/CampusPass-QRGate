<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionBiblioteca;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CerrarSesionPorInactividad
{
    public function handle(Request $request, Closure $next): Response
    {
        $guardName = match (true) {
            $request->is('eventos-academicos*') => 'eventos',
            $request->is('graduacion*') => 'graduacion',
            default => 'web',
        };

        $guard = Auth::guard($guardName);
        $usuario = $guard->user();

        if (! $usuario) {
            return $next($request);
        }

        $ajustes = ConfiguracionBiblioteca::query()->first();
        $cerrarPorInactividad = $ajustes?->sesion_cerrar_auto ?? true;
        $minutosInactividad = min(120, max(1, (int) ($ajustes?->sesion_inactividad_minutos ?? 30)));
        $ultimaActividad = $request->session()->get('campuspass.ultima_actividad');

        if ($cerrarPorInactividad && $guard->viaRemember() && ! $ultimaActividad) {
            return $this->cerrarSesion($request, $guardName);
        }

        if (
            $cerrarPorInactividad
            && $ultimaActividad
            && now()->timestamp - (int) $ultimaActividad >= $minutosInactividad * 60
        ) {
            return $this->cerrarSesion($request, $guardName);
        }

        $request->session()->put('campuspass.ultima_actividad', now()->timestamp);

        return $next($request);
    }

    private function cerrarSesion(Request $request, string $guardName): Response
    {
        Auth::guard($guardName)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => 'Tu sesión se cerró por inactividad. Inicia sesión nuevamente.',
        ]);

        $ruta = match ($guardName) {
            'eventos' => 'eventos.login',
            'graduacion' => 'graduacion.login',
            default => 'login',
        };

        return redirect()->route($ruta);
    }
}
