<?php

namespace App\Models;

use App\Support\RolesInstitucionales;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tipo_usuario', 'descripcion_tipo'])]
class TipoUsuario extends Model
{
    public const ADMINISTRADOR = RolesInstitucionales::ADMINISTRADOR;

    protected $table = 'tipos_usuario';

    protected $primaryKey = 'tipo_usuario';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function permisos()
    {
        return $this->belongsToMany(
            PermisoBiblioteca::class,
            'tipo_usuario_permiso',
            'tipo_usuario',
            'permiso_id',
            'tipo_usuario',
            'id',
        );
    }

    public static function porNombre(string $nombre): ?self
    {
        $codigo = RolesInstitucionales::codigoPorNombre($nombre);

        return $codigo ? self::query()->find($codigo) : null;
    }
}
