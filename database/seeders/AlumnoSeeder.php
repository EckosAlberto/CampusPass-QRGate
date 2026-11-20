<?php

namespace Database\Seeders;

use App\Models\Alumno;
use Illuminate\Database\Seeder;

class AlumnoSeeder extends Seeder
{
    public function run(): void
    {
        $alumnos = [
            ['no_de_control' => '20010001', 'carrera' => 'ISC', 'reticula' => 2010, 'semestre' => 6, 'apellido_paterno' => 'García', 'apellido_materno' => 'Pérez', 'nombre_alumno' => 'Luis', 'sexo' => 'H'],
            ['no_de_control' => '20010002', 'carrera' => 'ISC', 'reticula' => 2010, 'semestre' => 6, 'apellido_paterno' => 'Torres', 'apellido_materno' => 'Ramírez', 'nombre_alumno' => 'María', 'sexo' => 'M'],
            ['no_de_control' => '20010003', 'carrera' => 'IIN', 'reticula' => 2010, 'semestre' => 4, 'apellido_paterno' => 'Sánchez', 'apellido_materno' => 'Flores', 'nombre_alumno' => 'Jorge', 'sexo' => 'H'],
            ['no_de_control' => '20010004', 'carrera' => 'LAE', 'reticula' => 2010, 'semestre' => 8, 'apellido_paterno' => 'Ortiz', 'apellido_materno' => 'Cruz', 'nombre_alumno' => 'Daniela', 'sexo' => 'M'],
        ];

        foreach ($alumnos as $alumno) {
            Alumno::query()->updateOrCreate(
                ['no_de_control' => $alumno['no_de_control']],
                [...$alumno, 'estatus_alumno' => Alumno::ESTATUS_ACTIVO],
            );
        }
    }
}
