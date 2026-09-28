<?php

namespace Database\Seeders;

use App\Models\Personal;
use Illuminate\Database\Seeder;

class PersonalSeeder extends Seeder
{
    public function run(): void
    {
        $personal = [
            ['rfc' => 'HELC800101AB1', 'curp_empleado' => 'HELC800101HDFRRN09', 'apellidos_empleado' => 'Hernández López', 'nombre_empleado' => 'Carlos Alberto'],
            ['rfc' => 'ROMA850202CD2', 'curp_empleado' => 'ROMA850202MDFDRR08', 'apellidos_empleado' => 'Rodríguez Martínez', 'nombre_empleado' => 'Ana María'],
        ];

        foreach ($personal as $registro) {
            Personal::query()->updateOrCreate(['rfc' => $registro['rfc']], $registro);
        }
    }
}
