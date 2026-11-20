<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['carrera', 'reticula', 'nombre_carrera', 'nombre_reducido', 'siglas'])]
class Carrera extends Model
{
    protected $table = 'carreras';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'carrera' => 'string',
        ];
    }
}
