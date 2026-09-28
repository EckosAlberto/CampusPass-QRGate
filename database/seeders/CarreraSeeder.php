<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            ['carrera' => 'ISC', 'reticula' => 2010, 'nombre_carrera' => 'Ingeniería en Sistemas Computacionales', 'nombre_reducido' => 'Sistemas', 'siglas' => 'ISC'],
            ['carrera' => 'IIN', 'reticula' => 2010, 'nombre_carrera' => 'Ingeniería Industrial', 'nombre_reducido' => 'Industrial', 'siglas' => 'IIN'],
            ['carrera' => 'LAE', 'reticula' => 2010, 'nombre_carrera' => 'Licenciatura en Administración', 'nombre_reducido' => 'Administración', 'siglas' => 'LAE'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::query()->updateOrCreate(
                ['carrera' => $carrera['carrera'], 'reticula' => $carrera['reticula']],
                $carrera,
            );
        }
    }
}
