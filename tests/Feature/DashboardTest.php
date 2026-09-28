<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('recent accesses show the name of personal, not just alumnos', function () {
    $biblioteca = Ubicacion::query()->create(['nombre' => 'Biblioteca']);

    $personal = Personal::query()->create([
        'rfc' => 'MAGA900312416',
        'curp_empleado' => 'MAGA900312HNTRRL01',
        'nombre_empleado' => 'Alberto',
        'apellidos_empleado' => 'Martinez Garcia',
    ]);

    RegistroAcceso::query()->create([
        'rfc_personal' => $personal->rfc,
        'id_ubicacion' => $biblioteca->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('accesosRecientes.0.nombre', 'Alberto Martinez Garcia')
        );
});

test('sex distribution counts personal (derived from their CURP) alongside alumnos', function () {
    $biblioteca = Ubicacion::query()->create(['nombre' => 'Biblioteca']);

    Carrera::firstOrCreate(
        ['carrera' => 'ISC', 'reticula' => 1],
        ['nombre_carrera' => 'Ingeniería en Sistemas Computacionales', 'siglas' => 'ISC'],
    );

    $alumna = Alumno::create([
        'no_de_control' => '0178542112',
        'carrera' => 'ISC',
        'reticula' => 1,
        'estatus_alumno' => Alumno::ESTATUS_ACTIVO,
        'semestre' => 8,
        'apellido_paterno' => 'Pérez',
        'apellido_materno' => 'López',
        'nombre_alumno' => 'Ana',
        'sexo' => 'M',
    ]);

    $personal = Personal::query()->create([
        'rfc' => 'MAGA900312416',
        'curp_empleado' => 'MAGA900312HNTRRL01',
        'nombre_empleado' => 'Alberto',
        'apellidos_empleado' => 'Martinez Garcia',
    ]);

    RegistroAcceso::query()->create([
        'no_de_control' => $alumna->no_de_control,
        'id_ubicacion' => $biblioteca->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => now(),
    ]);

    RegistroAcceso::query()->create([
        'rfc_personal' => $personal->rfc,
        'id_ubicacion' => $biblioteca->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => now(),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $pagina = json_decode(json_encode($response->viewData('page')), true);
    $distribucion = collect($pagina['props']['distribucionPorSexo'])
        ->pluck('total', 'sexo');

    expect($distribucion->get('Mujeres'))->toBe(1);
    expect($distribucion->get('Hombres'))->toBe(1);
});
