<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'no_de_control',
    'carrera',
    'reticula',
    'estatus_alumno',
    'semestre',
    'apellido_paterno',
    'apellido_materno',
    'nombre_alumno',
    'curp_alumno',
    'fecha_nacimiento',
    'sexo',
])]
class Alumno extends Model
{
    public const ESTATUS_ACTIVO = 'ACT';

    protected $table = 'alumnos';

    protected $primaryKey = 'no_de_control';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre_alumno} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function estaActivo(): bool
    {
        return $this->estatus_alumno === self::ESTATUS_ACTIVO;
    }

    public function nombreCarrera(): ?string
    {
        return Carrera::query()
            ->where('carrera', $this->carrera)
            ->where('reticula', $this->reticula)
            ->value('nombre_carrera');
    }

    public function siglasCarrera(): ?string
    {
        return Carrera::query()
            ->where('carrera', $this->carrera)
            ->where('reticula', $this->reticula)
            ->value('siglas');
    }
}
