<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fk_id_boleto', 'fecha_hora'])]
class RegistroAccesoGraduacion extends Model
{
    use HasUuids;

    protected $table = 'registros_acceso_graduacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function boleto()
    {
        return $this->belongsTo(BoletoGraduacion::class, 'fk_id_boleto');
    }
}
