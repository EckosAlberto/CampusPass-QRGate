<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['evento', 'correo', 'push'])]
class NotificacionEventoBiblioteca extends Model
{
    public const INCIDENCIA = 'incidencia';

    public const REPORTE = 'reporte';

    public const ACCESO_DENEGADO = 'acceso-denegado';

    public const CAPACIDAD_MAXIMA = 'capacidad-maxima';

    public const RESUMEN_DIARIO = 'resumen-diario';

    protected $table = 'notificaciones_eventos_biblioteca';

    protected $primaryKey = 'evento';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'correo' => 'boolean',
            'push' => 'boolean',
        ];
    }
}
