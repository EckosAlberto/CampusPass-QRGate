<?php

namespace Database\Seeders;

use App\Models\PeriodoTutorias;
use App\Models\Tutor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TutorSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = PeriodoTutorias::query()->where('clave', 'Tutorías ENE-JUN/2025')->firstOrFail();
        $tipoTutorGrupal = DB::table('tipo_tutor')->where('clave', 'GRP')->value('id');

        $tutores = [
            ['clave_grupo' => 'ISC-2010-A', 'carrera' => 'ISC', 'reticula' => 2010, 'rfc' => 'HELC800101AB1'],
            ['clave_grupo' => 'IIN-2010-A', 'carrera' => 'IIN', 'reticula' => 2010, 'rfc' => 'ROMA850202CD2'],
        ];

        foreach ($tutores as $tutor) {
            Tutor::query()->updateOrCreate(
                ['clave_grupo' => $tutor['clave_grupo']],
                [
                    ...$tutor,
                    'fk_id_tipo_tutor' => $tipoTutorGrupal,
                    'fk_id_periodo_tutorias' => $periodo->id,
                ],
            );
        }
    }
}
