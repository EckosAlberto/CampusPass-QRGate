<?php

use App\Models\EventosUsuario;
use App\Models\IncidenciaAcceso;
use App\Models\Ubicacion;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('eventos.incidencias.index'));

    $response->assertRedirect(route('eventos.login'));
});

test('does not list or count incidencias that belong to biblioteca', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    $evento = crearEventoVigente();

    $ubicacion = Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );

    IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    IncidenciaAcceso::create([
        'no_de_control' => '0178542113',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    $this->get(route('eventos.incidencias.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('eventos/incidencias')
            ->where('incidencias.meta.total', 1)
            ->where('incidencias.data.0.mensaje', 'De eventos'),
        );
});

test('deletes a single incidencia', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    $evento = crearEventoVigente();

    $incidencia = IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('eventos.incidencias.destroy', $incidencia))->assertRedirect();

    expect(IncidenciaAcceso::find($incidencia->id))->toBeNull();
});

test('cannot delete an incidencia that belongs to biblioteca from the eventos route', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    $incidencia = IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('eventos.incidencias.destroy', $incidencia))->assertNotFound();

    expect(IncidenciaAcceso::find($incidencia->id))->not->toBeNull();
});

test('deletes only the incidencias matching the current filters', function () {
    $usuario = EventosUsuario::factory()->create();
    $this->actingAs($usuario, 'eventos');

    $evento = crearEventoVigente();

    IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'Coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $noCoincide = IncidenciaAcceso::create([
        'no_de_control' => '0178542113',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'No coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $deBiblioteca = IncidenciaAcceso::create([
        'no_de_control' => '0178542114',
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'De biblioteca, no debe borrarse desde eventos',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('eventos.incidencias.destroy-todas'), ['tipo' => IncidenciaAcceso::QR_INVALIDO])
        ->assertRedirect();

    expect(IncidenciaAcceso::count())->toBe(2);
    expect(IncidenciaAcceso::find($noCoincide->id))->not->toBeNull();
    expect(IncidenciaAcceso::find($deBiblioteca->id))->not->toBeNull();
});
