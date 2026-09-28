<?php

use App\Models\GraduacionUsuario;
use App\Models\IncidenciaAccesoGraduacion;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('graduacion.incidencias.index'));

    $response->assertRedirect(route('graduacion.login'));
});

test('lists incidencias with the ceremonia name resolved', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $this->actingAs($usuario, 'graduacion');

    $ceremonia = crearCeremoniaVigente();

    IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'no-existe',
        'tipo' => IncidenciaAccesoGraduacion::NO_REGISTRADO,
        'mensaje' => 'Código no encontrado',
        'fecha_hora' => now(),
    ]);

    $this->get(route('graduacion.incidencias.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('graduacion/incidencias')
            ->where('incidencias.meta.total', 1)
            ->where('incidencias.data.0.codigo', 'no-existe')
            ->where('incidencias.data.0.ceremonia', $ceremonia->nombre),
        );
});

test('deletes a single incidencia', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $this->actingAs($usuario, 'graduacion');

    $ceremonia = crearCeremoniaVigente();

    $incidencia = IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'no-existe',
        'tipo' => IncidenciaAccesoGraduacion::NO_REGISTRADO,
        'mensaje' => 'Código no encontrado',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('graduacion.incidencias.destroy', $incidencia))->assertRedirect();

    expect(IncidenciaAccesoGraduacion::find($incidencia->id))->toBeNull();
});

test('deletes only the incidencias matching the current filters', function () {
    $usuario = GraduacionUsuario::factory()->create();
    $this->actingAs($usuario, 'graduacion');

    $ceremonia = crearCeremoniaVigente();

    IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'a',
        'tipo' => IncidenciaAccesoGraduacion::QR_INVALIDO,
        'mensaje' => 'Coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $noCoincide = IncidenciaAccesoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'codigo' => 'b',
        'tipo' => IncidenciaAccesoGraduacion::NO_REGISTRADO,
        'mensaje' => 'No coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('graduacion.incidencias.destroy-todas'), ['tipo' => IncidenciaAccesoGraduacion::QR_INVALIDO])
        ->assertRedirect();

    expect(IncidenciaAccesoGraduacion::count())->toBe(1);
    expect(IncidenciaAccesoGraduacion::find($noCoincide->id))->not->toBeNull();
});
