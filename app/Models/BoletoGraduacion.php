<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'fk_id_ceremonia',
    'no_de_control',
    'tipo',
    'codigo',
    'invitados_autorizados',
    'generado_en',
])]
class BoletoGraduacion extends Model
{
    use HasUuids;

    public const TIPO_GRADUADO = 'graduado';

    public const TIPO_INVITADO = 'invitado';

    protected $table = 'boletos_graduacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'generado_en' => 'datetime',
        ];
    }

    public function ceremonia()
    {
        return $this->belongsTo(CeremoniaGraduacion::class, 'fk_id_ceremonia');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'no_de_control', 'no_de_control');
    }

    public function registros()
    {
        return $this->hasMany(RegistroAccesoGraduacion::class, 'fk_id_boleto');
    }

    public function invitadosRegistrados(): int
    {
        return $this->registros()->count();
    }

    public function invitacionesDisponibles(): bool
    {
        if ($this->tipo !== self::TIPO_INVITADO) {
            return true;
        }

        return $this->invitadosRegistrados() < ($this->invitados_autorizados ?? 0);
    }

    public function yaUtilizado(): bool
    {
        if ($this->tipo !== self::TIPO_GRADUADO) {
            return false;
        }

        return $this->registros()->exists();
    }
}
