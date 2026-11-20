<?php

use App\Models\Alumno;
use App\Models\BoletoGraduacion;
use App\Models\Carrera;
use App\Models\CeremoniaGraduacion;
use App\Models\EventosUsuario;
use App\Models\EventoTutorias;
use App\Models\GraduacionUsuario;
use App\Models\PeriodoTutorias;
use App\Models\Personal;
use App\Models\TipoEvento;
use App\Models\TipoUsuario;
use App\Models\Tutor;
use App\Models\User;
use App\Support\RolesInstitucionales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;



pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');



expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});



/**
 * @return array{0: TipoEvento, 1: PeriodoTutorias, 2: Tutor}
 */
function crearCatalogosDeEvento(): array
{
    DB::table('periodos_escolares')->insert([
        'periodo' => '20251',
        'fecha_inicio' => '2025-01-15',
        'fecha_termino' => '2025-06-15',
    ]);

    $tipoEvento = TipoEvento::create(['clave' => 'TUTGRP', 'nombre' => 'Tutoría grupal']);

    $periodoTutorias = PeriodoTutorias::create([
        'clave' => 'Tutorías ENE-JUN/2025',
        'fecha_inicio' => '2025-01-15',
        'fecha_fin' => '2025-06-15',
        'periodo' => '20251',
        'cierre' => false,
    ]);

    Personal::create([
        'rfc' => 'HELC800101AB1',
        'curp_empleado' => 'HELC800101HDFRRN09',
        'apellidos_empleado' => 'Hernández López',
        'nombre_empleado' => 'Carlos',
    ]);

    $tipoTutorId = (string) Str::uuid();
    DB::table('tipo_tutor')->insert(['id' => $tipoTutorId, 'clave' => 'GRP', 'nombre' => 'Tutor grupal', 'visible' => true]);

    $tutor = Tutor::create([
        'clave_grupo' => 'ISC-2010-A',
        'fk_id_tipo_tutor' => $tipoTutorId,
        'fk_id_periodo_tutorias' => $periodoTutorias->id,
        'rfc' => 'HELC800101AB1',
    ]);

    return [$tipoEvento, $periodoTutorias, $tutor];
}

/**
 * @return array<string, mixed>
 */
function datosDeEvento(TipoEvento $tipoEvento, PeriodoTutorias $periodoTutorias, Tutor $tutor, array $overrides = []): array
{
    return [
        'nombre' => 'Sesión de prueba',
        'fecha' => '2026-09-10',
        'fecha_fin' => '2026-09-10',
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
        'horas' => 2,
        'fk_id_tipo_evento' => $tipoEvento->id,
        'fk_id_periodo_tutorias' => $periodoTutorias->id,
        'fk_id_tutor' => $tutor->id,
        ...$overrides,
    ];
}


function crearEventoVigente(array $overrides = []): EventoTutorias
{
    [$tipoEvento, $periodoTutorias, $tutor] = crearCatalogosDeEvento();
    $creador = EventosUsuario::factory()->create();

    return EventoTutorias::create(datosDeEvento($tipoEvento, $periodoTutorias, $tutor, [
        'fecha' => today()->toDateString(),
        'fecha_fin' => today()->toDateString(),
        'hora_inicio' => '00:00',
        'hora_fin' => '23:59',
        'estatus' => true,
        'creado_por' => $creador->id,
        ...$overrides,
    ]));
}


function crearAlumnoActivo(string $noDeControl = '20010001'): Alumno
{
    Carrera::query()->updateOrCreate(
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


function crearCeremoniaVigente(array $overrides = []): CeremoniaGraduacion
{
    $creador = GraduacionUsuario::factory()->create();

    return CeremoniaGraduacion::create([
        'nombre' => 'Ceremonia de prueba',
        'periodo' => null,
        'fecha_inicio' => now()->subHour(),
        'fecha_fin' => now()->addHours(2),
        'tipo_acceso' => 'qr',
        'estatus' => true,
        'minutos_anticipados_graduados' => 0,
        'minutos_anticipados_invitados' => 0,
        'duracion_horas_acceso' => 5,
        'invitados_por_defecto' => 2,
        'creado_por' => $creador->id,
        ...$overrides,
    ]);
}


function crearBoleto(CeremoniaGraduacion $ceremonia, Alumno $alumno, string $tipo = BoletoGraduacion::TIPO_GRADUADO, array $overrides = []): BoletoGraduacion
{
    return BoletoGraduacion::create([
        'fk_id_ceremonia' => $ceremonia->id,
        'no_de_control' => $alumno->no_de_control,
        'tipo' => $tipo,
        'codigo' => Str::random(32),
        'invitados_autorizados' => $tipo === BoletoGraduacion::TIPO_INVITADO ? 2 : null,
        'generado_en' => now(),
        ...$overrides,
    ]);
}


function crearTiposUsuarioBiblioteca(): void
{
    foreach (RolesInstitucionales::CATALOGO as $clave => $descripcion) {
        TipoUsuario::query()->firstOrCreate(
            ['tipo_usuario' => $clave],
            ['descripcion_tipo' => $descripcion],
        );
    }
}


function crearUsuarioBiblioteca(string $tipo = TipoUsuario::ADMINISTRADOR, array $overrides = []): User
{
    crearTiposUsuarioBiblioteca();

    return User::factory()->create([
        'tipo' => $tipo,
        'activo' => true,
        'password' => Hash::make('password'),
        ...$overrides,
    ]);
}
