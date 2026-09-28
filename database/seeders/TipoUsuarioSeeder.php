<?php

namespace Database\Seeders;

use App\Models\TipoUsuario;
use App\Support\RolesInstitucionales;
use Illuminate\Database\Seeder;

class TipoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolesInstitucionales::CATALOGO as $clave => $descripcion) {
            TipoUsuario::query()->updateOrCreate(
                ['tipo_usuario' => $clave],
                ['descripcion_tipo' => $descripcion],
            );
        }

 
        TipoUsuario::query()
            ->whereNotIn('tipo_usuario', array_keys(RolesInstitucionales::CATALOGO))
            ->delete();
    }
}
