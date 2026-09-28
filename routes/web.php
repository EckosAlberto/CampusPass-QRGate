<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Panel\ConfiguracionController;
use App\Http\Controllers\Panel\EstadisticasController;
use App\Http\Controllers\Panel\IncidenciasController;
use App\Http\Controllers\Panel\NotificacionesController;
use App\Http\Controllers\Panel\PersonasDentroController;
use App\Http\Controllers\Panel\RegistroDelDiaController;
use App\Http\Controllers\Panel\ReporteController;
use App\Http\Middleware\PreventBackHistoryCache;
use Illuminate\Support\Facades\Route;
use Inertia\EncryptHistoryMiddleware;

// Rutas públicas: no requieren autenticación (fuera del grupo 'auth').
Route::inertia('/', 'portal')->name('home');

// Rutas protegidas
Route::middleware(['auth', 'verified', PreventBackHistoryCache::class, EncryptHistoryMiddleware::class])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('registro-del-dia', [RegistroDelDiaController::class, 'index'])->name('registro-del-dia');

    Route::get('personas-dentro', [PersonasDentroController::class, 'index'])->name('personas-dentro');

    Route::get('incidencias', [IncidenciasController::class, 'index'])->name('incidencias');
    Route::delete('incidencias', [IncidenciasController::class, 'destroyTodas'])->name('incidencias.destroy-todas');
    Route::delete('incidencias/{incidencia}', [IncidenciasController::class, 'destroy'])->name('incidencias.destroy');

    Route::get('notificaciones', [NotificacionesController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/marcar-vistas', [NotificacionesController::class, 'marcarVistas'])->name('notificaciones.marcar-vistas');

    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes');
    Route::post('reportes', [ReporteController::class, 'store'])->name('reportes.store');
    Route::get('reportes/{reporte}', [ReporteController::class, 'show'])->name('reportes.show');
    Route::delete('reportes/{reporte}', [ReporteController::class, 'destroy'])->name('reportes.destroy');
    Route::get('reportes/{reporte}/pdf', [ReporteController::class, 'descargarPdf'])->name('reportes.pdf');
    Route::get('reportes/{reporte}/excel', [ReporteController::class, 'exportarExcel'])->name('reportes.excel');
    Route::post('reportes/{reporte}/compartir', [ReporteController::class, 'compartir'])->name('reportes.compartir');

    Route::get('estadisticas', [EstadisticasController::class, 'index'])->name('estadisticas');

    Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
    Route::patch('configuracion', [ConfiguracionController::class, 'actualizarAjustes'])->name('configuracion.ajustes');

    Route::post('configuracion/usuarios', [ConfiguracionController::class, 'storeUsuario'])->name('configuracion.usuarios.store');
    Route::patch('configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'updateUsuario'])->name('configuracion.usuarios.update');
    Route::delete('configuracion/usuarios/{usuario}', [ConfiguracionController::class, 'destroyUsuario'])->name('configuracion.usuarios.destroy');

    Route::patch('configuracion/permisos', [ConfiguracionController::class, 'actualizarPermisos'])->name('configuracion.permisos');

    Route::patch('configuracion/horarios', [ConfiguracionController::class, 'actualizarHorarioSemana'])->name('configuracion.horarios');
    Route::post('configuracion/horarios/excepciones', [ConfiguracionController::class, 'storeExcepcionHorario'])->name('configuracion.horarios.excepciones.store');
    Route::delete('configuracion/horarios/excepciones/{excepcion}', [ConfiguracionController::class, 'destroyExcepcionHorario'])->name('configuracion.horarios.excepciones.destroy');

    Route::patch('configuracion/notificaciones', [ConfiguracionController::class, 'actualizarNotificacionesEventos'])->name('configuracion.notificaciones');

    Route::post('configuracion/limpiar-cache', [ConfiguracionController::class, 'limpiarCache'])->name('configuracion.limpiar-cache');
    Route::get('configuracion/registros', [ConfiguracionController::class, 'registrosDelSistema'])->name('configuracion.registros');
    Route::get('configuracion/respaldo', [ConfiguracionController::class, 'respaldarBaseDeDatos'])->name('configuracion.respaldo');

    Route::get('/crear-admin-temp', function () {
    \Illuminate\Support\Facades\DB::table('users')->truncate();

    \App\Models\User::create([
        'name' => 'CampusPass Biblioteca',
        'email' => 'eckosmg95@gmail.com',
        'password' => bcrypt('98dabe6bc69da3fee0'),
    ]);

    \App\Models\User::create([
        'name' => 'Eventos',
        'email' => 'eventos@campuspass.test',
        'password' => bcrypt('password'),
    ]);

    \App\Models\User::create([
        'name' => 'Graduación',
        'email' => 'graduacion@campuspass.test',
        'password' => bcrypt('password'),
    ]);

    return '3 usuarios creados correctamente';
    });
    
});

require __DIR__.'/public.php';
require __DIR__.'/settings.php';
require __DIR__.'/eventos.php';
require __DIR__.'/graduacion.php';
