<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaCarrera;
use App\Models\CeremoniaGraduacion;
use App\Models\GraduacionUsuario;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CeremoniaGraduacionSeeder extends Seeder
{
    public function run(): void
    {
        $creador = GraduacionUsuario::query()->where('email', 'graduacion@campuspass.test')->firstOrFail();

        $folioExistente = CeremoniaGraduacion::query()->where('nombre', 'Ceremonia de graduación 20262')->value('folio');

        $ceremonia = CeremoniaGraduacion::query()->updateOrCreate(
            ['nombre' => 'Ceremonia de graduación 20262'],
            [
                'folio' => $folioExistente ?? ((int) (CeremoniaGraduacion::query()->max('folio')) + 1),
                'periodo' => '20251',
                'fecha_inicio' => today()->setTime(10, 0),
                'fecha_fin' => today()->setTime(13, 0),
                'tipo_acceso' => 'qr',
                'estatus' => true,
                'descripcion' => 'Ceremonia de graduación de generación 2026-2.',
                'minutos_anticipados_graduados' => 10,
                'minutos_anticipados_invitados' => 10,
                'duracion_horas_acceso' => 5,
                'invitados_por_defecto' => 2,
                'creado_por' => $creador->id,
            ],
        );

        foreach (['ISC', 'IIN', 'LAE'] as $carrera) {
            CeremoniaCarrera::query()->firstOrCreate([
                'fk_id_ceremonia' => $ceremonia->id,
                'carrera' => $carrera,
                'reticula' => 2010,
            ]);
        }

        $alumnos = Alumno::query()->whereIn('no_de_control', ['20010001', '20010002', '20010003'])->get();

        foreach ($alumnos as $alumno) {
            $graduado = BoletoGraduacion::query()->firstOrCreate(
                [
                    'fk_id_ceremonia' => $ceremonia->id,
                    'no_de_control' => $alumno->no_de_control,
                    'tipo' => BoletoGraduacion::TIPO_GRADUADO,
                ],
                [
                    'codigo' => Str::random(32),
                    'generado_en' => now(),
                ],
            );

            $invitado = BoletoGraduacion::query()->firstOrCreate(
                [
                    'fk_id_ceremonia' => $ceremonia->id,
                    'no_de_control' => $alumno->no_de_control,
                    'tipo' => BoletoGraduacion::TIPO_INVITADO,
                ],
                [
                    'codigo' => Str::random(32),
                    'invitados_autorizados' => $ceremonia->invitados_por_defecto,
                    'generado_en' => now(),
                ],
            );

            if ($alumno->no_de_control === '20010001') {
                RegistroAccesoGraduacion::query()->firstOrCreate([
                    'fk_id_boleto' => $graduado->id,
                    'fecha_hora' => today()->setTime(9, 55),
                ]);

                RegistroAccesoGraduacion::query()->firstOrCreate([
                    'fk_id_boleto' => $invitado->id,
                    'fecha_hora' => today()->setTime(9, 57),
                ]);
            }
        }
    }
}
