<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'etiqueta'])]
class PermisoGraduacion extends Model
{
    protected $table = 'permisos_graduacion';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
