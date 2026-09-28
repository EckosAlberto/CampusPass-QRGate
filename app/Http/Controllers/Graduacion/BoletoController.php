<?php

namespace App\Http\Controllers\Graduacion;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\BoletoGraduacion;
use App\Models\Carrera;
use App\Models\CeremoniaGraduacion;
use App\Models\IdentificadorFoto;
use App\Services\QrCodeGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoletoController extends Controller
{
    /** Pantalla "QR para Invitados": buscador de boletos por carrera (grupal) o por alumno (individual). */
    public function index(Request $request): Response
    {
        $datos = $request->validate([
            'ceremonia' => ['nullable', 'uuid', 'exists:ceremonias_graduacion,id'],
            'modo' => ['nullable', 'in:grupal,individual'],
            'carrera' => ['nullable', 'string'],
            'reticula' => ['nullable', 'integer'],
            'query' => ['nullable', 'string'],
        ]);

        $ceremonia = isset($datos['ceremonia'])
            ? CeremoniaGraduacion::find($datos['ceremonia'])
            : CeremoniaGraduacion::query()->where('estatus', true)->orderByDesc('fecha_inicio')->first();

        $resultadoGrupal = null;
        $resultadoIndividual = null;

        if ($ceremonia && ($datos['modo'] ?? null) === 'grupal' && isset($datos['carrera'], $datos['reticula'])) {
            $resultadoGrupal = $this->buscarPorCarrera($ceremonia, $datos['carrera'], (int) $datos['reticula']);
        }

        if ($ceremonia && ($datos['modo'] ?? null) === 'individual' && ! empty($datos['query'])) {
            $resultadoIndividual = $this->buscarIndividual($ceremonia, $datos['query']);
        }

        return Inertia::render('graduacion/qr-invitados', [
            'ceremonias' => CeremoniaGraduacion::query()->orderByDesc('fecha_inicio')->get()
                ->map(fn (CeremoniaGraduacion $c) => ['value' => $c->id, 'label' => $c->nombre])->all(),
            'carreras' => Carrera::query()->orderBy('nombre_carrera')->get()
                ->map(fn (Carrera $c) => ['value' => "{$c->carrera}-{$c->reticula}", 'label' => $c->nombre_carrera ?? $c->carrera])->all(),
            'ceremoniaSeleccionada' => $ceremonia?->id,
            'resultadoGrupal' => $resultadoGrupal,
            'resultadoIndividual' => $resultadoIndividual,
        ]);
    }

    /** Botón "Generar boletos": crea el boleto de graduado y los de sus invitados autorizados. */
    public function generar(Request $request, CeremoniaGraduacion $ceremonia): RedirectResponse
    {
        $datos = $request->validate([
            'no_de_control' => ['required', 'string', 'exists:alumnos,no_de_control'],
        ]);

        $this->generarBoletos($ceremonia, $datos['no_de_control']);

        return back()->with('success', 'Boletos generados.');
    }

    /** Botón "Descargar QR" */
    public function descargarQr(BoletoGraduacion $boleto, QrCodeGenerator $generador): StreamedResponse
    {
        $png = $generador->pngBinario($boleto->codigo);

        return response()->streamDownload(
            function () use ($png) {
                echo $png;
            },
            "boleto-{$boleto->tipo}-{$boleto->no_de_control}.png",
            ['Content-Type' => 'image/png'],
        );
    }

    /** Botón "Regenerar" */
    public function regenerar(BoletoGraduacion $boleto): RedirectResponse
    {
        $boleto->update(['codigo' => Str::random(32)]);

        return back()->with('success', 'Código QR regenerado.');
    }

    /** Botón "Descargar QR grupal" */
    public function descargarQrGrupal(Request $request, CeremoniaGraduacion $ceremonia, QrCodeGenerator $generador): StreamedResponse
    {
        $datos = $request->validate([
            'carrera' => ['required', 'string'],
            'reticula' => ['required', 'integer'],
        ]);

        $boletos = BoletoGraduacion::query()
            ->where('fk_id_ceremonia', $ceremonia->id)
            ->where('tipo', BoletoGraduacion::TIPO_INVITADO)
            ->whereHas('alumno', fn ($q) => $q->where('carrera', $datos['carrera'])->where('reticula', $datos['reticula']))
            ->with('alumno')
            ->get()
            ->map(fn (BoletoGraduacion $boleto) => [
                'alumno' => $boleto->alumno?->nombreCompleto(),
                'noDeControl' => $boleto->no_de_control,
                'qr' => $generador->dataUri($boleto->codigo),
            ]);

        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reportes.graduacion-qr-grupal', [
            'ceremonia' => $ceremonia,
            'boletos' => $boletos,
        ])->render());
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf;
            },
            "qr-invitados-{$datos['carrera']}-{$ceremonia->nombre}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buscarPorCarrera(CeremoniaGraduacion $ceremonia, string $carrera, int $reticula): array
    {
        $alumnos = Alumno::query()
            ->where('carrera', $carrera)
            ->where('reticula', $reticula)
            ->orderBy('apellido_paterno')
            ->get();

        return [
            'carrera' => $alumnos->first()?->nombreCarrera() ?? $carrera,
            'alumnos' => $alumnos->map(function (Alumno $alumno) use ($ceremonia) {
                $invitado = BoletoGraduacion::query()
                    ->where('fk_id_ceremonia', $ceremonia->id)
                    ->where('no_de_control', $alumno->no_de_control)
                    ->where('tipo', BoletoGraduacion::TIPO_INVITADO)
                    ->first();

                return [
                    'noDeControl' => $alumno->no_de_control,
                    'nombre' => $alumno->nombreCompleto(),
                    'tieneBoleto' => $invitado !== null,
                    'invitadosAutorizados' => $invitado?->invitados_autorizados,
                ];
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buscarIndividual(CeremoniaGraduacion $ceremonia, string $query): ?array
    {
        $alumno = Alumno::query()
            ->where('no_de_control', $query)
            ->orWhere('nombre_alumno', 'like', "%{$query}%")
            ->orWhere('apellido_paterno', 'like', "%{$query}%")
            ->orWhere('apellido_materno', 'like', "%{$query}%")
            ->first();

        if (! $alumno) {
            return null;
        }

        [$graduado, $invitado] = $this->generarBoletos($ceremonia, $alumno->no_de_control);

        $identificador = IdentificadorFoto::query()->firstOrCreate(
            ['curp' => $alumno->curp_alumno],
            ['fecha_registro' => now(), 'estatus' => true],
        );

        return [
            'alumno' => [
                'noDeControl' => $alumno->no_de_control,
                'nombre' => $alumno->nombreCompleto(),
                'carrera' => $alumno->nombreCarrera(),
            ],
            'identificador' => [
                'idUnico' => $identificador->idFormateado(),
                'curp' => $identificador->curp,
                'hashFoto' => $identificador->hash_foto,
                'fechaRegistro' => $identificador->fecha_registro?->format('d/m/Y h:i A'),
                'estatus' => $identificador->estatus,
            ],
            'boletoInvitado' => [
                'id' => $invitado->id,
                'invitadosAutorizados' => $invitado->invitados_autorizados,
                'invitadosRegistrados' => $invitado->invitadosRegistrados(),
                'generadoEn' => $invitado->generado_en->format('d/m/Y h:i A'),
            ],
        ];
    }

    /**
     * @return array{0: BoletoGraduacion, 1: BoletoGraduacion}
     */
    private function generarBoletos(CeremoniaGraduacion $ceremonia, string $noDeControl): array
    {
        $graduado = BoletoGraduacion::query()->firstOrCreate(
            [
                'fk_id_ceremonia' => $ceremonia->id,
                'no_de_control' => $noDeControl,
                'tipo' => BoletoGraduacion::TIPO_GRADUADO,
            ],
            ['codigo' => Str::random(32), 'generado_en' => now()],
        );

        $invitado = BoletoGraduacion::query()->firstOrCreate(
            [
                'fk_id_ceremonia' => $ceremonia->id,
                'no_de_control' => $noDeControl,
                'tipo' => BoletoGraduacion::TIPO_INVITADO,
            ],
            [
                'codigo' => Str::random(32),
                'invitados_autorizados' => $ceremonia->invitados_por_defecto,
                'generado_en' => now(),
            ],
        );

        return [$graduado, $invitado];
    }
}
