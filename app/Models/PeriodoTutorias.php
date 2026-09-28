<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clave', 'fecha_inicio', 'fecha_fin', 'periodo', 'cierre'])]
class PeriodoTutorias extends Model
{
    use HasUuids;

    protected $table = 'periodo_tutorias';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'cierre' => 'boolean',
        ];
    }
}
