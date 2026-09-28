<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre',
    'tipo',
    'fk_id_ubicacion',
    'carrera',
    'semestre',
    'sexo',
    'tipo_movimiento',
    'fecha_inicial',
    'fecha_final',
    'registros',
    'datos',
    'fk_generado_por',
])]
class ReporteBiblioteca extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'reportes_biblioteca';

    protected function casts(): array
    {
        return [
            'fecha_inicial' => 'date',
            'fecha_final' => 'date',
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'fk_id_ubicacion');
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'fk_generado_por');
    }
}
