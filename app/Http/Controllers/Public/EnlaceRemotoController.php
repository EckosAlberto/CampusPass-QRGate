<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnlaceRemotoController extends Controller
{
    private const POR_PAGINA = 20;

    public function show(Request $request, CeremoniaGraduacion $ceremonia): Response
    {
        if (! $request->hasValidSignature()) {
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Este enlace ya no está disponible.',
            ]);
        }

        if (now()->greaterThan($ceremonia->fecha_fin)) {
            return Inertia::render('graduacion/enlace-no-disponible', [
                'mensaje' => 'Esta ceremonia ya finalizó.',
            ]);
        }

        $accesos = RegistroAccesoGraduacion::query()
            ->whereHas('boleto', fn ($q) => $q->where('fk_id_ceremonia', $ceremonia->id))
            ->with('boleto.alumno')
            ->orderByDesc('fecha_hora')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('graduacion/enlace-remoto', [
            'ceremonia' => [
                'nombre' => $ceremonia->nombre,
                'fechaInicio' => $ceremonia->fecha_inicio->format('d/m/Y h:i A'),
                'fechaFin' => $ceremonia->fecha_fin->format('d/m/Y h:i A'),
            ],
            'egresadosRegistrados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_GRADUADO)->count(),
            'invitadosRegistrados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_INVITADO)->count(),
            'totalAccesos' => $accesos->total(),
            'accesos' => [
                'data' => collect($accesos->items())->map(fn (RegistroAccesoGraduacion $registro) => [
                    'id' => $registro->id,
                    'fecha' => $registro->fecha_hora->format('d/m/Y'),
                    'hora' => $registro->fecha_hora->format('h:i A'),
                    'nombre' => $registro->boleto?->alumno?->nombreCompleto() ?? 'Desconocido',
                    'carrera' => $registro->boleto?->alumno?->nombreCarrera(),
                    'noDeControl' => $registro->boleto?->no_de_control,
                    'estatus' => $registro->boleto?->tipo === BoletoGraduacion::TIPO_GRADUADO ? 'Egresado' : 'Invitado',
                ])->all(),
                'meta' => [
                    'paginaActual' => $accesos->currentPage(),
                    'ultimaPagina' => $accesos->lastPage(),
                ],
            ],
        ]);
    }
}
