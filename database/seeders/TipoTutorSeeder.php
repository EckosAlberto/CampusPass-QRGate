<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TipoTutorSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['clave' => 'IND', 'nombre' => 'Tutor individual'],
            ['clave' => 'GRP', 'nombre' => 'Tutor grupal'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipo_tutor')->updateOrInsert(
                ['clave' => $tipo['clave']],
                ['id' => (string) Str::uuid(), 'nombre' => $tipo['nombre'], 'visible' => true],
            );
        }
    }
}
