<?php

namespace Database\Seeders;

use App\Models\GraduacionUsuario;
use App\Models\PermisoGraduacion;
use App\Models\RolGraduacion;
use App\Support\RolesInstitucionales;
use Illuminate\Database\Seeder;

class RolPermisoGraduacionSeeder extends Seeder
{
    private const PERMISOS = [
        'gestionar-ceremonias' => 'Gestionar ceremonias',
        'gestionar-boletos' => 'Generar QR para invitados',
        'ver-estadisticas' => 'Ver estadísticas',
        'generar-reportes' => 'Generar y exportar reportes',
        'configurar-sistema' => 'Configurar el sistema',
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $id => $etiqueta) {
            PermisoGraduacion::query()->updateOrCreate(['id' => $id], ['etiqueta' => $etiqueta]);
        }


        foreach (RolesInstitucionales::nombres() as $nombre) {
            RolGraduacion::query()->firstOrCreate(['nombre' => $nombre]);
        }

        $administrador = RolGraduacion::query()->where('nombre', RolesInstitucionales::CATALOGO[RolesInstitucionales::ADMINISTRADOR])->firstOrFail();
        $administrador->permisos()->sync(array_keys(self::PERMISOS));


        GraduacionUsuario::query()
            ->where('email', 'graduacion@campuspass.test')
            ->update(['rol_id' => $administrador->id, 'activo' => true]);

        RolGraduacion::query()->where('nombre', 'Coordinador')->delete();
    }
}
