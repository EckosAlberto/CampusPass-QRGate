<?php

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\IncidenciaAcceso;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Cache;

function crearAlumnoBibliotecaDePrueba(string $noDeControl = '20010001'): Alumno
{
    Carrera::firstOrCreate(
        ['carrera' => 'ISC', 'reticula' => 2010],
        ['nombre_carrera' => 'Ingeniería en Sistemas Computacionales'],
    );

    return Alumno::create([
        'no_de_control' => $noDeControl,
        'carrera' => 'ISC',
        'reticula' => 2010,
        'estatus_alumno' => Alumno::ESTATUS_ACTIVO,
        'nombre_alumno' => 'Luis',
        'apellido_paterno' => 'García',
        'apellido_materno' => 'Pérez',
    ]);
}

function crearPersonalDePrueba(string $rfc = 'HELC800101AB1'): Personal
{
    return Personal::create([
        'rfc' => $rfc,
        'curp_empleado' => 'HELC800101HNTRPR01',
        'nombre_empleado' => 'Carlos Alberto',
        'apellidos_empleado' => 'Hernández López',
    ]);
}

test('a valid alumno codigo registers an access', function () {
    $alumno = crearAlumnoBibliotecaDePrueba();

    $response = $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);

    $response->assertRedirect();

    $registro = RegistroAcceso::query()->where('no_de_control', $alumno->no_de_control)->firstOrFail();
    expect($registro->tipo_movimiento)->toBe('ENTRADA');
    expect($registro->rfc_personal)->toBeNull();
});

test('a valid personal rfc registers an access even without an alumno match', function () {
    $personal = crearPersonalDePrueba();

    $response = $this->post(route('biblioteca.store'), ['codigo' => $personal->rfc]);

    $response->assertRedirect();

    $registro = RegistroAcceso::query()->where('rfc_personal', $personal->rfc)->firstOrFail();
    expect($registro->tipo_movimiento)->toBe('ENTRADA');
    expect($registro->no_de_control)->toBeNull();
});

test('a personal rfc typed in lowercase still matches', function () {
    $personal = crearPersonalDePrueba();

    $response = $this->post(route('biblioteca.store'), ['codigo' => strtolower($personal->rfc)]);

    $response->assertRedirect();

    expect(RegistroAcceso::query()->where('rfc_personal', $personal->rfc)->exists())->toBeTrue();
});

test('an unknown code registers a no registrado incidencia and no access', function () {
    $response = $this->post(route('biblioteca.store'), ['codigo' => 'NOEXISTE1234']);

    $response->assertRedirect();

    expect(RegistroAcceso::query()->count())->toBe(0);

    $incidencia = IncidenciaAcceso::query()->firstOrFail();
    expect($incidencia->tipo)->toBe(IncidenciaAcceso::NO_REGISTRADO);
});

test('a first ever scan is always entrada, and each scan after toggles based on the last movement', function () {
    $alumno = crearAlumnoBibliotecaDePrueba();

    
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);
    $this->travel(10)->seconds();
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);
    $this->travel(10)->seconds();
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);
    $this->travel(10)->seconds();
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);

    $movimientos = RegistroAcceso::query()
        ->where('no_de_control', $alumno->no_de_control)
        ->orderBy('fecha_hora')
        ->pluck('tipo_movimiento')
        ->all();

    expect($movimientos)->toBe(['ENTRADA', 'SALIDA', 'ENTRADA', 'SALIDA']);
});

test('scanning the same codigo twice within the double-scan window is rejected, not toggled', function () {
    $alumno = crearAlumnoBibliotecaDePrueba();

    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);
    $this->travel(2)->seconds();
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);

    $registros = RegistroAcceso::query()->where('no_de_control', $alumno->no_de_control)->get();
    expect($registros)->toHaveCount(1);
    expect($registros->first()->tipo_movimiento)->toBe('ENTRADA');

    $incidencia = IncidenciaAcceso::query()->where('tipo', IncidenciaAcceso::DOBLE_ESCANEO)->firstOrFail();
    expect($incidencia->no_de_control)->toBe($alumno->no_de_control);
});

test('a scan is serialized against a concurrent scan of the same codigo, not racing it', function () {
    
    $alumno = crearAlumnoBibliotecaDePrueba();
    $lock = Cache::lock("registro-acceso:biblioteca:{$alumno->no_de_control}", 10);
    expect($lock->get())->toBeTrue();

    try {
        $response = $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);

        
        $response->assertRedirect();
        expect(RegistroAcceso::query()->where('no_de_control', $alumno->no_de_control)->count())->toBe(0);
    } finally {
        $lock->release();
    }
})->group('slow');

test('alumno and personal accesos on the same day are tracked independently, not merged', function () {
    $ubicacion = Ubicacion::firstOrCreate(
        ['nombre' => 'Biblioteca'],
        ['descripcion' => 'Control de acceso a la biblioteca', 'estatus' => true],
    );
    $alumno = crearAlumnoBibliotecaDePrueba();
    $personal = crearPersonalDePrueba();

    
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);
    $this->post(route('biblioteca.store'), ['codigo' => $personal->rfc]);

    expect(RegistroAcceso::where('id_ubicacion', $ubicacion->id)->where('tipo_movimiento', 'ENTRADA')->count())->toBe(2);

    
    $this->travel(10)->seconds();
    $this->post(route('biblioteca.store'), ['codigo' => $alumno->no_de_control]);

    $registroAlumno = RegistroAcceso::where('no_de_control', $alumno->no_de_control)->latest('fecha_hora')->first();
    $registroPersonal = RegistroAcceso::where('rfc_personal', $personal->rfc)->latest('fecha_hora')->first();

    expect($registroAlumno->tipo_movimiento)->toBe('SALIDA');
    expect($registroPersonal->tipo_movimiento)->toBe('ENTRADA');
});
