<?php

namespace Database\Seeders;

use App\Models\HorarioBiblioteca;
use Illuminate\Database\Seeder;

class HorarioBibliotecaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HorarioBiblioteca::DIAS as $dia) {
            HorarioBiblioteca::query()->firstOrCreate(
                ['dia' => $dia],
                [
                    'abierto' => $dia !== 'Domingo',
                    'apertura' => $dia === 'Sábado' ? '09:00' : '08:00',
                    'cierre' => $dia === 'Sábado' ? '14:00' : '19:00',
                ],
            );
        }
    }
}
