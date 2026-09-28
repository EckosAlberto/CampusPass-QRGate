<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clave', 'nombre'])]
class TipoEvento extends Model
{
    use HasUuids;

    protected $table = 'tipo_evento';

    public $timestamps = false;
}
