<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\IncidenciaAcceso;
use App\Models\Ubicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearUbicacionBibliotecaParaIncidencias(): Ubicacion
{
    return Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('incidencias'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit incidencias with none registered', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('incidencias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/incidencias')
        ->where('incidencias.data', [])
        ->where('incidencias.meta.total', 0),
    );
});

test('lists incidencias ordered by most recent, resolving the alumno name when it exists', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaIncidencias();

    Carrera::firstOrCreate(
        ['carrera' => 'ISC', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Sistemas Computacionales', 'siglas' => 'ISC'],
    );

    $alumno = Alumno::create([
        'no_de_control' => '0178542112',
        'carrera' => 'ISC',
        'reticula' => 1,
        'estatus_alumno' => 'BAJ',
        'semestre' => 8,
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'López',
        'nombre_alumno' => 'Juan',
        'sexo' => 'H',
    ]);

    IncidenciaAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::INACTIVO,
        'mensaje' => "Estudiante con estatus 'BAJ' intentó acceder",
        'fecha_hora' => now()->subMinute(),
    ]);

    IncidenciaAcceso::create([
        'no_de_control' => 'https://qr',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'Código escaneado con formato inválido',
        'fecha_hora' => now(),
    ]);

    $response = $this->get(route('incidencias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/incidencias')
        ->where('incidencias.meta.total', 2)
        ->where('incidencias.data.0.tipo', IncidenciaAcceso::QR_INVALIDO)
        ->where('incidencias.data.0.nombre', null)
        ->where('incidencias.data.1.tipo', IncidenciaAcceso::INACTIVO)
        ->where('incidencias.data.1.nombre', 'Juan Pérez López'),
    );
});

test('filters incidencias by tipo and search term', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaIncidencias();

    IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'Número de control no encontrado',
        'fecha_hora' => now(),
    ]);

    IncidenciaAcceso::create([
        'no_de_control' => 'https://qr',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'Código escaneado con formato inválido',
        'fecha_hora' => now(),
    ]);

    $this->get(route('incidencias', ['tipo' => IncidenciaAcceso::QR_INVALIDO]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('incidencias.meta.total', 1)
            ->where('incidencias.data.0.tipo', IncidenciaAcceso::QR_INVALIDO),
        );

    $this->get(route('incidencias', ['buscar' => '0178542112']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('incidencias.meta.total', 1)
            ->where('incidencias.data.0.noDeControl', '0178542112'),
        );
});

test('does not list or count incidencias that belong to an evento', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaIncidencias();
    $evento = crearEventoVigente();

    IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    IncidenciaAcceso::create([
        'no_de_control' => '0178542113',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    $this->get(route('incidencias'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('incidencias.meta.total', 1)
            ->where('incidencias.data.0.mensaje', 'De biblioteca'),
        );
});

test('deletes a single incidencia', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaIncidencias();

    $incidencia = IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De biblioteca',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('incidencias.destroy', $incidencia))->assertRedirect();

    expect(IncidenciaAcceso::find($incidencia->id))->toBeNull();
});

test('cannot delete an incidencia that belongs to an evento from the biblioteca route', function () {
    $this->actingAs(User::factory()->create());

    $evento = crearEventoVigente();

    $incidencia = IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'De eventos',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('incidencias.destroy', $incidencia))->assertNotFound();

    expect(IncidenciaAcceso::find($incidencia->id))->not->toBeNull();
});

test('deletes only the incidencias matching the current filters', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaIncidencias();
    $evento = crearEventoVigente();

    IncidenciaAcceso::create([
        'no_de_control' => '0178542112',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'Coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $noCoincide = IncidenciaAcceso::create([
        'no_de_control' => '0178542113',
        'id_ubicacion' => $ubicacion->id,
        'tipo' => IncidenciaAcceso::NO_REGISTRADO,
        'mensaje' => 'No coincide con el filtro',
        'fecha_hora' => now(),
    ]);

    $deEventos = IncidenciaAcceso::create([
        'no_de_control' => '0178542114',
        'id_evento' => $evento->id,
        'tipo' => IncidenciaAcceso::QR_INVALIDO,
        'mensaje' => 'De eventos, no debe borrarse desde biblioteca',
        'fecha_hora' => now(),
    ]);

    $this->delete(route('incidencias.destroy-todas'), ['tipo' => IncidenciaAcceso::QR_INVALIDO])
        ->assertRedirect();

    expect(IncidenciaAcceso::count())->toBe(2);
    expect(IncidenciaAcceso::find($noCoincide->id))->not->toBeNull();
    expect(IncidenciaAcceso::find($deEventos->id))->not->toBeNull();
});
