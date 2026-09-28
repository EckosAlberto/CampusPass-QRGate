<?php

use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\IncidenciaAccesoGraduacion;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

function urlDeRegistroGraduacion(CeremoniaGraduacion $ceremonia): string
{
    return URL::temporarySignedRoute('graduacion.registro', now()->addHour(), ['ceremonia' => $ceremonia->id]);
}

test('a valid graduado boleto registers an access', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_GRADUADO);

    $response = $this->post(urlDeRegistroGraduacion($ceremonia), ['codigo' => $boleto->codigo]);

    $response->assertRedirect();

    $registro = RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->firstOrFail();
    expect($registro)->not->toBeNull();
});

test('a graduado boleto cannot be scanned twice', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_GRADUADO);

    $url = urlDeRegistroGraduacion($ceremonia);
    $this->post($url, ['codigo' => $boleto->codigo]);
    $this->travel(4)->seconds();
    $this->post($url, ['codigo' => $boleto->codigo]);

    expect(RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->count())->toBe(1);

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::YA_UTILIZADO);
});

test('an invitado boleto can be scanned up to its authorized guest count', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_INVITADO, ['invitados_autorizados' => 2]);

    $url = urlDeRegistroGraduacion($ceremonia);
    $this->post($url, ['codigo' => $boleto->codigo]);
    $this->travel(4)->seconds();
    $this->post($url, ['codigo' => $boleto->codigo]);
    $this->travel(4)->seconds();
    $this->post($url, ['codigo' => $boleto->codigo]);

    expect(RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->count())->toBe(2);

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::SIN_INVITACIONES);
});

test('scanning the same boleto twice within 3 seconds is rejected as a double scan', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_GRADUADO);

    $url = urlDeRegistroGraduacion($ceremonia);
    $this->post($url, ['codigo' => $boleto->codigo]);
    $this->travel(2)->seconds();
    $this->post($url, ['codigo' => $boleto->codigo]);

    $registros = RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->get();
    expect($registros)->toHaveCount(1);

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::DOBLE_ESCANEO);
});

test('a scan is serialized against a concurrent scan of the same boleto, not racing it', function () {

    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_INVITADO, ['invitados_autorizados' => 2]);

    $lock = Cache::lock("registro-acceso:graduacion:{$boleto->id}", 10);
    expect($lock->get())->toBeTrue();

    try {
        $response = $this->post(urlDeRegistroGraduacion($ceremonia), ['codigo' => $boleto->codigo]);

        $response->assertRedirect();
        expect(RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->count())->toBe(0);
    } finally {
        $lock->release();
    }
})->group('slow');

test('an invalid code registers an incidencia and no access', function () {
    $ceremonia = crearCeremoniaVigente();

    $this->post(urlDeRegistroGraduacion($ceremonia), ['codigo' => 'no-existe'])->assertRedirect();

    expect(RegistroAccesoGraduacion::query()->count())->toBe(0);

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::QR_INVALIDO);
});

test('an unknown but well-formatted code registers as no registrado', function () {
    $ceremonia = crearCeremoniaVigente();

    $this->post(urlDeRegistroGraduacion($ceremonia), ['codigo' => str_repeat('a', 32)])->assertRedirect();

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::NO_REGISTRADO);
});

test('an inactive alumno is denied and logged as an incidencia', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $alumno->update(['estatus_alumno' => 'BAJA']);
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_GRADUADO);

    $this->post(urlDeRegistroGraduacion($ceremonia), ['codigo' => $boleto->codigo])->assertRedirect();

    expect(RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->count())->toBe(0);

    $incidencia = IncidenciaAccesoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAccesoGraduacion::INACTIVO);
});

test('an expired or tampered signature shows the enlace no disponible page', function () {
    $ceremonia = crearCeremoniaVigente();

    $response = $this->get(url('/graduacion/registro/'.$ceremonia->id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('graduacion/enlace-no-disponible'));
});

test('a finished ceremonia shows the enlace no disponible page even with a valid signature', function () {
    $ceremonia = crearCeremoniaVigente(['estatus' => false]);

    $response = $this->get(urlDeRegistroGraduacion($ceremonia));

    $response->assertInertia(fn ($page) => $page->component('graduacion/enlace-no-disponible'));
});

test('a scan synced offline with capturado_en uses that as the real fecha_hora', function () {
    $ceremonia = crearCeremoniaVigente();
    $alumno = crearAlumnoActivo();
    $boleto = crearBoleto($ceremonia, $alumno, BoletoGraduacion::TIPO_GRADUADO);
    $capturadoEn = now()->subMinutes(20);

    $this->post(urlDeRegistroGraduacion($ceremonia), [
        'codigo' => $boleto->codigo,
        'capturado_en' => $capturadoEn->toIso8601String(),
    ])->assertRedirect();

    $registro = RegistroAccesoGraduacion::query()->where('fk_id_boleto', $boleto->id)->firstOrFail();
    expect($registro->fecha_hora->toDateTimeString())->toBe($capturadoEn->toDateTimeString());
});
