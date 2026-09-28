<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduacion\StoreCeremoniaRequest;
use App\Http\Requests\Graduacion\UpdateCeremoniaRequest;
use App\Mail\CeremoniaUrlCompartida;
use App\Models\BoletoGraduacion;
use App\Models\Carrera;
use App\Models\CeremoniaCarrera;
use App\Models\CeremoniaGraduacion;
use App\Models\PeriodoEscolar;
use App\Models\RegistroAccesoGraduacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CeremoniaController extends Controller
{
    private const POR_PAGINA = 10;

    /** Pantalla de Gestión de ceremonias */
    public function index(Request $request): Response
    {
        $ceremonias = CeremoniaGraduacion::query()
            ->with(['periodoEscolar', 'creador', 'carrerasParticipantes'])
            ->orderByDesc('fecha_inicio')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return Inertia::render('graduacion/gestion-ceremonias', [
            'usuarioId' => $request->user('graduacion')->id,
            'ceremonias' => [
                'data' => collect($ceremonias->items())->map(fn (CeremoniaGraduacion $ceremonia) => $this->serializar($ceremonia))->all(),
                'meta' => [
                    'paginaActual' => $ceremonias->currentPage(),
                    'ultimaPagina' => $ceremonias->lastPage(),
                    'total' => $ceremonias->total(),
                    'desde' => $ceremonias->firstItem(),
                    'hasta' => $ceremonias->lastItem(),
                ],
            ],
            'catalogos' => [
                'periodos' => PeriodoEscolar::query()->orderByDesc('fecha_inicio')->get()
                    ->map(fn (PeriodoEscolar $periodo) => ['value' => $periodo->periodo, 'label' => $periodo->identificacion_corta ?? $periodo->periodo])->all(),
                'carreras' => Carrera::query()->orderBy('nombre_carrera')->get()
                    ->map(fn (Carrera $carrera) => ['value' => "{$carrera->carrera}-{$carrera->reticula}", 'label' => $carrera->nombre_carrera ?? $carrera->carrera])->all(),
            ],
        ]);
    }

    /** Botón "+ Nueva ceremonia": crea la ceremonia con un folio consecutivo nuevo. */
    public function store(StoreCeremoniaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $carreras = $datos['carreras'] ?? [];
        unset($datos['carreras']);

        $ceremonia = DB::transaction(function () use ($datos, $request) {
            $folio = (int) (CeremoniaGraduacion::query()->lockForUpdate()->max('folio')) + 1;

            return CeremoniaGraduacion::create([
                ...$datos,
                'folio' => $folio,
                'estatus' => true,
                'creado_por' => $request->user('graduacion')->id,
            ]);
        });

        $this->sincronizarCarreras($ceremonia, $carreras);

        return back();
    }

    /** Botón "Editar": guarda los cambios de una ceremonia existente. */
    public function update(UpdateCeremoniaRequest $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $datos = $request->validated();
        $carreras = $datos['carreras'] ?? [];
        unset($datos['carreras']);

        $ceremonia->update($datos);

        $this->sincronizarCarreras($ceremonia, $carreras);

        return back();
    }

    /** Interruptor de activo/inactivo de la ceremonia en la tabla. */
    public function actualizarEstatus(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $datos = $request->validate([
            'estatus' => ['required', 'boolean'],
        ]);

        $ceremonia->update($datos);

        return back();
    }

    /** Botón "Finalizar": cierra la ceremonia para que ya no se puedan registrar más accesos. */
    public function finalizar(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        abort_unless($ceremonia->creado_por === $request->user('graduacion')->id, 403);

        $ceremonia->update(['estatus' => false]);

        return redirect()->route('graduacion.panel');
    }

    /** Botón "Eliminar": rechaza el borrado si la ceremonia ya tiene accesos registrados. */
    public function destroy(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        if ($ceremonia->creado_por !== $request->user('graduacion')->id) {
            abort(403, 'Solo la persona que creó la ceremonia puede eliminarla.');
        }

        
        $tieneAccesosRegistrados = RegistroAccesoGraduacion::query()
            ->whereIn('fk_id_boleto', BoletoGraduacion::query()->where('fk_id_ceremonia', $ceremonia->id)->pluck('id'))
            ->exists();

        if ($tieneAccesosRegistrados) {
            throw ValidationException::withMessages([
                'ceremonia' => 'No se puede eliminar: esta ceremonia ya tiene accesos registrados. Finalízala en su lugar.',
            ]);
        }

        $ceremonia->delete();

        return back();
    }

    /** Pantalla de detalle de una ceremonia: sus datos y las URL de registro/enlace remoto. */
    public function show(CeremoniaGraduacion $ceremonia): Response
    {
        return Inertia::render('graduacion/ceremonia', [
            'ceremonia' => $this->serializar($ceremonia),
            'urlRegistro' => $ceremonia->urlRegistro(),
            'urlEnlace' => $ceremonia->urlEnlace(),
        ]);
    }

    /** Botón "Compartir": manda por correo la URL de registro o de enlace remoto. */
    public function compartir(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:registro,enlace'],
            'correo' => ['required', 'email'],
        ]);

        $url = $datos['tipo'] === 'registro'
            ? $ceremonia->urlRegistro()
            : $ceremonia->urlEnlace();

        if (! $url) {
            throw ValidationException::withMessages([
                'tipo' => 'Esta URL ya no está disponible.',
            ]);
        }

        Mail::to($datos['correo'])->send(new CeremoniaUrlCompartida($ceremonia, $datos['tipo'], $url));

        return back()->with('success', "URL enviada a {$datos['correo']}.");
    }

    /**
     * @param  list<array{carrera: string, reticula: int}>  $carreras
     */
    private function sincronizarCarreras(CeremoniaGraduacion $ceremonia, array $carreras): void
    {
        CeremoniaCarrera::query()->where('fk_id_ceremonia', $ceremonia->id)->delete();

        foreach ($carreras as $carrera) {
            CeremoniaCarrera::create([
                'fk_id_ceremonia' => $ceremonia->id,
                'carrera' => $carrera['carrera'],
                'reticula' => $carrera['reticula'],
            ]);
        }
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
            'periodo' => $ceremonia->periodoEscolar ? ['value' => $ceremonia->periodoEscolar->periodo, 'label' => $ceremonia->periodoEscolar->identificacion_corta ?? $ceremonia->periodoEscolar->periodo] : null,
            'fechaInicio' => $ceremonia->fecha_inicio?->format('Y-m-d\TH:i'),
            'fechaFin' => $ceremonia->fecha_fin?->format('Y-m-d\TH:i'),
            'fechaInicioLabel' => $ceremonia->fecha_inicio?->format('d/m/Y h:i A'),
            'fechaFinLabel' => $ceremonia->fecha_fin?->format('d/m/Y h:i A'),
            'tipoAcceso' => $ceremonia->tipo_acceso,
            'estatus' => $ceremonia->estatus,
            'descripcion' => $ceremonia->descripcion,
            'minutosAnticipadosGraduados' => $ceremonia->minutos_anticipados_graduados,
            'minutosAnticipadosInvitados' => $ceremonia->minutos_anticipados_invitados,
            'duracionHorasAcceso' => $ceremonia->duracion_horas_acceso,
            'invitadosPorDefecto' => $ceremonia->invitados_por_defecto,
            'creadoPor' => $ceremonia->creado_por,
            'creadorNombre' => $ceremonia->creador?->name,
            'carreras' => $ceremonia->carrerasParticipantes->map(fn (CeremoniaCarrera $cc) => [
                'carrera' => $cc->carrera,
                'reticula' => $cc->reticula,
                'nombre' => $cc->carreraInfo()?->nombre_carrera ?? $cc->carrera,
            ])->all(),
            'vigente' => $ceremonia->estaVigente(),
            'pantallaHabilitada' => now()->between($ceremonia->pantallaHabilitadaDesde(), $ceremonia->expiraEn()) && $ceremonia->estatus,
            'egresadosRegistrados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_GRADUADO)->count(),
            'invitadosRegistrados' => BoletoGraduacion::where('fk_id_ceremonia', $ceremonia->id)->where('tipo', BoletoGraduacion::TIPO_INVITADO)->count(),
        ];
    }
}
