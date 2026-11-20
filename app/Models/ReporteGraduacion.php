<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre',
    'tipo',
    'fk_id_ceremonia',
    'carrera',
    'fecha_inicial',
    'fecha_final',
    'registros',
    'datos',
    'fk_generado_por',
])]
class ReporteGraduacion extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'reportes_graduacion';

    protected function casts(): array
    {
        return [
            'fecha_inicial' => 'date',
            'fecha_final' => 'date',
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ceremonia()
    {
        return $this->belongsTo(CeremoniaGraduacion::class, 'fk_id_ceremonia');
    }

    public function generadoPor()
    {
        return $this->belongsTo(GraduacionUsuario::class, 'fk_generado_por');
    }
}
