<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoEscolarSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('periodos_escolares')->insertOrIgnore([
            'periodo' => '20251',
            'identificacion_larga' => 'Enero - Junio 2025',
            'identificacion_corta' => 'ENE-JUN 25',
            'fecha_inicio' => '2025-01-15',
            'fecha_termino' => '2025-06-15',
        ]);
    }
}
