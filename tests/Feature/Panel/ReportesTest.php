<?php

use App\Mail\ReporteBibliotecaGenerado;
use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\ReporteBiblioteca;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function crearAlumnoDePruebaParaReportes(array $atributos = []): Alumno
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

function crearUbicacionBibliotecaParaReportes(): Ubicacion
{
    return Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
}

function sembrarAsistenciaBiblioteca(): void
{
    $ubicacion = crearUbicacionBibliotecaParaReportes();
    $alumno1 = crearAlumnoDePruebaParaReportes(['no_de_control' => '0178542112', 'sexo' => 'H']);
    $alumno2 = crearAlumnoDePruebaParaReportes(['no_de_control' => '0178542113', 'sexo' => 'M']);

    RegistroAcceso::create(['no_de_control' => $alumno1->no_de_control, 'id_ubicacion' => $ubicacion->id, 'tipo_movimiento' => 'ENTRADA', 'fecha_hora' => today()->setTime(9, 0)]);
    RegistroAcceso::create(['no_de_control' => $alumno2->no_de_control, 'id_ubicacion' => $ubicacion->id, 'tipo_movimiento' => 'ENTRADA', 'fecha_hora' => today()->setTime(9, 5)]);
    RegistroAcceso::create(['no_de_control' => $alumno1->no_de_control, 'id_ubicacion' => $ubicacion->id, 'tipo_movimiento' => 'SALIDA', 'fecha_hora' => today()->setTime(11, 0)]);
}

test('generating a report computes real indicators from registro_acceso', function () {
    sembrarAsistenciaBiblioteca();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();
    $response->assertRedirect(route('reportes.show', $reporte));

    expect($reporte->registros)->toBe(3);
    expect($reporte->datos['indicadores']['entradas'])->toBe(2);
    expect($reporte->datos['indicadores']['salidas'])->toBe(1);
    expect($reporte->datos['indicadores']['usuariosUnicos'])->toBe(2);
    expect($reporte->datos['indicadores']['hombres'])->toBe(1);
    expect($reporte->datos['indicadores']['mujeres'])->toBe(1);
});

test('the hombres/mujeres indicators and sexo filter also count personal, derived from their CURP', function () {
    $ubicacion = crearUbicacionBibliotecaParaReportes();
    $personal = Personal::create([
        'rfc' => 'MAGA900312416',
        'curp_empleado' => 'MAGA900312HNTRRL01',
        'nombre_empleado' => 'Alberto',
        'apellidos_empleado' => 'Martinez Garcia',
    ]);
    RegistroAcceso::create(['rfc_personal' => $personal->rfc, 'id_ubicacion' => $ubicacion->id, 'tipo_movimiento' => 'ENTRADA', 'fecha_hora' => today()->setTime(9, 30)]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();
    $response->assertRedirect(route('reportes.show', $reporte));

    expect($reporte->datos['indicadores']['hombres'])->toBe(1);
    expect($reporte->datos['indicadores']['mujeres'])->toBe(0);

    $reporteFiltrado = $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'sexo' => 'H',
        'tipo' => 'resumen_general',
    ]);

    $reporteFiltrado->assertRedirect();
    $ultimoReporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();
    expect($ultimoReporte->registros)->toBe(1);
});

test('the pdf download returns a real pdf document', function () {
    sembrarAsistenciaBiblioteca();
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();

    $response = $this->actingAs($usuario)->get(route('reportes.pdf', $reporte));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->streamedContent(), 0, 4))->toBe('%PDF');
});

test('the excel export returns a real xlsx document', function () {
    sembrarAsistenciaBiblioteca();
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();

    $response = $this->actingAs($usuario)->get(route('reportes.excel', $reporte));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(substr($response->streamedContent(), 0, 2))->toBe('PK');
});

test('sharing a report by email sends a real mailable with the pdf attached', function () {
    Mail::fake();

    sembrarAsistenciaBiblioteca();
    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();

    $this->actingAs($usuario)
        ->post(route('reportes.compartir', $reporte), ['correo' => 'coordinador@ittepic.edu.mx'])
        ->assertRedirect();

    Mail::assertSent(ReporteBibliotecaGenerado::class, fn ($mail) => $mail->hasTo('coordinador@ittepic.edu.mx'));
});

test('only the person who generated the report can delete it', function () {
    sembrarAsistenciaBiblioteca();
    $generador = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $this->actingAs($generador)->post(route('reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = ReporteBiblioteca::query()->latest('created_at')->firstOrFail();

    $this->actingAs($otroUsuario)
        ->delete(route('reportes.destroy', $reporte))
        ->assertForbidden();

    expect(ReporteBiblioteca::find($reporte->id))->not->toBeNull();

    $this->actingAs($generador)
        ->delete(route('reportes.destroy', $reporte))
        ->assertRedirect();

    expect(ReporteBiblioteca::find($reporte->id))->toBeNull();
});
