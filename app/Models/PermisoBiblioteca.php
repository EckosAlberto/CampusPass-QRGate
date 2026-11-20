<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'etiqueta'])]
class PermisoBiblioteca extends Model
{
    protected $table = 'permisos_biblioteca';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
