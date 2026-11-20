<?php

namespace App\Concerns;

trait DatosPersonalesValidationRules
{
    /**
     *
     * @return array<string, array<int, mixed>>
     */
    protected function datosPersonalesRules(bool $requerido = true): array
    {
        $obligatorio = $requerido ? 'required' : 'sometimes';

        return [
            'fecha_nacimiento' => [$obligatorio, 'date', 'before:today'],
            'curp' => [$obligatorio, 'string', 'size:18', 'regex:/^[A-Za-z0-9]{18}$/'],
            'rfc' => [$obligatorio, 'string', 'between:12,13', 'regex:/^[A-Za-z0-9]{12,13}$/'],
            'telefono' => [$obligatorio, 'string', 'regex:/^[0-9]{10}$/'],
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    protected function normalizarDatosPersonales(array $datos): array
    {
        foreach (['curp', 'rfc'] as $campo) {
            if (array_key_exists($campo, $datos) && $datos[$campo] !== null) {
                $datos[$campo] = strtoupper($datos[$campo]);
            }
        }

        return $datos;
    }
}
