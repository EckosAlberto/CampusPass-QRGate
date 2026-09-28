<?php

namespace Database\Factories;

use App\Models\CeremoniaGraduacion;
use Illuminate\Database\Eloquent\Factories\Factory;


class CeremoniaGraduacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'folio' => fake()->unique()->numberBetween(1, 100000),
            'nombre' => 'Ceremonia de graduación '.fake()->numberBetween(20250, 20269),
            'periodo' => null,
            'fecha_inicio' => $inicio,
            'fecha_fin' => (clone $inicio)->modify('+3 hours'),
            'tipo_acceso' => 'qr',
            'estatus' => true,
            'descripcion' => fake()->optional()->sentence(),
            'minutos_anticipados_graduados' => 10,
            'minutos_anticipados_invitados' => 10,
            'duracion_horas_acceso' => 5,
            'invitados_por_defecto' => 2,
        ];
    }
}
