<?php

use App\Http\Controllers\Eventos\ConfiguracionController;
use App\Http\Controllers\Eventos\EventoController;
use App\Http\Controllers\Eventos\IncidenciasController;
use App\Http\Controllers\Eventos\LoginController;
use App\Http\Controllers\Eventos\MisEventosController;
use App\Http\Controllers\Eventos\NotificacionesController;
use App\Http\Controllers\Eventos\PanelController;
use App\Http\Controllers\Eventos\PasswordConfirmationController;
use App\Http\Controllers\Eventos\ReporteController;
use App\Http\Controllers\Eventos\SeguridadController;
use App\Http\Controllers\Eventos\TwoFactorChallengeController;
use App\Http\Middleware\PreventBackHistoryCache;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Inertia\EncryptHistoryMiddleware;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;


Route::middleware('guest:eventos')->group(function () {
    Route::get('eventos-academicos/login', [LoginController::class, 'create'])->name('eventos.login');
    Route::post('eventos-academicos/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('eventos.login.store');

    Route::get('eventos-academicos/dos-factores', [TwoFactorChallengeController::class, 'create'])->name('eventos.two-factor.login');
    Route::post('eventos-academicos/dos-factores', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:two-factor-eventos')
        ->name('eventos.two-factor.login.store');
});

Route::middleware(['auth:eventos', PreventBackHistoryCache::class, EncryptHistoryMiddleware::class])->group(function () {
    Route::post('eventos-academicos/logout', [LoginController::class, 'destroy'])->name('eventos.logout');

    Route::get('eventos-academicos/cuenta/confirmar-password', [PasswordConfirmationController::class, 'show'])->name('eventos.password.confirm');
    Route::post('eventos-academicos/cuenta/confirmar-password', [PasswordConfirmationController::class, 'store'])->name('eventos.password.confirm.store');

    Route::get('eventos-academicos/cuenta/seguridad', [SeguridadController::class, 'edit'])
        ->middleware(RequirePassword::using('eventos.password.confirm'))
        ->name('eventos.seguridad.edit');

    Route::put('eventos-academicos/cuenta/password', [SeguridadController::class, 'actualizarPassword'])
        ->middleware('throttle:6,1')
        ->name('eventos.seguridad.password');

    Route::middleware(RequirePassword::using('eventos.password.confirm'))->group(function () {
        Route::post('eventos-academicos/cuenta/dos-factores', [TwoFactorAuthenticationController::class, 'store'])->name('eventos.two-factor.enable');
        Route::post('eventos-academicos/cuenta/dos-factores/confirmar', [ConfirmedTwoFactorAuthenticationController::class, 'store'])->name('eventos.two-factor.confirm');
        Route::delete('eventos-academicos/cuenta/dos-factores', [TwoFactorAuthenticationController::class, 'destroy'])->name('eventos.two-factor.disable');
        Route::get('eventos-academicos/cuenta/dos-factores/qr', [TwoFactorQrCodeController::class, 'show'])->name('eventos.two-factor.qr-code');
        Route::get('eventos-academicos/cuenta/dos-factores/clave', [TwoFactorSecretKeyController::class, 'show'])->name('eventos.two-factor.secret-key');
        Route::get('eventos-academicos/cuenta/dos-factores/codigos', [RecoveryCodeController::class, 'index'])->name('eventos.two-factor.recovery-codes');
        Route::post('eventos-academicos/cuenta/dos-factores/codigos', [RecoveryCodeController::class, 'store'])->name('eventos.two-factor.regenerate-recovery-codes');
    });

    Route::get('eventos-academicos', [PanelController::class, 'index'])->name('eventos.panel');

    Route::get('eventos-academicos/notificaciones', [NotificacionesController::class, 'index'])->name('eventos.notificaciones.index');
    Route::post('eventos-academicos/notificaciones/marcar-vistas', [NotificacionesController::class, 'marcarVistas'])->name('eventos.notificaciones.marcar-vistas');

    Route::get('eventos-academicos/eventos', [EventoController::class, 'index'])->name('eventos.eventos.index');
    Route::post('eventos-academicos/eventos', [EventoController::class, 'store'])->name('eventos.eventos.store');
    Route::put('eventos-academicos/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.eventos.update');
    Route::patch('eventos-academicos/eventos/{evento}/estatus', [EventoController::class, 'actualizarEstatus'])->name('eventos.eventos.estatus');
    Route::delete('eventos-academicos/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.eventos.destroy');

    Route::get('eventos-academicos/mis-eventos', [MisEventosController::class, 'index'])->name('eventos.mis-eventos.index');
    Route::post('eventos-academicos/mis-eventos/{evento}/url', [MisEventosController::class, 'generarUrl'])->name('eventos.mis-eventos.generar-url');
    Route::post('eventos-academicos/mis-eventos/{evento}/compartir', [MisEventosController::class, 'compartir'])->name('eventos.mis-eventos.compartir');

    Route::get('eventos-academicos/incidencias', [IncidenciasController::class, 'index'])->name('eventos.incidencias.index');
    Route::delete('eventos-academicos/incidencias', [IncidenciasController::class, 'destroyTodas'])->name('eventos.incidencias.destroy-todas');
    Route::delete('eventos-academicos/incidencias/{incidencia}', [IncidenciasController::class, 'destroy'])->name('eventos.incidencias.destroy');

    Route::get('eventos-academicos/reportes', [ReporteController::class, 'index'])->name('eventos.reportes.index');
    Route::post('eventos-academicos/reportes', [ReporteController::class, 'store'])->name('eventos.reportes.store');
    Route::get('eventos-academicos/reportes/{reporte}', [ReporteController::class, 'show'])->name('eventos.reportes.show');
    Route::delete('eventos-academicos/reportes/{reporte}', [ReporteController::class, 'destroy'])->name('eventos.reportes.destroy');
    Route::get('eventos-academicos/reportes/{reporte}/pdf', [ReporteController::class, 'descargarPdf'])->name('eventos.reportes.pdf');
    Route::get('eventos-academicos/reportes/{reporte}/excel', [ReporteController::class, 'exportarExcel'])->name('eventos.reportes.excel');
    Route::post('eventos-academicos/reportes/{reporte}/compartir', [ReporteController::class, 'compartir'])->name('eventos.reportes.compartir');

    Route::get('eventos-academicos/configuracion', [ConfiguracionController::class, 'index'])->name('eventos.configuracion');
    Route::post('eventos-academicos/configuracion/usuarios', [ConfiguracionController::class, 'storeUsuario'])->name('eventos.configuracion.usuarios.store');
    Route::patch('eventos-academicos/configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'updateUsuario'])->name('eventos.configuracion.usuarios.update');
    Route::delete('eventos-academicos/configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'destroyUsuario'])->name('eventos.configuracion.usuarios.destroy');
    Route::patch('eventos-academicos/configuracion/permisos', [ConfiguracionController::class, 'actualizarPermisos'])->name('eventos.configuracion.permisos');
});
