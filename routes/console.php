<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\CeremoniaGraduacion;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    CeremoniaGraduacion::where('estatus', true)
        ->where('fecha_fin', '<', now())
        ->update(['estatus' => false]);
})->everyMinute();