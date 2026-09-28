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
            'nombre' => ['required', 'string', 'max:120'],
            'periodo' => ['nullable', 'string', 'exists:periodos_escolares,periodo'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
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
