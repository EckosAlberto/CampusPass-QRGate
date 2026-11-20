import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    LogIn,
    LogOut,
    Mail,
    Printer,
    Sheet as SheetIcon,
    Share2,
    Sigma,
    UsersRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { AccesosPorDiaChart } from '@/components/dashboard/accesos-por-dia-chart';
import { InstitutionHeader } from '@/components/institution-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { reportes as reportesIndex } from '@/routes';
import reportes from '@/routes/reportes';
import type { ReporteBibliotecaVistaPreviaProps } from '@/types';

function TarjetaIndicador({
    etiqueta,
    valor,
    simbolo,
    color,
}: {
    etiqueta: string;
    valor: ReactNode;
    simbolo: ReactNode;
    color: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-1 rounded-lg border border-l-4 border-border px-2 py-4 text-center',
                color,
            )}
        >
            {simbolo}
            <span className="text-xl font-bold tabular-nums">{valor}</span>
            <span className="text-[11px] text-muted-foreground">
                {etiqueta}
            </span>
        </div>
    );
}

export default function ReporteVistaPrevia({
    reporte,
    datos,
}: ReporteBibliotecaVistaPreviaProps) {
    const imprimirAlAbrir =
        typeof window !== 'undefined' &&
        new URLSearchParams(window.location.search).get('imprimir') === '1';

    const yaImprimio = useRef(false);

    useEffect(() => {
        if (imprimirAlAbrir && !yaImprimio.current) {
            yaImprimio.current = true;
            const temporizador = setTimeout(() => window.print(), 400);

            return () => clearTimeout(temporizador);
        }
    }, [imprimirAlAbrir]);

    const [compartirAbierto, setCompartirAbierto] = useState(false);
    const [correo, setCorreo] = useState('');
    const [enviando, setEnviando] = useState(false);

    function enviarPorCorreo() {
        setEnviando(true);

        router.post(
            reportes.compartir(reporte.id).url,
            { correo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`Reporte enviado a ${correo}.`);
                    setCompartirAbierto(false);
                },
                onError: (errores) =>
                    toast.error(
                        errores.correo ?? 'No se pudo enviar el reporte.',
                    ),
                onFinish: () => setEnviando(false),
            },
        );
    }

    return (
        <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
            <Head title={`Vista previa — ${reporte.nombre}`} />

            <div className="print:hidden">
                <InstitutionHeader
                    right={
                        <Link
                            href={reportesIndex()}
                            className="flex items-center gap-1 text-sm font-medium text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="size-4" />
                            Volver
                        </Link>
                    }
                />
            </div>

            <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-4 px-6 py-8">
                <div className="flex items-center justify-between text-xs text-muted-foreground print:hidden">
                    <span>Tipo: {reporte.tipoLabel}</span>
                    <span>Generado por: {reporte.generadoPor ?? '—'}</span>
                </div>

                <div className="text-center">
                    <h1 className="text-2xl font-extrabold tracking-tight text-primary uppercase">
                        {reporte.nombre}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Periodo: {reporte.periodo}
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <TarjetaIndicador
                        etiqueta="Total de entradas"
                        valor={datos.indicadores.entradas}
                        simbolo={
                            <LogIn className="size-5 text-emerald-600 dark:text-emerald-400" />
                        }
                        color="border-l-emerald-500"
                    />
                    <TarjetaIndicador
                        etiqueta="Total de salidas"
                        valor={datos.indicadores.salidas}
                        simbolo={
                            <LogOut className="size-5 text-red-600 dark:text-red-400" />
                        }
                        color="border-l-red-500"
                    />
                    <TarjetaIndicador
                        etiqueta="Usuarios únicos"
                        valor={datos.indicadores.usuariosUnicos}
                        simbolo={
                            <UsersRound className="size-5 text-violet-600 dark:text-violet-400" />
                        }
                        color="border-l-violet-500"
                    />
                    <TarjetaIndicador
                        etiqueta="Mujeres"
                        valor={datos.indicadores.mujeres}
                        simbolo={
                            <span className="text-lg text-pink-500">♀</span>
                        }
                        color="border-l-pink-500"
                    />
                    <TarjetaIndicador
                        etiqueta="Hombres"
                        valor={datos.indicadores.hombres}
                        simbolo={
                            <span className="text-lg text-blue-500">♂</span>
                        }
                        color="border-l-blue-500"
                    />
                    <TarjetaIndicador
                        etiqueta="Total de accesos"
                        valor={datos.indicadores.totalAccesos}
                        simbolo={<Sigma className="size-5 text-orange-500" />}
                        color="border-l-orange-500"
                    />
                </div>

                <Fieldset>
                    <FieldsetLegend>Detalle de accesos</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {datos.detalle.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Sin datos en el periodo seleccionado.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Fecha y hora
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            No. control
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            RFC
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Carrera
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Semestre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Movimiento
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {datos.detalle.map((fila, indice) => (
                                        <tr
                                            key={indice}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {fila.fecha} · {fila.hora}
                                            </td>
                                            <td className="px-3 py-2">
                                                {fila.nombre}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {fila.noDeControl ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {fila.rfc ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {fila.carrera ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {fila.semestre
                                                    ? `${fila.semestre}°`
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Badge
                                                    className={cn(
                                                        'rounded-full border-transparent font-semibold',
                                                        fila.tipoMovimiento ===
                                                            'ENTRADA'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                                                    )}
                                                >
                                                    {fila.tipoMovimiento ===
                                                    'ENTRADA'
                                                        ? 'Entrada'
                                                        : 'Salida'}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </Fieldset>

                <Fieldset className="break-inside-avoid">
                    <FieldsetLegend>Entradas por día</FieldsetLegend>
                    <AccesosPorDiaChart data={datos.serieDiaria} />
                </Fieldset>

                <div className="flex flex-wrap items-center justify-center gap-2 pt-2 print:hidden">
                    <Button type="button" variant="outline" asChild>
                        <a href={reportes.pdf(reporte.id).url}>
                            <Download />
                            Descargar PDF
                        </a>
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <a href={reportes.excel(reporte.id).url}>
                            <SheetIcon />
                            Exportar Excel
                        </a>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => window.print()}
                    >
                        <Printer />
                        Imprimir
                    </Button>
                    <Button
                        type="button"
                        onClick={() => setCompartirAbierto(true)}
                    >
                        <Share2 />
                        Compartir
                    </Button>
                </div>
            </main>

            <Dialog open={compartirAbierto} onOpenChange={setCompartirAbierto}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Compartir reporte</DialogTitle>
                    </DialogHeader>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="correo-compartir">
                            Correo institucional
                        </Label>
                        <div className="relative">
                            <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="correo-compartir"
                                type="email"
                                className="pl-9"
                                value={correo}
                                onChange={(evento) =>
                                    setCorreo(evento.target.value)
                                }
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            disabled={enviando}
                            onClick={enviarPorCorreo}
                        >
                            Enviar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
