<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre'])]
class RolGraduacion extends Model
{
    use HasUuids;

    protected $table = 'roles_graduacion';

    public $timestamps = false;

    public function permisos()
    {
        return $this->belongsToMany(
            PermisoGraduacion::class,
            'rol_permiso_graduacion',
            'rol_id',
            'permiso_id',
        );
    }

    public static function porNombre(string $nombre): ?self
    {
        return self::query()->where('nombre', $nombre)->first();
    }
}
