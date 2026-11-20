<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait CeremoniaValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function ceremoniaRules(): array
    {
        return [
            'nombre' => ['required',
            'string',
            'min:4',
            'max:120',
            'regex:/^(?=.*[a-zA-ZáéíóúÁÉÍÓÚñÑ]).+$/'
            ],
            
            'periodo' => ['nullable', 'string', 'exists:periodos_escolares,periodo'],
            
            'fecha_inicio' => ['required', 'date'],
            
            'fecha_fin' => [
                'required', 
                'date', 
                'after:fecha_inicio',
                function ($attribute, $value, $fail) {
                    $inicio = request()->input('fecha_inicio');
                    if ($inicio) {
                        $minutos = \Carbon\Carbon::parse($inicio)->diffInMinutes(\Carbon\Carbon::parse($value));
                        if ($minutos < 60) {
                            $fail('La ceremonia debe tener una duración de al menos 1 hora.');
                        }
                    }
                },
            ],
            
            'tipo_acceso' => ['required', 'in:qr'],
            'descripcion' => ['nullable', 'string'],
            'minutos_anticipados_graduados' => ['required', 'integer', 'min:0', 'max:180'],
            'minutos_anticipados_invitados' => ['required', 'integer', 'min:0', 'max:180'],
            'duracion_horas_acceso' => ['required', 'integer', 'min:1', 'max:24'],
            'invitados_por_defecto' => ['required', 'integer', 'min:0', 'max:10'],
            'carreras' => ['nullable', 'array'],
            'carreras.*.carrera' => ['required', 'string', 'max:3'],
            'carreras.*.reticula' => ['required', 'integer'],
        ];
    }
}
