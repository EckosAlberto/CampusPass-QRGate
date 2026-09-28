<?php

namespace Database\Seeders;

use App\Models\EventoTutorias;
use App\Models\RegistroAcceso;
use Illuminate\Database\Seeder;

class RegistroAccesoSeeder extends Seeder
{
    public function run(): void
    {
        $evento = EventoTutorias::query()->where('nombre', 'Sesión grupal de seguimiento')->firstOrFail();

        $movimientos = [
            ['no_de_control' => '20010001', 'tipo_movimiento' => 'ENTRADA', 'hora' => '09:02'],
            ['no_de_control' => '20010002', 'tipo_movimiento' => 'ENTRADA', 'hora' => '09:03'],
            ['no_de_control' => '20010003', 'tipo_movimiento' => 'ENTRADA', 'hora' => '09:05'],
            ['no_de_control' => '20010001', 'tipo_movimiento' => 'SALIDA', 'hora' => '10:58'],
        ];

        foreach ($movimientos as $movimiento) {
            RegistroAcceso::query()->firstOrCreate([
                'no_de_control' => $movimiento['no_de_control'],
                'id_evento' => $evento->id,
                'tipo_movimiento' => $movimiento['tipo_movimiento'],
                'fecha_hora' => today()->setTimeFromTimeString($movimiento['hora']),
            ]);
        }
    }
}
