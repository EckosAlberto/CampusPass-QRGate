<?php

namespace Database\Seeders;

use App\Models\NotificacionEventoBiblioteca;
use Illuminate\Database\Seeder;

class NotificacionEventoBibliotecaSeeder extends Seeder
{
    private const VALORES_POR_DEFECTO = [
        NotificacionEventoBiblioteca::INCIDENCIA => ['correo' => true, 'push' => false],
        NotificacionEventoBiblioteca::REPORTE => ['correo' => true, 'push' => false],
        NotificacionEventoBiblioteca::ACCESO_DENEGADO => ['correo' => false, 'push' => false],
        NotificacionEventoBiblioteca::CAPACIDAD_MAXIMA => ['correo' => true, 'push' => false],
        NotificacionEventoBiblioteca::RESUMEN_DIARIO => ['correo' => false, 'push' => false],
    ];

    public function run(): void
    {
        foreach (self::VALORES_POR_DEFECTO as $evento => $valores) {
            NotificacionEventoBiblioteca::query()->firstOrCreate(['evento' => $evento], $valores);
        }
    }
}
