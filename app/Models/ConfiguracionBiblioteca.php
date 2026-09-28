<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre_sistema',
    'institucion',
    'nombre_biblioteca',
    'capacidad_maxima',
    'horario_apertura',
    'horario_cierre',
    'qr_activo',
    'qr_validez_minutos',
    'qr_permitir_reingreso',
    'qr_notificar_correo',
    'notif_incidencias',
    'notif_reportes',
    'notif_resumen_diario',
    'notif_correo',
    'sesion_inactividad_minutos',
    'sesion_cerrar_auto',
    'sesion_mantener_activa',
    'zona_horaria',
    'idioma',
    'formato_fecha',
    'nivel_registro',
    'modo_mantenimiento',
    'canal_correo_activo',
    'canal_push_activo',
])]
class ConfiguracionBiblioteca extends Model
{
    protected $table = 'configuracion_biblioteca';

    protected function casts(): array
    {
        return [
            'qr_activo' => 'boolean',
            'qr_permitir_reingreso' => 'boolean',
            'qr_notificar_correo' => 'boolean',
            'notif_incidencias' => 'boolean',
            'notif_reportes' => 'boolean',
            'notif_resumen_diario' => 'boolean',
            'notif_correo' => 'boolean',
            'sesion_cerrar_auto' => 'boolean',
            'sesion_mantener_activa' => 'boolean',
            'modo_mantenimiento' => 'boolean',
            'canal_correo_activo' => 'boolean',
            'canal_push_activo' => 'boolean',
        ];
    }

    public static function actual(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }
}
