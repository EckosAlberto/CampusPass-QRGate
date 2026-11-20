<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'etiqueta'])]
class PermisoEventos extends Model
{
    protected $table = 'permisos_eventos';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
