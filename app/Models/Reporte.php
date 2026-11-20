<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre',
    'tipo',
    'fk_id_evento',
    'carrera',
    'fecha_inicial',
    'fecha_final',
    'registros',
    'datos',
    'fk_generado_por',
])]
class Reporte extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'reportes';

    protected function casts(): array
    {
        return [
            'fecha_inicial' => 'date',
            'fecha_final' => 'date',
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function evento()
    {
        return $this->belongsTo(EventoTutorias::class, 'fk_id_evento');
    }

    public function generadoPor()
    {
        return $this->belongsTo(EventosUsuario::class, 'fk_generado_por');
    }
}
