<?php

namespace Database\Seeders;

use App\Models\TipoEvento;
use Illuminate\Database\Seeder;

class TipoEventoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['clave' => 'TUTIND', 'nombre' => 'Tutoría individual'],
            ['clave' => 'TUTGRP', 'nombre' => 'Tutoría grupal'],
            ['clave' => 'TALLER', 'nombre' => 'Taller académico'],
        ];

        foreach ($tipos as $tipo) {
            TipoEvento::query()->updateOrCreate(['clave' => $tipo['clave']], $tipo);
        }
    }
}
