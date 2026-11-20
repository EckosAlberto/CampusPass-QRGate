<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'no_de_control',
    'rfc_personal',
    'id_ubicacion',
    'id_evento',
    'tipo_movimiento',
    'fecha_hora',
    'observaciones',
])]
class RegistroAcceso extends Model
{
    use HasUuids;

    protected $table = 'registro_acceso';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'no_de_control', 'no_de_control');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'rfc_personal', 'rfc');
    }

    public function identificador(): ?string
    {
        return $this->no_de_control ?? $this->rfc_personal;
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }

    public function evento()
    {
        return $this->belongsTo(EventoTutorias::class, 'id_evento');
    }
}
