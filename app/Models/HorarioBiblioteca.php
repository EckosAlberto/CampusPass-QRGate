<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['dia', 'abierto', 'apertura', 'cierre'])]
class HorarioBiblioteca extends Model
{
    public const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    protected $table = 'horarios_biblioteca';

    protected $primaryKey = 'dia';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'abierto' => 'boolean',
        ];
    }
}
