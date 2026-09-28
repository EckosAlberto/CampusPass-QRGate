<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\BoletoGraduacion;
use App\Models\CeremoniaGraduacion;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    private const POR_PAGINA = 10;

    /** Pantalla de Inicio: ceremonia activa y su resumen de asistencia. */
    public function index(Request $request): Response
    {
        $ceremoniaActiva = CeremoniaGraduacion::query()
            ->where('estatus', true)
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->orderBy('fecha_inicio')
            ->first();

        $egresadosRegistrados = 0;
        $invitadosRegistrados = 0;
        $totalAsistentes = 0;
        $asistenciaPorCarrera = [];
        $registrosQuery = RegistroAccesoGraduacion::query()->whereRaw('0 = 1');

        if ($ceremoniaActiva) {
            $egresadosRegistrados = BoletoGraduacion::where('fk_id_ceremonia', $ceremoniaActiva->id)
                ->where('tipo', BoletoGraduacion::TIPO_GRADUADO)->count();
            $invitadosRegistrados = BoletoGraduacion::where('fk_id_ceremonia', $ceremoniaActiva->id)
                ->where('tipo', BoletoGraduacion::TIPO_INVITADO)->count();

            $registrosQuery = RegistroAccesoGraduacion::query()
                ->whereHas('boleto', fn ($query) => $query->where('fk_id_ceremonia', $ceremoniaActiva->id));

            $totalAsistentes = (clone $registrosQuery)->count();

            $asistenciaPorCarrera = (clone $registrosQuery)
                ->with('boleto.alumno')
                ->get()
                ->groupBy(fn (RegistroAccesoGraduacion $registro) => $registro->boleto?->alumno?->carrera ?? '—')
                ->map(fn ($grupo, $carrera) => [
                    'carrera' => $grupo->first()->boleto?->alumno?->nombreCarrera() ?? 'Sin carrera',
                    'total' => $grupo->count(),
                ])
                ->values()
                ->all();
        }

        $ultimosAccesos = (clone $registrosQuery)
            ->with('boleto.alumno')
            ->orderByDesc('fecha_hora')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('graduacion/panel', [
            'usuario' => $request->user('graduacion'),
            'resumen' => [
                'ceremoniaActiva' => $ceremoniaActiva ? [
                    'id' => $ceremoniaActiva->id,
                    'nombre' => $ceremoniaActiva->nombre,
                    'fechaInicio' => $ceremoniaActiva->fecha_inicio->format('d/m/Y h:i A'),
                    'fechaFin' => $ceremoniaActiva->fecha_fin->format('d/m/Y h:i A'),
                ] : null,
                'egresadosRegistrados' => $egresadosRegistrados,
                'invitadosRegistrados' => $invitadosRegistrados,
                'totalAsistentes' => $totalAsistentes,
            ],
            'asistenciaPorCarrera' => $asistenciaPorCarrera,
            'ultimosAccesos' => [
                'data' => collect($ultimosAccesos->items())->map(fn (RegistroAccesoGraduacion $registro) => [
                    'id' => $registro->id,
                    'fecha' => $registro->fecha_hora->format('d/m/Y'),
                    'hora' => $registro->fecha_hora->format('h:i A'),
                    'nombre' => $registro->boleto?->alumno?->nombreCompleto() ?? 'Desconocido',
                    'carrera' => $registro->boleto?->alumno?->nombreCarrera(),
                    'noDeControl' => $registro->boleto?->no_de_control,
                    'estatus' => $registro->boleto?->tipo === BoletoGraduacion::TIPO_GRADUADO ? 'Egresado' : 'Invitado',
                ])->all(),
                'meta' => [
                    'paginaActual' => $ultimosAccesos->currentPage(),
                    'ultimaPagina' => $ultimosAccesos->lastPage(),
                    'total' => $ultimosAccesos->total(),
                    'desde' => $ultimosAccesos->firstItem(),
                    'hasta' => $ultimosAccesos->lastItem(),
                ],
            ],
        ]);
    }
}
