<?php

use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\GraduacionUsuario;
use App\Models\RegistroAccesoGraduacion;

test('a ceremonia with registered accesos cannot be deleted', function () {
    $creador = GraduacionUsuario::factory()->create();
    $ceremonia = crearCeremoniaVigente(['creado_por' => $creador->id]);
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno);

    RegistroAccesoGraduacion::create([
        'fk_id_boleto' => $boleto->id,
        'fecha_hora' => now(),
    ]);

    $response = $this->actingAs($creador, 'graduacion')
        ->delete(route('graduacion.ceremonias.destroy', $ceremonia));

    $response->assertSessionHasErrors('ceremonia');
    expect(CeremoniaGraduacion::find($ceremonia->id))->not->toBeNull();
    expect(BoletoGraduacion::find($boleto->id))->not->toBeNull();
});

test('a ceremonia without registered accesos can be deleted by its creator', function () {
    $creador = GraduacionUsuario::factory()->create();
    $ceremonia = crearCeremoniaVigente(['creado_por' => $creador->id]);

    $response = $this->actingAs($creador, 'graduacion')
        ->delete(route('graduacion.ceremonias.destroy', $ceremonia));

    $response->assertRedirect();
    expect(CeremoniaGraduacion::find($ceremonia->id))->toBeNull();
});
