<?php

namespace Database\Seeders;

use App\Models\EventosUsuario;
use App\Models\EventoTutorias;
use App\Models\PeriodoTutorias;
use App\Models\TipoEvento;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

class EventoTutoriasSeeder extends Seeder
{
    public function run(): void
    {
        $tipoEvento = TipoEvento::query()->where('clave', 'TUTGRP')->firstOrFail();
        $periodo = PeriodoTutorias::query()->where('clave', 'Tutorías ENE-JUN/2025')->firstOrFail();
        $tutor = Tutor::query()->where('clave_grupo', 'ISC-2010-A')->firstOrFail();
        $creador = EventosUsuario::query()->where('email', 'eventos@campuspass.test')->firstOrFail();

        $comunes = [
            'fk_id_tipo_evento' => $tipoEvento->id,
            'fk_id_periodo_tutorias' => $periodo->id,
            'fk_id_tutor' => $tutor->id,
            'creado_por' => $creador->id,
        ];

        EventoTutorias::query()->updateOrCreate(
            ['nombre' => 'Sesión grupal de seguimiento'],
            [
                ...$comunes,
                'fecha' => today(),
                'fecha_fin' => today(),
                'hora_inicio' => '09:00',
                'hora_fin' => '11:00',
                'horas' => 2,
                'estatus' => true,
            ],
        );

        EventoTutorias::query()->updateOrCreate(
            ['nombre' => 'Taller de hábitos de estudio'],
            [
                ...$comunes,
                'fecha' => today()->addWeek(),
                'fecha_fin' => today()->addWeek(),
                'hora_inicio' => '10:00',
                'hora_fin' => '12:00',
                'horas' => 2,
                'estatus' => true,
            ],
        );

        EventoTutorias::query()->updateOrCreate(
            ['nombre' => 'Sesión de cierre de periodo'],
            [
                ...$comunes,
                'fecha' => today()->subMonth(),
                'fecha_fin' => today()->subMonth(),
                'hora_inicio' => '09:00',
                'hora_fin' => '10:00',
                'horas' => 1,
                'estatus' => false,
            ],
        );
    }
}
