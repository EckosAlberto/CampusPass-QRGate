<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curp', 'hash_foto', 'fecha_registro', 'estatus', 'foto_path'])]
class IdentificadorFoto extends Model
{
    protected $table = 'identificador_fotos';

    protected $primaryKey = 'id_unico';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'estatus' => 'boolean',
        ];
    }

    public function idFormateado(): string
    {
        return 'IDU-'.str_pad((string) $this->id_unico, 5, '0', STR_PAD_LEFT);
    }
}
