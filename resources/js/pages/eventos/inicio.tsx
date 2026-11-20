import { Head, router } from '@inertiajs/react';
import {
    CalendarClock,
    DoorOpen,
    Eye,
    LogOut as LogOutIcon,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { cn } from '@/lib/utils';
import { panel } from '@/routes/eventos';
import type { AsistenciaFila, InicioProps } from '@/types';

function construirPaginas(
    actual: number,
    ultima: number,
): (number | 'salto')[] {
    if (ultima <= 7) {
        return Array.from({ length: ultima }, (_, indice) => indice + 1);
    }

    const paginas = new Set<number>([1, 2, 3, ultima]);

    for (let pagina = actual - 1; pagina <= actual + 1; pagina++) {
        if (pagina >= 1 && pagina <= ultima) {
            paginas.add(pagina);
        }
    }

    const ordenadas = Array.from(paginas).sort((a, b) => a - b);
    const resultado: (number | 'salto')[] = [];

    ordenadas.forEach((pagina, indice) => {
        if (indice > 0 && pagina - ordenadas[indice - 1] > 1) {
            resultado.push('salto');
        }

        resultado.push(pagina);
    });

    return resultado;
}

function BadgeMovimiento({
    movimiento,
}: {
    movimiento: AsistenciaFila['tipoMovimiento'];
}) {
    return (
        <Badge
            className={cn(
                'rounded-full border-transparent font-semibold',
                movimiento === 'ENTRADA'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                    : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            )}
        >
            {movimiento === 'ENTRADA' ? 'Entrada' : 'Salida'}
        </Badge>
    );
}

function DetalleAsistenciaDialog({
    registro,
    onOpenChange,
}: {
    registro: AsistenciaFila | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={registro !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {registro && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{registro.nombre}</DialogTitle>
                        </DialogHeader>

                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha y hora
                                </dt>
                                <dd className="font-medium">
                                    {registro.fecha} · {registro.hora}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    No. de control
                                </dt>
                                <dd className="font-medium">
                                    {registro.noDeControl}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Evento
                                </dt>
                                <dd className="font-medium">
                                    {registro.evento ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Movimiento
                                </dt>
                                <dd>
                                    <BadgeMovimiento
                                        movimiento={registro.tipoMovimiento}
                                    />
                                </dd>
                            </div>
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function EventosInicio({ resumen, asistencia }: InicioProps) {
    const [registroSeleccionado, setRegistroSeleccionado] =
        useState<AsistenciaFila | null>(null);

    const paginas = construirPaginas(
        asistencia.meta.paginaActual,
        asistencia.meta.ultimaPagina,
    );

    function navegar(pagina: number) {
        router.get(
            panel().url,
            { pagina },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Inicio" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Resumen general
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Eventos Académicos y Tutorías
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card className="border-l-4 border-l-blue-500 py-4 lg:col-span-1 dark:border-l-blue-400">
                        <CardContent className="flex flex-col gap-1">
                            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Evento activo
                            </span>
                            {resumen.eventoActivo ? (
                                <>
                                    <span className="font-semibold">
                                        {resumen.eventoActivo.nombre}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {resumen.eventoActivo.horaInicio} -{' '}
                                        {resumen.eventoActivo.horaFin}
                                        {resumen.eventoActivo.tutor &&
                                            ` · ${resumen.eventoActivo.tutor}`}
                                    </span>
                                </>
                            ) : (
                                <span className="text-sm text-muted-foreground">
                                    Sin evento activo
                                </span>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-orange-500 py-4 dark:border-l-orange-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-950">
                                <Users className="size-6 text-orange-500 dark:text-orange-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.asistentesHoy}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Asistentes hoy
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-emerald-500 py-4 dark:border-l-emerald-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950">
                                <DoorOpen className="size-6 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.entradasHoy}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Entradas registradas
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-red-500 py-4 dark:border-l-red-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-950">
                                <LogOutIcon className="size-6 text-red-500 dark:text-red-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.salidasHoy}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Salidas registradas
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-amber-500 py-4 dark:border-l-amber-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950">
                                <CalendarClock className="size-6 text-amber-500 dark:text-amber-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.pendientesSalida}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Pendientes de salida
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Asistencia</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {asistencia.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Todavía no hay registros de asistencia hoy.
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
                                            Evento
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tipo movimiento
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {asistencia.data.map((registro) => (
                                        <tr
                                            key={registro.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {registro.fecha} ·{' '}
                                                {registro.hora}
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.nombre}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {registro.noDeControl}
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.evento ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <BadgeMovimiento
                                                    movimiento={
                                                        registro.tipoMovimiento
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setRegistroSeleccionado(
                                                            registro,
                                                        )
                                                    }
                                                >
                                                    <Eye />
                                                    Ver
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {asistencia.data.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {asistencia.meta.desde} a{' '}
                                {asistencia.meta.hasta} de{' '}
                                {asistencia.meta.total} registros
                            </p>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={asistencia.meta.paginaActual <= 1}
                                    onClick={() =>
                                        navegar(
                                            asistencia.meta.paginaActual - 1,
                                        )
                                    }
                                >
                                    ‹
                                </Button>

                                {paginas.map((pagina, indice) =>
                                    pagina === 'salto' ? (
                                        <span
                                            key={`salto-${indice}`}
                                            className="px-2 text-sm text-muted-foreground"
                                        >
                                            …
                                        </span>
                                    ) : (
                                        <Button
                                            key={pagina}
                                            type="button"
                                            variant={
                                                pagina ===
                                                asistencia.meta.paginaActual
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="icon"
                                            onClick={() => navegar(pagina)}
                                        >
                                            {pagina}
                                        </Button>
                                    ),
                                )}

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={
                                        asistencia.meta.paginaActual >=
                                        asistencia.meta.ultimaPagina
                                    }
                                    onClick={() =>
                                        navegar(
                                            asistencia.meta.paginaActual + 1,
                                        )
                                    }
                                >
                                    ›
                                </Button>
                            </div>
                        </div>
                    )}
                </Fieldset>
            </div>

            <DetalleAsistenciaDialog
                registro={registroSeleccionado}
                onOpenChange={(abierto) =>
                    !abierto && setRegistroSeleccionado(null)
                }
            />
        </>
    );
}

EventosInicio.layout = {
    breadcrumbs: [{ title: 'Inicio', href: panel() }],
};
