<?php

namespace Database\Seeders;

use App\Models\PeriodoTutorias;
use Illuminate\Database\Seeder;

class PeriodoTutoriasSeeder extends Seeder
{
    public function run(): void
    {
        PeriodoTutorias::query()->updateOrCreate(
            ['clave' => 'Tutorías ENE-JUN/2025'],
            [
                'fecha_inicio' => '2025-01-15',
                'fecha_fin' => '2025-06-15',
                'periodo' => '20251',
                'cierre' => false,
            ],
        );
    }
}
