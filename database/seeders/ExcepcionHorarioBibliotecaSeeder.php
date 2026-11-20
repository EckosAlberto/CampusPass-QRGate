<?php

namespace Database\Seeders;

use App\Models\ExcepcionHorarioBiblioteca;
use Illuminate\Database\Seeder;

class ExcepcionHorarioBibliotecaSeeder extends Seeder
{
    public function run(): void
    {
        $anio = today()->year;

        $excepciones = [
            ['fecha' => "{$anio}-01-01", 'motivo' => 'Año Nuevo', 'cerrado_todo_dia' => true],
            ['fecha' => "{$anio}-09-16", 'motivo' => 'Día de la Independencia', 'cerrado_todo_dia' => true],
            ['fecha' => "{$anio}-11-02", 'motivo' => 'Día de Muertos', 'cerrado_todo_dia' => false, 'horario_apertura' => '10:00', 'horario_cierre' => '14:00'],
        ];

        foreach ($excepciones as $excepcion) {
            ExcepcionHorarioBiblioteca::query()->firstOrCreate(
                ['fecha' => $excepcion['fecha'], 'motivo' => $excepcion['motivo']],
                $excepcion,
            );
        }
    }
}
