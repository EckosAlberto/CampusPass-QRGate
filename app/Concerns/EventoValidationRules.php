<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait EventoValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function eventoRules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:64'],
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'horas' => ['required', 'integer', 'min:1', 'max:24'],
            'fk_id_tipo_evento' => ['required', 'uuid', 'exists:tipo_evento,id'],
            'fk_id_periodo_tutorias' => ['required', 'uuid', 'exists:periodo_tutorias,id'],
            'fk_id_tutor' => ['required', 'uuid', 'exists:tutor,id'],
        ];
    }
}
