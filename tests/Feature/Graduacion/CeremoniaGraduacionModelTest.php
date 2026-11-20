<?php

use App\Models\BoletoGraduacion;

test('accesoPermitido evaluates against a given momento instead of always now', function () {
    
    $ceremonia = crearCeremoniaVigente([
        'fecha_inicio' => now()->subHours(10),
        'duracion_horas_acceso' => 5,
        'minutos_anticipados_graduados' => 0,
    ]);

    
    expect($ceremonia->accesoPermitido(BoletoGraduacion::TIPO_GRADUADO))->toBeFalse();

    $capturadoDentroDeLaVentana = now()->subHours(8);
    expect($ceremonia->accesoPermitido(BoletoGraduacion::TIPO_GRADUADO, $capturadoDentroDeLaVentana))->toBeTrue();

    
    $capturadoAntesDeAbrir = now()->subHours(11);
    expect($ceremonia->accesoPermitido(BoletoGraduacion::TIPO_GRADUADO, $capturadoAntesDeAbrir))->toBeFalse();
});
