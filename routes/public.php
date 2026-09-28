<?php

use App\Http\Controllers\Public\BibliotecaController;
use App\Http\Controllers\Public\CeremoniaRegistroController;
use App\Http\Controllers\Public\EnlaceRemotoController;
use App\Http\Controllers\Public\EventoRegistroController;
use Illuminate\Support\Facades\Route;

Route::inertia('portal', 'portal')->name('portal');

// Rutas para la biblioteca
Route::prefix('biblioteca')->name('biblioteca.')->group(function () {
    Route::inertia('/', 'biblioteca/public')->name('index');
    Route::get('registro-de-acceso', [BibliotecaController::class, 'show'])->name('registro');
    Route::post('registro-de-acceso', [BibliotecaController::class, 'store'])->name('store');
});

// URL pública temporal
Route::match(['GET', 'POST'], 'eventos-academicos/registro/{evento}/{tipo}', [EventoRegistroController::class, 'handle'])
    ->where('tipo', 'entrada|salida')
    ->name('eventos.registro');

// URL pública temporal
Route::match(['GET', 'POST'], 'graduacion/registro/{ceremonia}', [CeremoniaRegistroController::class, 'handle'])
    ->name('graduacion.registro');

// Enlace público de consulta remota en tiempo real
Route::get('graduacion/enlace/{ceremonia}', [EnlaceRemotoController::class, 'show'])
    ->name('graduacion.enlace-remoto');
