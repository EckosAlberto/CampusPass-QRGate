<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['rfc', 'curp_empleado', 'apellidos_empleado', 'nombre_empleado'])]
class Personal extends Model
{
    protected $table = 'personal';

    protected $primaryKey = 'rfc';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre_empleado} {$this->apellidos_empleado}");
    }

    public function sexo(): ?string
    {
        $letra = mb_strtoupper(substr((string) $this->curp_empleado, 10, 1));

        return in_array($letra, ['H', 'M'], true) ? $letra : null;
    }
}
