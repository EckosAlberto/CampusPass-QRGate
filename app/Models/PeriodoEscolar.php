<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['periodo', 'identificacion_larga', 'identificacion_corta', 'fecha_inicio', 'fecha_termino'])]
class PeriodoEscolar extends Model
{
    protected $table = 'periodos_escolares';

    protected $primaryKey = 'periodo';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_termino' => 'date',
        ];
    }
}
