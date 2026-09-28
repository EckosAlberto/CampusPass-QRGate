<?php

namespace App\Concerns;

use App\Models\Carrera;

trait OpcionesCarreras
{
    /**
     * @return list<array{value: string, label: string}>
     */
    private function opcionesCarreras(): array
    {
        return Carrera::query()
            ->selectRaw('carrera, MIN(nombre_carrera) as nombre_carrera')
            ->groupBy('carrera')
            ->orderBy('nombre_carrera')
            ->get()
            ->map(fn (Carrera $carrera) => [
                'value' => $carrera->carrera,
                'label' => $carrera->nombre_carrera ?? $carrera->carrera,
            ])
            ->all();
    }
}
