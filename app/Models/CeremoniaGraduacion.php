<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CeremoniaGraduacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

#[Fillable([
    'folio',
    'nombre',
    'periodo',
    'fecha_inicio',
    'fecha_fin',
    'tipo_acceso',
    'estatus',
    'descripcion',
    'minutos_anticipados_graduados',
    'minutos_anticipados_invitados',
    'duracion_horas_acceso',
    'invitados_por_defecto',
    'creado_por',
])]
class CeremoniaGraduacion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ceremonias_graduacion';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'estatus' => 'boolean',
        ];
    }

    public function estaVigente(): bool
    {
        if (! $this->estatus) {
            return false;
        }

        return now()->lessThanOrEqualTo($this->expiraEn());
    }

    public function minutosAnticipados(string $tipoBoleto): int
    {
        return $tipoBoleto === BoletoGraduacion::TIPO_INVITADO
            ? $this->minutos_anticipados_invitados
            : $this->minutos_anticipados_graduados;
    }

    public function habilitadoDesde(string $tipoBoleto): CarbonInterface
    {
        return $this->fecha_inicio->copy()->subMinutes($this->minutosAnticipados($tipoBoleto));
    }

    public function pantallaHabilitadaDesde(): CarbonInterface
    {
        return $this->fecha_inicio->copy()->subMinutes(max(
            $this->minutos_anticipados_graduados,
            $this->minutos_anticipados_invitados,
        ));
    }

    public function expiraEn(): CarbonInterface
    {
        return $this->fecha_inicio->copy()->addHours($this->duracion_horas_acceso);
    }

    public function accesoPermitido(string $tipoBoleto, ?CarbonInterface $momento = null): bool
    {
        if (! $this->estatus) {
            return false;
        }

        return ($momento ?? now())->between($this->habilitadoDesde($tipoBoleto), $this->expiraEn());
    }

    public function periodoEscolar()
    {
        return $this->belongsTo(PeriodoEscolar::class, 'periodo', 'periodo');
    }

    public function creador()
    {
        return $this->belongsTo(GraduacionUsuario::class, 'creado_por');
    }

    public function carrerasParticipantes()
    {
        return $this->hasMany(CeremoniaCarrera::class, 'fk_id_ceremonia');
    }

    public function boletos()
    {
        return $this->hasMany(BoletoGraduacion::class, 'fk_id_ceremonia');
    }

    public function codigo(): string
    {
        return sprintf('CER-%s-%03d', $this->fecha_inicio->format('Y'), $this->folio ?? 0);
    }

    public function urlRegistro(): ?string
    {
        if (now()->greaterThan($this->expiraEn())) {
            return null;
        }

        return URL::temporarySignedRoute(
            'graduacion.registro',
            $this->expiraEn(),
            ['ceremonia' => $this->id],
        );
    }

    public function urlEnlace(): ?string
    {
        if (now()->greaterThan($this->fecha_fin)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'graduacion.enlace-remoto',
            $this->fecha_fin,
            ['ceremonia' => $this->id],
        );
    }
}
