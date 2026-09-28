<?php

use App\Models\EventoTutorias;
use App\Models\IncidenciaAcceso;
use App\Models\RegistroAcceso;
use Illuminate\Support\Facades\URL;

function urlDeRegistro(EventoTutorias $evento, string $tipo): string
{
    return URL::temporarySignedRoute('eventos.registro', now()->addHour(), ['evento' => $evento->id, 'tipo' => $tipo]);
}

test('a valid signed url registers an entrada for an active student', function () {
    $evento = crearEventoVigente();
    $alumno = crearAlumnoActivo();

    $url = urlDeRegistro($evento, 'entrada');

    $response = $this->post($url, ['codigo' => $alumno->no_de_control]);

    $response->assertRedirect();

    $registro = RegistroAcceso::query()->where('id_evento', $evento->id)->firstOrFail();
    expect($registro->no_de_control)->toBe($alumno->no_de_control);
    expect($registro->tipo_movimiento)->toBe('ENTRADA');
});

test('an expired or tampered signature shows the enlace no disponible page', function () {
    $evento = crearEventoVigente();

    $response = $this->get(url('/eventos-academicos/registro/'.$evento->id.'/entrada'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('eventos/enlace-no-disponible'));
});

test('a nonexistent alumno registers an incidencia and no access', function () {
    $evento = crearEventoVigente();
    $url = urlDeRegistro($evento, 'entrada');

    $this->post($url, ['codigo' => '99999999'])->assertRedirect();

    expect(RegistroAcceso::query()->where('id_evento', $evento->id)->count())->toBe(0);

    $incidencia = IncidenciaAcceso::query()->where('id_evento', $evento->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAcceso::NO_REGISTRADO);
});

test('an inactive alumno is denied and logged as an incidencia', function () {
    $evento = crearEventoVigente();
    $alumno = crearAlumnoActivo();
    $alumno->update(['estatus_alumno' => 'BAJA']);

    $url = urlDeRegistro($evento, 'entrada');

    $this->post($url, ['codigo' => $alumno->no_de_control])->assertRedirect();

    expect(RegistroAcceso::query()->where('id_evento', $evento->id)->count())->toBe(0);

    $incidencia = IncidenciaAcceso::query()->where('id_evento', $evento->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAcceso::INACTIVO);
});

test('a finished evento shows the enlace no disponible page even with a valid signature', function () {
    $evento = crearEventoVigente(['estatus' => false]);

    $url = urlDeRegistro($evento, 'entrada');

    $response = $this->get($url);

    $response->assertInertia(fn ($page) => $page->component('eventos/enlace-no-disponible'));
});

test('a scan synced offline with capturado_en uses that as the real fecha_hora', function () {
    $evento = crearEventoVigente();
    $alumno = crearAlumnoActivo();
    $capturadoEn = now()->subMinutes(20);

    $url = urlDeRegistro($evento, 'entrada');

    $this->post($url, [
        'codigo' => $alumno->no_de_control,
        'capturado_en' => $capturadoEn->toIso8601String(),
    ])->assertRedirect();

    $registro = RegistroAcceso::query()->where('id_evento', $evento->id)->firstOrFail();
    expect($registro->fecha_hora->toDateTimeString())->toBe($capturadoEn->toDateTimeString());
});

test('the doble escaneo window is evaluated against capturado_en, not the sync time', function () {
    $evento = crearEventoVigente();
    $alumno = crearAlumnoActivo();
    $url = urlDeRegistro($evento, 'entrada');

    $primerEscaneo = now()->subHour();

    $this->post($url, [
        'codigo' => $alumno->no_de_control,
        'capturado_en' => $primerEscaneo->toIso8601String(),
    ])->assertRedirect();

    $this->post($url, [
        'codigo' => $alumno->no_de_control,
        'capturado_en' => $primerEscaneo->clone()->addSeconds(3)->toIso8601String(),
    ])->assertRedirect();

    expect(RegistroAcceso::query()->where('id_evento', $evento->id)->count())->toBe(1);

    $incidencia = IncidenciaAcceso::query()
        ->where('id_evento', $evento->id)
        ->where('tipo', IncidenciaAcceso::DOBLE_ESCANEO)
        ->first();
    expect($incidencia)->not->toBeNull();


    $this->post($url, [
        'codigo' => $alumno->no_de_control,
        'capturado_en' => $primerEscaneo->clone()->addSeconds(10)->toIso8601String(),
    ])->assertRedirect();

    expect(RegistroAcceso::query()->where('id_evento', $evento->id)->count())->toBe(2);
});
