<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'descripcion', 'capacidad', 'estatus'])]
class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'estatus' => 'boolean',
        ];
    }
}
