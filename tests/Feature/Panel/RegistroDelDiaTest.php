<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearAlumnoDePrueba(array $atributos = []): Alumno
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

function crearUbicacionBiblioteca(): Ubicacion
{
    return Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('registro-del-dia'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit registro del dia with no records today', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('registro-del-dia'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/registro-del-dia')
        ->where('registros.data', [])
        ->where('registros.meta.total', 0),
    );
});

test('lists today accesses and computes tiempo de permanencia between entrada and salida', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBiblioteca();
    $alumno = crearAlumnoDePrueba();

    RegistroAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(8, 0),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'SALIDA',
        'fecha_hora' => today()->setTime(10, 25),
    ]);

    // Un registro de ayer no debe aparecer en "registros del día".
    RegistroAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->subDay()->setTime(9, 0),
    ]);

    $response = $this->get(route('registro-del-dia'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/registro-del-dia')
        ->where('registros.meta.total', 2)
        ->where('registros.data.0.movimiento', 'SALIDA')
        ->where('registros.data.0.tiempoPermanencia', '02:25:00')
        ->where('registros.data.0.nombre', 'Juan Pérez López')
        ->where('registros.data.0.carrera', 'ISC')
        ->where('registros.data.0.semestre', 8)
        ->where('registros.data.0.tipo', 'Estudiante')
        ->where('registros.data.1.movimiento', 'ENTRADA')
        ->where('registros.data.1.tiempoPermanencia', null),
    );
});

test('filters registros by movimiento, carrera, semestre, sexo and search', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBiblioteca();

    $juan = crearAlumnoDePrueba();

    Carrera::firstOrCreate(
        ['carrera' => 'IGE', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Gestión Empresarial', 'siglas' => 'IGE'],
    );

    $maria = crearAlumnoDePrueba([
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
        'fecha_hora' => today()->setTime(8, 5),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $maria->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(8, 12),
    ]);

    $this->get(route('registro-del-dia', ['movimiento' => 'ENTRADA', 'sexo' => 'M']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.noDeControl', $maria->no_de_control),
        );

    $this->get(route('registro-del-dia', ['carrera' => 'ISC']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.noDeControl', $juan->no_de_control),
        );

    $this->get(route('registro-del-dia', ['semestre' => 6]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.noDeControl', $maria->no_de_control),
        );

    $this->get(route('registro-del-dia', ['buscar' => 'gonzález']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.noDeControl', $maria->no_de_control),
        );

    $this->get(route('registro-del-dia', ['buscar' => $juan->no_de_control]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.noDeControl', $juan->no_de_control),
        );
});

test('the sexo column and filter also work for personal, derived from their CURP', function () {
    $this->actingAs(User::factory()->create());

    $ubicacion = crearUbicacionBiblioteca();

    $personal = Personal::create([
        'rfc' => 'MAGA900312416',
        'curp_empleado' => 'MAGA900312HNTRRL01',
        'nombre_empleado' => 'Alberto',
        'apellidos_empleado' => 'Martinez Garcia',
    ]);

    RegistroAcceso::create([
        'rfc_personal' => $personal->rfc,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(9, 37),
    ]);

    $this->get(route('registro-del-dia'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.data.0.sexo', 'H'),
        );

    $this->get(route('registro-del-dia', ['sexo' => 'H']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 1)
            ->where('registros.data.0.rfc', $personal->rfc),
        );

    $this->get(route('registro-del-dia', ['sexo' => 'M']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('registros.meta.total', 0),
        );
});
