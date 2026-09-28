<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['no_de_control', 'id_ubicacion', 'id_evento', 'tipo', 'mensaje', 'fecha_hora'])]
class IncidenciaAcceso extends Model
{
    use HasUuids;

    public const QR_INVALIDO = 'QR_INVALIDO';

    public const NO_REGISTRADO = 'NO_REGISTRADO';

    public const INACTIVO = 'INACTIVO';

    public const DOBLE_ESCANEO = 'DOBLE_ESCANEO';

    protected $table = 'incidencias_acceso';

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

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }

    public function evento()
    {
        return $this->belongsTo(EventoTutorias::class, 'id_evento');
    }
}
