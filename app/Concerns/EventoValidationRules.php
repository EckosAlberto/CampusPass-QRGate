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
            'nombre' => [
                'required',
                'string',
                'min:4',
                'max:64',
                'regex:/^(?=.*[a-zA-ZáéíóúÁÉÍÓÚñÑ]).+$/'],
            
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            
            'hora_fin' => [
                'required', 
                'date_format:H:i', 
                'after:hora_inicio',
                function ($attribute, $value, $fail) {
                    $inicio = request()->input('hora_inicio');
                    if ($inicio) {
                        $minutos = \Carbon\Carbon::parse($inicio)->diffInMinutes(\Carbon\Carbon::parse($value));
                        if ($minutos < 60) {
                            $fail('La duración del evento debe ser de al menos 1 hora (60 minutos).');
                        }
                    }
                },
            ],
            
            
            'horas' => ['required', 'numeric', 'min:1', 'max:24'],
            'fk_id_tipo_evento' => ['required', 'uuid', 'exists:tipo_evento,id'],
            'fk_id_periodo_tutorias' => ['required', 'uuid', 'exists:periodo_tutorias,id'],
            'fk_id_tutor' => ['required', 'uuid', 'exists:tutor,id'],
        ];
    }
}
