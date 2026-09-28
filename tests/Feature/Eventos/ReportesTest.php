<?php

use App\Mail\ReporteGenerado;
use App\Models\EventosUsuario;
use App\Models\RegistroAcceso;
use App\Models\Reporte;
use Illuminate\Support\Facades\Mail;

function sembrarAsistencia(): array
{
    $evento = crearEventoVigente();
    $alumno1 = crearAlumnoActivo('20010001');
    $alumno2 = crearAlumnoActivo('20010002');

    RegistroAcceso::create(['no_de_control' => $alumno1->no_de_control, 'id_evento' => $evento->id, 'tipo_movimiento' => 'ENTRADA', 'fecha_hora' => today()->setTime(9, 0)]);
    RegistroAcceso::create(['no_de_control' => $alumno2->no_de_control, 'id_evento' => $evento->id, 'tipo_movimiento' => 'ENTRADA', 'fecha_hora' => today()->setTime(9, 5)]);
    RegistroAcceso::create(['no_de_control' => $alumno1->no_de_control, 'id_evento' => $evento->id, 'tipo_movimiento' => 'SALIDA', 'fecha_hora' => today()->setTime(11, 0)]);

    return [$evento, $alumno1, $alumno2];
}

test('generating a report computes real indicators from registro_acceso', function () {
    [$evento] = sembrarAsistencia();
    $usuario = EventosUsuario::factory()->create();

    $response = $this->actingAs($usuario, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();
    $response->assertRedirect(route('eventos.reportes.show', $reporte));

    expect($reporte->registros)->toBe(3);
    expect($reporte->datos['indicadores']['asistentesRegistrados'])->toBe(2);
    expect($reporte->datos['indicadores']['entradas'])->toBe(2);
    expect($reporte->datos['indicadores']['salidas'])->toBe(1);
    expect($reporte->datos)->not->toHaveKey('detalle');
});

test('a detallado report includes the per-student detail rows', function () {
    sembrarAsistencia();
    $usuario = EventosUsuario::factory()->create();

    $this->actingAs($usuario, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'detallado',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();

    expect($reporte->datos['detalle'])->toHaveCount(3);
});

test('the pdf download returns a real pdf document', function () {
    sembrarAsistencia();
    $usuario = EventosUsuario::factory()->create();

    $this->actingAs($usuario, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();

    $response = $this->actingAs($usuario, 'eventos')->get(route('eventos.reportes.pdf', $reporte));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->streamedContent(), 0, 4))->toBe('%PDF');
});

test('the excel export returns a real xlsx document', function () {
    sembrarAsistencia();
    $usuario = EventosUsuario::factory()->create();

    $this->actingAs($usuario, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();

    $response = $this->actingAs($usuario, 'eventos')->get(route('eventos.reportes.excel', $reporte));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    
    expect(substr($response->streamedContent(), 0, 2))->toBe('PK');
});

test('sharing a report by email sends a real mailable with the pdf attached', function () {
    Mail::fake();

    sembrarAsistencia();
    $usuario = EventosUsuario::factory()->create();

    $this->actingAs($usuario, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();

    $this->actingAs($usuario, 'eventos')
        ->post(route('eventos.reportes.compartir', $reporte), ['correo' => 'coordinador@ittepic.edu.mx'])
        ->assertRedirect();

    Mail::assertSent(ReporteGenerado::class, fn ($mail) => $mail->hasTo('coordinador@ittepic.edu.mx'));
});

test('only the person who generated the report can delete it', function () {
    sembrarAsistencia();
    $generador = EventosUsuario::factory()->create();
    $otroUsuario = EventosUsuario::factory()->create();

    $this->actingAs($generador, 'eventos')->post(route('eventos.reportes.store'), [
        'fecha_inicial' => today()->toDateString(),
        'fecha_final' => today()->toDateString(),
        'tipo' => 'resumen_general',
    ]);

    $reporte = Reporte::query()->latest('created_at')->firstOrFail();

    $this->actingAs($otroUsuario, 'eventos')
        ->delete(route('eventos.reportes.destroy', $reporte))
        ->assertForbidden();

    expect(Reporte::find($reporte->id))->not->toBeNull();

    $this->actingAs($generador, 'eventos')
        ->delete(route('eventos.reportes.destroy', $reporte))
        ->assertRedirect();

    expect(Reporte::find($reporte->id))->toBeNull();
});
