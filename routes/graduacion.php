<?php

use App\Http\Controllers\Graduacion\BoletoController;
use App\Http\Controllers\Graduacion\CeremoniaController;
use App\Http\Controllers\Graduacion\ConfiguracionController;
use App\Http\Controllers\Graduacion\EstadisticasController;
use App\Http\Controllers\Graduacion\IncidenciasController;
use App\Http\Controllers\Graduacion\LoginController;
use App\Http\Controllers\Graduacion\MisCeremoniasController;
use App\Http\Controllers\Graduacion\NotificacionesController;
use App\Http\Controllers\Graduacion\PanelController;
use App\Http\Controllers\Graduacion\PasswordConfirmationController;
use App\Http\Controllers\Graduacion\ReporteController;
use App\Http\Controllers\Graduacion\SeguridadController;
use App\Http\Controllers\Graduacion\TwoFactorChallengeController;
use App\Http\Middleware\PreventBackHistoryCache;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Inertia\EncryptHistoryMiddleware;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;


Route::middleware('guest:graduacion')->group(function () {
    Route::get('graduacion/login', [LoginController::class, 'create'])->name('graduacion.login');
    Route::post('graduacion/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('graduacion.login.store');

    Route::get('graduacion/dos-factores', [TwoFactorChallengeController::class, 'create'])->name('graduacion.two-factor.login');
    Route::post('graduacion/dos-factores', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:two-factor-graduacion')
        ->name('graduacion.two-factor.login.store');
});

Route::middleware(['auth:graduacion', PreventBackHistoryCache::class, EncryptHistoryMiddleware::class])->group(function () {
    Route::post('graduacion/logout', [LoginController::class, 'destroy'])->name('graduacion.logout');

    Route::get('graduacion/cuenta/confirmar-password', [PasswordConfirmationController::class, 'show'])->name('graduacion.password.confirm');
    Route::post('graduacion/cuenta/confirmar-password', [PasswordConfirmationController::class, 'store'])->name('graduacion.password.confirm.store');

    Route::get('graduacion/cuenta/seguridad', [SeguridadController::class, 'edit'])
        ->middleware(RequirePassword::using('graduacion.password.confirm'))
        ->name('graduacion.seguridad.edit');

    Route::put('graduacion/cuenta/password', [SeguridadController::class, 'actualizarPassword'])
        ->middleware('throttle:6,1')
        ->name('graduacion.seguridad.password');

    Route::middleware(RequirePassword::using('graduacion.password.confirm'))->group(function () {
        Route::post('graduacion/cuenta/dos-factores', [TwoFactorAuthenticationController::class, 'store'])->name('graduacion.two-factor.enable');
        Route::post('graduacion/cuenta/dos-factores/confirmar', [ConfirmedTwoFactorAuthenticationController::class, 'store'])->name('graduacion.two-factor.confirm');
        Route::delete('graduacion/cuenta/dos-factores', [TwoFactorAuthenticationController::class, 'destroy'])->name('graduacion.two-factor.disable');
        Route::get('graduacion/cuenta/dos-factores/qr', [TwoFactorQrCodeController::class, 'show'])->name('graduacion.two-factor.qr-code');
        Route::get('graduacion/cuenta/dos-factores/clave', [TwoFactorSecretKeyController::class, 'show'])->name('graduacion.two-factor.secret-key');
        Route::get('graduacion/cuenta/dos-factores/codigos', [RecoveryCodeController::class, 'index'])->name('graduacion.two-factor.recovery-codes');
        Route::post('graduacion/cuenta/dos-factores/codigos', [RecoveryCodeController::class, 'store'])->name('graduacion.two-factor.regenerate-recovery-codes');
    });

    Route::get('graduacion', [PanelController::class, 'index'])->name('graduacion.panel');

    Route::get('graduacion/notificaciones', [NotificacionesController::class, 'index'])->name('graduacion.notificaciones.index');
    Route::post('graduacion/notificaciones/marcar-vistas', [NotificacionesController::class, 'marcarVistas'])->name('graduacion.notificaciones.marcar-vistas');

    Route::get('graduacion/ceremonias', [CeremoniaController::class, 'index'])->name('graduacion.ceremonias.index');
    Route::post('graduacion/ceremonias', [CeremoniaController::class, 'store'])->name('graduacion.ceremonias.store');
    Route::get('graduacion/ceremonias/{ceremonia}', [CeremoniaController::class, 'show'])->name('graduacion.ceremonias.show');
    Route::put('graduacion/ceremonias/{ceremonia}', [CeremoniaController::class, 'update'])->name('graduacion.ceremonias.update');
    Route::patch('graduacion/ceremonias/{ceremonia}/estatus', [CeremoniaController::class, 'actualizarEstatus'])->name('graduacion.ceremonias.estatus');
    Route::post('graduacion/ceremonias/{ceremonia}/finalizar', [CeremoniaController::class, 'finalizar'])->name('graduacion.ceremonias.finalizar');
    Route::post('graduacion/ceremonias/{ceremonia}/compartir', [CeremoniaController::class, 'compartir'])->name('graduacion.ceremonias.compartir');
    Route::delete('graduacion/ceremonias/{ceremonia}', [CeremoniaController::class, 'destroy'])->name('graduacion.ceremonias.destroy');

    Route::get('graduacion/mis-ceremonias', [MisCeremoniasController::class, 'index'])->name('graduacion.mis-ceremonias.index');

    Route::get('graduacion/boletos', [BoletoController::class, 'index'])->name('graduacion.boletos.index');
    Route::post('graduacion/boletos/ceremonias/{ceremonia}/generar', [BoletoController::class, 'generar'])->name('graduacion.boletos.generar');
    Route::get('graduacion/boletos/{boleto}/qr', [BoletoController::class, 'descargarQr'])->name('graduacion.boletos.qr');
    Route::post('graduacion/boletos/{boleto}/regenerar', [BoletoController::class, 'regenerar'])->name('graduacion.boletos.regenerar');
    Route::get('graduacion/boletos/ceremonias/{ceremonia}/qr-grupal', [BoletoController::class, 'descargarQrGrupal'])->name('graduacion.boletos.qr-grupal');

    Route::get('graduacion/estadisticas', [EstadisticasController::class, 'index'])->name('graduacion.estadisticas');

    Route::get('graduacion/incidencias', [IncidenciasController::class, 'index'])->name('graduacion.incidencias.index');
    Route::delete('graduacion/incidencias', [IncidenciasController::class, 'destroyTodas'])->name('graduacion.incidencias.destroy-todas');
    Route::delete('graduacion/incidencias/{incidencia}', [IncidenciasController::class, 'destroy'])->name('graduacion.incidencias.destroy');

    Route::get('graduacion/reportes', [ReporteController::class, 'index'])->name('graduacion.reportes.index');
    Route::post('graduacion/reportes', [ReporteController::class, 'store'])->name('graduacion.reportes.store');
    Route::get('graduacion/reportes/{reporte}', [ReporteController::class, 'show'])->name('graduacion.reportes.show');
    Route::delete('graduacion/reportes/{reporte}', [ReporteController::class, 'destroy'])->name('graduacion.reportes.destroy');
    Route::get('graduacion/reportes/{reporte}/pdf', [ReporteController::class, 'descargarPdf'])->name('graduacion.reportes.pdf');
    Route::get('graduacion/reportes/{reporte}/excel', [ReporteController::class, 'exportarExcel'])->name('graduacion.reportes.excel');
    Route::post('graduacion/reportes/{reporte}/compartir', [ReporteController::class, 'compartir'])->name('graduacion.reportes.compartir');

    Route::get('graduacion/configuracion', [ConfiguracionController::class, 'index'])->name('graduacion.configuracion');
    Route::post('graduacion/configuracion/usuarios', [ConfiguracionController::class, 'storeUsuario'])->name('graduacion.configuracion.usuarios.store');
    Route::patch('graduacion/configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'updateUsuario'])->name('graduacion.configuracion.usuarios.update');
    Route::delete('graduacion/configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'destroyUsuario'])->name('graduacion.configuracion.usuarios.destroy');
    Route::patch('graduacion/configuracion/permisos', [ConfiguracionController::class, 'actualizarPermisos'])->name('graduacion.configuracion.permisos');
});
