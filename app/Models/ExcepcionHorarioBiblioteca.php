<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['fecha', 'motivo', 'cerrado_todo_dia', 'horario_apertura', 'horario_cierre'])]
class ExcepcionHorarioBiblioteca extends Model
{
    use HasUuids;

    protected $table = 'excepciones_horario_biblioteca';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cerrado_todo_dia' => 'boolean',
        ];
    }

    public function detalle(): string
    {
        if ($this->cerrado_todo_dia) {
            return 'Cerrado todo el día';
        }

        return "Horario reducido: {$this->horario_apertura}–{$this->horario_cierre}";
    }
}
