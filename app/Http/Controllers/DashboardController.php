<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Personal;
use App\Models\RegistroAcceso;
use App\Models\Ubicacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Horario de operación considerado para la gráfica de entradas por hora.
     */
    private const HORA_APERTURA = 7;

    private const HORA_CIERRE = 21;

    private const ACCESOS_RECIENTES_LIMITE = 8;

    private const DIAS_HISTORICO = 7;

    private const NOMBRES_DIAS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    /** Pantalla de Inicio de Biblioteca: resumen del día y gráfica de entradas por hora. */
    public function index(): Response
    {
        $ubicacion = Ubicacion::where('nombre', 'Biblioteca')->first();
        $bibliotecaId = $ubicacion?->id;

        $registrosHoy = RegistroAcceso::where('id_ubicacion', $bibliotecaId)
            ->whereDate('fecha_hora', today())
            ->orderBy('fecha_hora')
            ->get(['no_de_control', 'rfc_personal', 'tipo_movimiento', 'fecha_hora']);

        return Inertia::render('dashboard', [
            'resumen' => $this->resumenDelDia($registrosHoy),
            'entradasPorHora' => $this->entradasPorHora($registrosHoy),
            'distribucionPorSexo' => $this->distribucionPorSexo($registrosHoy),
            'accesosRecientes' => $this->accesosRecientes($bibliotecaId),
            'accesosPorDia' => $this->accesosPorDia($bibliotecaId),
        ]);
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registrosHoy
     * @return array{entradasHoy: int, salidasHoy: int, usuariosUnicos: int, personasDentro: int}
     */
    private function resumenDelDia(Collection $registrosHoy): array
    {
        $porPersona = $registrosHoy->groupBy(fn (RegistroAcceso $registro) => $registro->identificador());

        $personasDentro = $porPersona
            ->filter(fn (Collection $registros) => $registros->last()->tipo_movimiento === 'ENTRADA')
            ->count();

        return [
            'entradasHoy' => $registrosHoy->where('tipo_movimiento', 'ENTRADA')->count(),
            'salidasHoy' => $registrosHoy->where('tipo_movimiento', 'SALIDA')->count(),
            'usuariosUnicos' => $porPersona->count(),
            'personasDentro' => $personasDentro,
        ];
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registrosHoy
     * @return list<array{hora: string, total: int}>
     */
    private function entradasPorHora(Collection $registrosHoy): array
    {
        $entradasPorHora = $registrosHoy
            ->where('tipo_movimiento', 'ENTRADA')
            ->countBy(fn (RegistroAcceso $registro) => $registro->fecha_hora->hour);

        return collect(range(self::HORA_APERTURA, self::HORA_CIERRE))
            ->map(fn (int $hora) => [
                'hora' => sprintf('%02d:00', $hora),
                'total' => $entradasPorHora->get($hora, 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, RegistroAcceso>  $registrosHoy
     * @return list<array{sexo: string, total: int}>
     */
    private function distribucionPorSexo(Collection $registrosHoy): array
    {
        $noDeControlUnicos = $registrosHoy->pluck('no_de_control')->filter()->unique();
        $rfcPersonalUnicos = $registrosHoy->pluck('rfc_personal')->filter()->unique();

        $etiquetas = ['H' => 'Hombres', 'M' => 'Mujeres'];

        $conteoAlumnos = Alumno::whereIn('no_de_control', $noDeControlUnicos)
            ->get(['sexo'])
            ->countBy(fn (Alumno $alumno) => $etiquetas[$alumno->sexo] ?? 'No especificado');

        
        $conteoPersonal = Personal::whereIn('rfc', $rfcPersonalUnicos)
            ->get(['curp_empleado'])
            ->countBy(fn (Personal $persona) => $etiquetas[$persona->sexo()] ?? 'No especificado');

        $conteoPorSexo = $conteoAlumnos->mergeRecursive($conteoPersonal)
            ->map(fn ($total) => is_array($total) ? array_sum($total) : $total);

        return $conteoPorSexo
            ->map(fn (int $total, string $sexo) => ['sexo' => $sexo, 'total' => $total])
            ->values()
            ->all();
    }

    /**
     * @return list<array{hora: string, nombre: string, carrera: ?string, tipoMovimiento: string}>
     */
    private function accesosRecientes(?int $bibliotecaId): array
    {
        return RegistroAcceso::where('id_ubicacion', $bibliotecaId)
            ->with(['alumno', 'personal'])
            ->latest('fecha_hora')
            ->limit(self::ACCESOS_RECIENTES_LIMITE)
            ->get()
            ->map(fn (RegistroAcceso $registro) => [
                'hora' => $registro->fecha_hora->format('h:i:s A'),
                'nombre' => $registro->alumno?->nombreCompleto()
                    ?? $registro->personal?->nombreCompleto()
                    ?? 'Desconocido',
                'carrera' => $registro->alumno?->nombreCarrera(),
                'tipoMovimiento' => $registro->tipo_movimiento,
            ])
            ->all();
    }

    /**
     * @return list<array{fecha: string, etiqueta: string, total: int}>
     */
    private function accesosPorDia(?int $bibliotecaId): array
    {
        $inicio = today()->subDays(self::DIAS_HISTORICO - 1);

        $conteoPorDia = RegistroAcceso::where('id_ubicacion', $bibliotecaId)
            ->whereBetween('fecha_hora', [$inicio, today()->endOfDay()])
            ->get(['fecha_hora'])
            ->countBy(fn (RegistroAcceso $registro) => $registro->fecha_hora->toDateString());

        return collect(range(0, self::DIAS_HISTORICO - 1))
            ->map(function (int $offset) use ($inicio, $conteoPorDia) {
                /** @var CarbonImmutable $fecha */
                $fecha = $inicio->addDays($offset);

                return [
                    'fecha' => $fecha->toDateString(),
                    'etiqueta' => self::NOMBRES_DIAS[$fecha->dayOfWeek],
                    'total' => $conteoPorDia->get($fecha->toDateString(), 0),
                ];
            })
            ->all();
    }
}
