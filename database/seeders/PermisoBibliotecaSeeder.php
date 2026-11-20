<?php

namespace Database\Seeders;

use App\Models\PermisoBiblioteca;
use App\Models\TipoUsuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoBibliotecaSeeder extends Seeder
{
    private const PERMISOS = [
        'ver-accesos' => 'Ver registros de acceso',
        'gestionar-usuarios' => 'Gestionar usuarios y roles',
        'generar-reportes' => 'Generar y exportar reportes',
        'ver-estadisticas' => 'Ver estadísticas',
        'gestionar-incidencias' => 'Gestionar incidencias',
        'configurar-sistema' => 'Configurar el sistema',
        'respaldar-bd' => 'Respaldar y restaurar la base de datos',
    ];


    private const MATRIZ_POR_DEFECTO = [
        'ver-accesos' => [TipoUsuario::ADMINISTRADOR],
        'gestionar-usuarios' => [TipoUsuario::ADMINISTRADOR],
        'generar-reportes' => [TipoUsuario::ADMINISTRADOR],
        'ver-estadisticas' => [TipoUsuario::ADMINISTRADOR],
        'gestionar-incidencias' => [TipoUsuario::ADMINISTRADOR],
        'configurar-sistema' => [TipoUsuario::ADMINISTRADOR],
        'respaldar-bd' => [TipoUsuario::ADMINISTRADOR],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $id => $etiqueta) {
            PermisoBiblioteca::query()->updateOrCreate(['id' => $id], ['etiqueta' => $etiqueta]);
        }

        foreach (self::MATRIZ_POR_DEFECTO as $permisoId => $tipos) {
            foreach ($tipos as $tipo) {
                DB::table('tipo_usuario_permiso')->updateOrInsert([
                    'tipo_usuario' => $tipo,
                    'permiso_id' => $permisoId,
                ]);
            }
        }
    }
}
