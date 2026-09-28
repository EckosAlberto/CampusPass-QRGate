<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crearUbicacionBibliotecaParaEstadisticas(): Ubicacion
{
    return Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
}

function crearAlumnoParaEstadisticas(string $noDeControl, array $atributos = []): Alumno
{
    Carrera::firstOrCreate(
        ['carrera' => 'ISC', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Sistemas Computacionales', 'siglas' => 'ISC'],
    );

    return Alumno::create([
        'no_de_control' => $noDeControl,
        'carrera' => 'ISC',
        'reticula' => 1,
        'estatus_alumno' => Alumno::ESTATUS_ACTIVO,
        'semestre' => 4,
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'López',
        'nombre_alumno' => 'Alumno de prueba',
        'sexo' => 'H',
        ...$atributos,
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('estadisticas'));

    $response->assertRedirect(route('login'));
});

test('an authenticated user can view real statistics for the selected range', function () {
    $ubicacion = crearUbicacionBibliotecaParaEstadisticas();
    $hombre = crearAlumnoParaEstadisticas('0178542001', ['sexo' => 'H']);
    $mujer = crearAlumnoParaEstadisticas('0178542002', ['sexo' => 'M']);

    RegistroAcceso::create([
        'no_de_control' => $hombre->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(9, 0),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $mujer->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->setTime(10, 0),
    ]);

    RegistroAcceso::create([
        'no_de_control' => $mujer->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'SALIDA',
        'fecha_hora' => today()->setTime(12, 0),
    ]);

    $response = $this->actingAs(User::factory()->create())->get(route('estadisticas'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('panel/estadisticas')
        ->where('datos.resumen.hombres', 1)
        ->where('datos.resumen.mujeres', 1)
        ->where('datos.resumen.totalAlumnos', 2)
        ->where('datos.resumen.entradas', 2)
        ->where('datos.resumen.salidas', 1)
        ->where('datos.resumen.personasDentro', 1)
    );
});

test('filtering by carrera and semestre only counts matching accesos', function () {
    $ubicacion = crearUbicacionBibliotecaParaEstadisticas();
    $alumnoDentroDelFiltro = crearAlumnoParaEstadisticas('0178542003', ['semestre' => 4]);
    $alumnoFueraDelFiltro = crearAlumnoParaEstadisticas('0178542004', ['semestre' => 8]);

    foreach ([$alumnoDentroDelFiltro, $alumnoFueraDelFiltro] as $alumno) {
        RegistroAcceso::create([
            'no_de_control' => $alumno->no_de_control,
            'id_ubicacion' => $ubicacion->id,
            'tipo_movimiento' => 'ENTRADA',
            'fecha_hora' => today()->setTime(9, 0),
        ]);
    }

    $response = $this->actingAs(User::factory()->create())
        ->get(route('estadisticas', ['carrera' => 'ISC', 'semestre' => 4]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('datos.resumen.totalAlumnos', 1)
        ->where('datos.resumen.entradas', 1)
    );
});

test('accesos outside the selected date range are excluded', function () {
    $ubicacion = crearUbicacionBibliotecaParaEstadisticas();
    $alumno = crearAlumnoParaEstadisticas('0178542005');

    RegistroAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => today()->subDays(30)->setTime(9, 0),
    ]);

    $response = $this->actingAs(User::factory()->create())->get(route('estadisticas'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('datos.resumen.entradas', 0)
        ->where('datos.resumen.totalAlumnos', 0)
    );
});
