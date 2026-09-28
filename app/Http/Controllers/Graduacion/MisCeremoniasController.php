<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\CeremoniaGraduacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MisCeremoniasController extends Controller
{
    /** Pantalla "Ceremonias": solo las ceremonias creadas por el usuario actual. */
    public function index(Request $request): Response
    {
        $ceremonias = CeremoniaGraduacion::query()
            ->where('creado_por', $request->user('graduacion')->id)
            ->orderByDesc('fecha_inicio')
            ->get();

        return Inertia::render('graduacion/mis-ceremonias', [
            'ceremonias' => $ceremonias->map(fn (CeremoniaGraduacion $ceremonia) => $this->serializar($ceremonia))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(CeremoniaGraduacion $ceremonia): array
    {
        return [
            'id' => $ceremonia->id,
            'codigo' => $ceremonia->codigo(),
            'nombre' => $ceremonia->nombre,
            'fechaInicio' => $ceremonia->fecha_inicio->format('d/m/Y h:i A'),
            'fechaFin' => $ceremonia->fecha_fin->format('d/m/Y h:i A'),
            'estatus' => $ceremonia->estatus,
            'vigente' => $ceremonia->estaVigente(),
            'urlRegistro' => $ceremonia->urlRegistro(),
            'urlEnlace' => $ceremonia->urlEnlace(),
        ];
    }
}
