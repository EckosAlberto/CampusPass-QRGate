<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clave_grupo', 'fk_id_tipo_tutor', 'fk_id_periodo_tutorias', 'carrera', 'reticula', 'rfc'])]
class Tutor extends Model
{
    use HasUuids;

    protected $table = 'tutor';

    public $timestamps = false;

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'rfc', 'rfc');
    }

    public function periodoTutorias()
    {
        return $this->belongsTo(PeriodoTutorias::class, 'fk_id_periodo_tutorias');
    }

    public function nombreCompleto(): ?string
    {
        return $this->personal?->nombreCompleto();
    }
}
