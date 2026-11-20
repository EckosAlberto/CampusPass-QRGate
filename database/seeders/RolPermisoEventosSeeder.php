<?php

namespace Database\Seeders;

use App\Models\EventosUsuario;
use App\Models\PermisoEventos;
use App\Models\RolEventos;
use App\Support\RolesInstitucionales;
use Illuminate\Database\Seeder;

class RolPermisoEventosSeeder extends Seeder
{
    private const PERMISOS = [
        'ver-eventos' => 'Ver eventos',
        'gestionar-eventos' => 'Gestionar eventos (crear, editar, eliminar)',
        'generar-reportes' => 'Generar y exportar reportes',
        'configurar-sistema' => 'Configurar el sistema',
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $id => $etiqueta) {
            PermisoEventos::query()->updateOrCreate(['id' => $id], ['etiqueta' => $etiqueta]);
        }


        foreach (RolesInstitucionales::nombres() as $nombre) {
            RolEventos::query()->firstOrCreate(['nombre' => $nombre]);
        }

        $administrador = RolEventos::query()->where('nombre', RolesInstitucionales::CATALOGO[RolesInstitucionales::ADMINISTRADOR])->firstOrFail();
        $administrador->permisos()->sync(array_keys(self::PERMISOS));


        EventosUsuario::query()
            ->where('email', 'eventos@campuspass.test')
            ->update(['rol_id' => $administrador->id, 'activo' => true]);

        RolEventos::query()->where('nombre', 'Coordinador')->delete();
    }
}
