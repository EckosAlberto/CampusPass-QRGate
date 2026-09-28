<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fk_id_ceremonia', 'codigo', 'tipo', 'mensaje', 'fecha_hora'])]
class IncidenciaAccesoGraduacion extends Model
{
    use HasUuids;

    public const QR_INVALIDO = 'QR_INVALIDO';

    public const NO_REGISTRADO = 'NO_REGISTRADO';

    public const INACTIVO = 'INACTIVO';

    public const CEREMONIA_NO_VIGENTE = 'CEREMONIA_NO_VIGENTE';

    public const SIN_INVITACIONES = 'SIN_INVITACIONES';

    public const YA_UTILIZADO = 'YA_UTILIZADO';

    public const DOBLE_ESCANEO = 'DOBLE_ESCANEO';

    protected $table = 'incidencias_acceso_graduacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function ceremonia()
    {
        return $this->belongsTo(CeremoniaGraduacion::class, 'fk_id_ceremonia');
    }
}
