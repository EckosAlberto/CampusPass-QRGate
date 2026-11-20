<?php

use App\Mail\EventoUrlCompartida;
use App\Models\EventosUsuario;
use App\Models\EventoTutorias;
use Illuminate\Support\Facades\Mail;

function eventoConHorario(string $horaInicio, string $horaFin): EventoTutorias
{
    return crearEventoVigente([
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
    ]);
}

test('entrada cannot be generated more than 5 minutes before the event starts', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(9, 54));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada'])
        ->assertSessionHasErrors('tipo');

    expect($evento->fresh()->entrada_generada_en)->toBeNull();
});

test('entrada can be generated starting 5 minutes before the event starts', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(9, 55));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada'])
        ->assertSessionHasNoErrors();

    expect($evento->fresh()->entrada_generada_en)->not->toBeNull();
});

test('salida cannot be generated more than 10 minutes before the event ends', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(11, 49));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'salida'])
        ->assertSessionHasErrors('tipo');

    expect($evento->fresh()->salida_generada_en)->toBeNull();
});

test('salida can be generated starting 10 minutes before the event ends', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(11, 50));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'salida'])
        ->assertSessionHasNoErrors();

    expect($evento->fresh()->salida_generada_en)->not->toBeNull();
});

test('a generated url expires 30 minutes after it was generated', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(10, 0));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada']);

    $evento->refresh();
    expect($evento->urlEntradaVigente())->toBeTrue();

    $this->travelTo(today()->setTime(10, 29, 59));
    expect($evento->urlEntradaVigente())->toBeTrue();

    $this->travelTo(today()->setTime(10, 30, 1));
    expect($evento->urlEntradaVigente())->toBeFalse();
});

test('mis eventos index does not show an already expired url', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(10, 0));
    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada']);

    $this->travelTo(today()->setTime(10, 31));

    $response = $this->actingAs($creador, 'eventos')->get(route('eventos.mis-eventos.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('eventos.data.0.urlEntrada', null)
        ->where('eventos.data.0.entradaHabilitada', true),
    );
});

test('sharing a still-valid url by email sends a real mailable', function () {
    Mail::fake();

    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(10, 0));
    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada']);

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.compartir', $evento), ['tipo' => 'entrada', 'correo' => 'tutor@ittepic.edu.mx'])
        ->assertSessionHasNoErrors();

    Mail::assertSent(EventoUrlCompartida::class, fn ($mail) => $mail->hasTo('tutor@ittepic.edu.mx'));
});

test('sharing an already expired url fails with a clear error', function () {
    Mail::fake();

    $evento = eventoConHorario('10:00', '12:00');
    $creador = EventosUsuario::query()->find($evento->creado_por);

    $this->travelTo(today()->setTime(10, 0));
    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada']);

    $this->travelTo(today()->setTime(10, 40));

    $this->actingAs($creador, 'eventos')
        ->post(route('eventos.mis-eventos.compartir', $evento), ['tipo' => 'entrada', 'correo' => 'tutor@ittepic.edu.mx'])
        ->assertSessionHasErrors('tipo');

    Mail::assertNotSent(EventoUrlCompartida::class);
});

test('only the creator of the evento can generate or share its urls', function () {
    $evento = eventoConHorario('10:00', '12:00');
    $otroUsuario = EventosUsuario::factory()->create();

    $this->travelTo(today()->setTime(10, 0));

    $this->actingAs($otroUsuario, 'eventos')
        ->post(route('eventos.mis-eventos.generar-url', $evento), ['tipo' => 'entrada'])
        ->assertForbidden();

    $this->actingAs($otroUsuario, 'eventos')
        ->post(route('eventos.mis-eventos.compartir', $evento), ['tipo' => 'entrada', 'correo' => 'tutor@ittepic.edu.mx'])
        ->assertForbidden();
});
