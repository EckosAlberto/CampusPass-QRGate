<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable([
    'nombre',
    'fecha',
    'fecha_fin',
    'hora_inicio',
    'hora_fin',
    'horas',
    'fk_id_tipo_evento',
    'fk_id_periodo_tutorias',
    'fk_id_tutor',
    'estatus',
    'creado_por',
    'entrada_generada_en',
    'salida_generada_en',
])]
class EventoTutorias extends Model
{
    use HasUuids;

    public const MINUTOS_ANTES_PARA_HABILITAR_ENTRADA = 5;

    public const MINUTOS_ANTES_PARA_HABILITAR_SALIDA = 10;

    public const MINUTOS_DURACION_URL = 30;

    protected $table = 'evento_tutorias';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_fin' => 'date',
            'estatus' => 'boolean',
            'entrada_generada_en' => 'datetime',
            'salida_generada_en' => 'datetime',
        ];
    }

    public function estaVigenteHoy(): bool
    {
        if (! $this->estatus || ! $this->fecha || ! $this->fecha_fin) {
            return false;
        }

        return today()->between($this->fecha, $this->fecha_fin);
    }

    public function horarioPermitido(): bool
    {
        if (! $this->hora_inicio || ! $this->hora_fin) {
            return false;
        }

        $ahora = now()->format('H:i:s');

        return $ahora >= $this->hora_inicio && $ahora <= $this->hora_fin;
    }

    public function entradaHabilitada(): bool
    {
        if (! $this->estaVigenteHoy() || ! $this->fecha || ! $this->hora_inicio) {
            return false;
        }

        $inicio = Carbon::parse($this->fecha->format('Y-m-d').' '.$this->hora_inicio);

        return now()->greaterThanOrEqualTo($inicio->subMinutes(self::MINUTOS_ANTES_PARA_HABILITAR_ENTRADA));
    }

    public function salidaHabilitada(): bool
    {
        if (! $this->estaVigenteHoy() || ! $this->fecha_fin || ! $this->hora_fin) {
            return false;
        }

        $fin = Carbon::parse($this->fecha_fin->format('Y-m-d').' '.$this->hora_fin);

        return now()->greaterThanOrEqualTo($fin->subMinutes(self::MINUTOS_ANTES_PARA_HABILITAR_SALIDA));
    }

    public function urlEntradaVigente(): bool
    {
        return $this->entrada_generada_en !== null
            && now()->lessThan($this->entrada_generada_en->copy()->addMinutes(self::MINUTOS_DURACION_URL));
    }

    public function urlSalidaVigente(): bool
    {
        return $this->salida_generada_en !== null
            && now()->lessThan($this->salida_generada_en->copy()->addMinutes(self::MINUTOS_DURACION_URL));
    }

    public function tipoEvento()
    {
        return $this->belongsTo(TipoEvento::class, 'fk_id_tipo_evento');
    }

    public function periodoTutorias()
    {
        return $this->belongsTo(PeriodoTutorias::class, 'fk_id_periodo_tutorias');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'fk_id_tutor');
    }

    public function creador()
    {
        return $this->belongsTo(EventosUsuario::class, 'creado_por');
    }

    public function registrosAcceso()
    {
        return $this->hasMany(RegistroAcceso::class, 'id_evento');
    }
}
