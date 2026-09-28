<?php

use App\Models\EventosUsuario;
use App\Models\EventoTutorias;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;

test('an eventos user can create an evento', function () {
    [$tipoEvento, $periodoTutorias, $tutor] = crearCatalogosDeEvento();
    $usuario = EventosUsuario::factory()->create();

    $response = $this->actingAs($usuario, 'eventos')
        ->post(route('eventos.eventos.store'), datosDeEvento($tipoEvento, $periodoTutorias, $tutor));

    $response->assertSessionHasNoErrors()->assertRedirect();

    $evento = EventoTutorias::query()->where('nombre', 'Sesión de prueba')->firstOrFail();
    expect($evento->estatus)->toBeTrue();
    expect($evento->creado_por)->toBe($usuario->id);
});

test('only the creator of an evento can delete it', function () {
    [$tipoEvento, $periodoTutorias, $tutor] = crearCatalogosDeEvento();
    $creador = EventosUsuario::factory()->create();
    $otroUsuario = EventosUsuario::factory()->create();

    $evento = EventoTutorias::create([
        ...datosDeEvento($tipoEvento, $periodoTutorias, $tutor),
        'estatus' => true,
        'creado_por' => $creador->id,
    ]);

    $this->actingAs($otroUsuario, 'eventos')
        ->delete(route('eventos.eventos.destroy', $evento))
        ->assertForbidden();

    expect(EventoTutorias::find($evento->id))->not->toBeNull();

    $this->actingAs($creador, 'eventos')
        ->delete(route('eventos.eventos.destroy', $evento))
        ->assertRedirect();

    expect(EventoTutorias::find($evento->id))->toBeNull();
});

test('an evento with registered accesos cannot be deleted', function () {
    [$tipoEvento, $periodoTutorias, $tutor] = crearCatalogosDeEvento();
    $creador = EventosUsuario::factory()->create();
    $alumno = crearAlumnoActivo();
    $ubicacion = Ubicacion::create(['nombre' => 'Auditorio', 'descripcion' => 'Auditorio principal', 'estatus' => true]);

    $evento = EventoTutorias::create([
        ...datosDeEvento($tipoEvento, $periodoTutorias, $tutor),
        'estatus' => true,
        'creado_por' => $creador->id,
    ]);

    RegistroAcceso::create([
        'no_de_control' => $alumno->no_de_control,
        'id_ubicacion' => $ubicacion->id,
        'id_evento' => $evento->id,
        'tipo_movimiento' => 'ENTRADA',
        'fecha_hora' => now(),
    ]);

    $response = $this->actingAs($creador, 'eventos')
        ->delete(route('eventos.eventos.destroy', $evento));

    $response->assertSessionHasErrors('evento');
    expect(EventoTutorias::find($evento->id))->not->toBeNull();
});

test('any eventos user can toggle the estatus of an evento', function () {
    [$tipoEvento, $periodoTutorias, $tutor] = crearCatalogosDeEvento();
    $creador = EventosUsuario::factory()->create();
    $otroUsuario = EventosUsuario::factory()->create();

    $evento = EventoTutorias::create([
        ...datosDeEvento($tipoEvento, $periodoTutorias, $tutor),
        'estatus' => true,
        'creado_por' => $creador->id,
    ]);

    $this->actingAs($otroUsuario, 'eventos')
        ->patch(route('eventos.eventos.estatus', $evento), ['estatus' => false])
        ->assertRedirect();

    expect($evento->fresh()->estatus)->toBeFalse();
});
