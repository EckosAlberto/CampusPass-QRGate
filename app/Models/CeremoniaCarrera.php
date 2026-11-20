<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fk_id_ceremonia', 'carrera', 'reticula'])]
class CeremoniaCarrera extends Model
{
    use HasUuids;

    protected $table = 'ceremonia_carrera';

    public $timestamps = false;

    public function ceremonia()
    {
        return $this->belongsTo(CeremoniaGraduacion::class, 'fk_id_ceremonia');
    }

    public function carreraInfo(): ?Carrera
    {
        return Carrera::query()
            ->where('carrera', $this->carrera)
            ->where('reticula', $this->reticula)
            ->first();
    }
}
