<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearAlumnoDePruebaParaPersonasDentro(array $atributos = []): Alumno
{
    Carrera::firstOrCreate(
        ['carrera' => 'ISC', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Sistemas Computacionales', 'siglas' => 'ISC'],
    );

    return Alumno::create(array_merge([
        'no_de_control' => '0178542112',
        'carrera' => 'ISC',
        'reticula' => 1,
        'estatus_alumno' => Alumno::ESTATUS_ACTIVO,
        'semestre' => 8,
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'López',
        'nombre_alumno' => 'Juan',
        'sexo' => 'H',
    ], $atributos));
}

function crearUbicacionBibliotecaParaPersonasDentro(): Ubicacion
{
    return Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('personas-dentro'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit personas dentro with nobody inside', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('personas-dentro'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/personas-dentro')
        ->where('personas.data', [])
        ->where('personas.meta.total', 0)
        ->where('totalDentro', 0),
    );
});

test('lists only people whose last movement today is entrada', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaPersonasDentro();

    $juan = crearAlumnoDePruebaParaPersonasDentro();

    $maria = crearAlumnoDePruebaParaPersonasDentro([
        'no_de_control' => '0178542189',
        'apellido_paterno' => 'González',
        'apellido_materno' => 'Ruiz',
        'nombre_alumno' => 'María',
        'sexo' => 'M',
    ]);

    
    RegistroAcceso::create([
        'no_de_control' => $juan->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(13, 0),
    ]);

    
    RegistroAcceso::create([
        'no_de_control' => $maria->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(9, 0),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $maria->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'SALIDA',
        'fecha_hora' => today()->setTime(10, 0),
    ]);

    $response = $this->get(route('personas-dentro'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/personas-dentro')
        ->where('personas.meta.total', 1)
        ->where('totalDentro', 1)
        ->where('personas.data.0.noDeControl', $juan->no_de_control)
        ->where('personas.data.0.nombre', 'Juan Pérez López')
        ->where('personas.data.0.carrera', 'ISC')
        ->where('personas.data.0.semestre', 8)
        ->where('personas.data.0.estadoActual', 'Dentro'),
    );
});

test('filters personas dentro by carrera, semestre and search without affecting totalDentro', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBibliotecaParaPersonasDentro();

    $juan = crearAlumnoDePruebaParaPersonasDentro();

    Carrera::firstOrCreate(
        ['carrera' => 'IGE', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Gestión Empresarial', 'siglas' => 'IGE'],
    );

    $maria = crearAlumnoDePruebaParaPersonasDentro([
        'no_de_control' => '0178542189',
        'carrera' => 'IGE',
        'semestre' => 6,
        'apellido_paterno' => 'González',
        'apellido_materno' => 'Ruiz',
        'nombre_alumno' => 'María',
        'sexo' => 'M',
    ]);

    RegistroAcceso::create([
        'no_de_control' => $juan->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(13, 0),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $maria->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(13, 15),
    ]);

    $this->get(route('personas-dentro', ['carrera' => 'IGE']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('personas.meta.total', 1)
            ->where('totalDentro', 2)
            ->where('personas.data.0.noDeControl', $maria->no_de_control),
        );

    $this->get(route('personas-dentro', ['semestre' => 8]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('personas.meta.total', 1)
            ->where('personas.data.0.noDeControl', $juan->no_de_control),
        );

    $this->get(route('personas-dentro', ['buscar' => 'gonzález']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('personas.meta.total', 1)
            ->where('personas.data.0.noDeControl', $maria->no_de_control),
        );

    $this->get(route('personas-dentro', ['buscar' => $juan->no_de_control]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('personas.meta.total', 1)
            ->where('personas.data.0.noDeControl', $juan->no_de_control),
        );
});
