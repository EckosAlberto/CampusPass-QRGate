import { Head, router } from '@inertiajs/react';
import { Eraser, Eye, Search, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { dashboard, incidencias } from '@/routes';
import { destroy, destroyTodas } from '@/routes/incidencias';
import type {
    IncidenciaFila,
    IncidenciasFiltros,
    IncidenciasProps,
    TipoIncidencia,
} from '@/types';

const FILTRO_TODOS = 'todos';

const ESTILO_TIPO: Record<TipoIncidencia, { etiqueta: string; clase: string }> =
    {
        QR_INVALIDO: {
            etiqueta: 'Código QR inválido',
            clase: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        },
        NO_REGISTRADO: {
            etiqueta: 'No registrado',
            clase: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        },
        INACTIVO: {
            etiqueta: 'Estatus inactivo',
            clase: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
        },
        DOBLE_ESCANEO: {
            etiqueta: 'Doble escaneo',
            clase: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        },
    };

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

function BadgeTipo({ tipo }: { tipo: TipoIncidencia }) {
    const estilo = ESTILO_TIPO[tipo];

    return (
        <Badge
            className={cn(
                'rounded-full border-transparent font-semibold',
                estilo.clase,
            )}
        >
            {estilo.etiqueta}
        </Badge>
    );
}

function DetalleIncidenciaDialog({
    incidencia,
    onOpenChange,
}: {
    incidencia: IncidenciaFila | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={incidencia !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {incidencia && (
                    <>
                        <DialogHeader>
                            <DialogTitle>
                                {incidencia.nombre ?? 'Sin identificar'}
                            </DialogTitle>
                        </DialogHeader>

                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha y hora
                                </dt>
                                <dd className="font-medium">
                                    {incidencia.fecha} · {incidencia.hora}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    No. de control
                                </dt>
                                <dd className="font-medium">
                                    {incidencia.noDeControl ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Tipo</dt>
                                <dd>
                                    <BadgeTipo tipo={incidencia.tipo} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Ubicación
                                </dt>
                                <dd className="font-medium">
                                    {incidencia.ubicacion ?? '—'}
                                </dd>
                            </div>
                            <div className="col-span-2">
                                <dt className="text-muted-foreground">
                                    Mensaje
                                </dt>
                                <dd className="font-medium">
                                    {incidencia.mensaje ?? '—'}
                                </dd>
                            </div>
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function Incidencias({
    incidencias: listado,
    filtros,
    opciones,
}: IncidenciasProps) {
    const [busqueda, setBusqueda] = useState(filtros.buscar);
    const [incidenciaSeleccionada, setIncidenciaSeleccionada] =
        useState<IncidenciaFila | null>(null);

    // Mantiene el campo de búsqueda sincronizado si los filtros cambian desde
    // fuera (ej. navegación con el botón "atrás" del navegador). Ajustar el
    // estado durante el render (en vez de en un efecto) evita un re-render
    // en cascada; ver https://react.dev/learn/you-might-not-need-an-effect.
    const [ultimoBuscarDesdeProps, setUltimoBuscarDesdeProps] = useState(
        filtros.buscar,
    );

    if (filtros.buscar !== ultimoBuscarDesdeProps) {
        setUltimoBuscarDesdeProps(filtros.buscar);
        setBusqueda(filtros.buscar);
    }

    // Busca con un pequeño retraso mientras el usuario escribe, en vez de
    // consultar al servidor en cada tecla.
    useEffect(() => {
        if (busqueda === filtros.buscar) {
            return;
        }

        const temporizador = setTimeout(
            () => navegar({ buscar: busqueda }),
            400,
        );

        return () => clearTimeout(temporizador);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [busqueda]);

    function navegar(
        cambios: Partial<IncidenciasFiltros> & { pagina?: number },
    ) {
        const siguiente: IncidenciasFiltros = {
            buscar: cambios.buscar ?? filtros.buscar,
            tipo: cambios.tipo ?? filtros.tipo,
            fechaInicial: cambios.fechaInicial ?? filtros.fechaInicial,
            fechaFinal: cambios.fechaFinal ?? filtros.fechaFinal,
        };

        const query: Record<string, string | number> = {};

        if (siguiente.buscar) {
            query.buscar = siguiente.buscar;
        }

        if (siguiente.tipo) {
            query.tipo = siguiente.tipo;
        }

        if (siguiente.fechaInicial) {
            query.fechaInicial = siguiente.fechaInicial;
        }

        if (siguiente.fechaFinal) {
            query.fechaFinal = siguiente.fechaFinal;
        }

        if (cambios.pagina && cambios.pagina > 1) {
            query.pagina = cambios.pagina;
        }

        router.get(incidencias().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function limpiarFiltros() {
        navegar({ buscar: '', tipo: '', fechaInicial: '', fechaFinal: '' });
    }

    const hayFiltrosActivos =
        Boolean(filtros.buscar) ||
        Boolean(filtros.tipo) ||
        Boolean(filtros.fechaInicial) ||
        Boolean(filtros.fechaFinal);

    function eliminar(incidencia: IncidenciaFila) {
        if (
            !window.confirm(
                '¿Eliminar esta incidencia? Esta acción no se puede deshacer.',
            )
        ) {
            return;
        }

        router.delete(destroy(incidencia.id).url, {
            preserveScroll: true,
            onError: () => toast.error('No se pudo eliminar la incidencia.'),
        });
    }

    function eliminarTodas() {
        const mensaje = hayFiltrosActivos
            ? `¿Eliminar las ${listado.meta.total} incidencias que coinciden con los filtros actuales? Esta acción no se puede deshacer.`
            : `¿Eliminar las ${listado.meta.total} incidencias registradas? Esta acción no se puede deshacer.`;

        if (!window.confirm(mensaje)) {
            return;
        }

        router.delete(destroyTodas.url(), {
            data: {
                buscar: filtros.buscar || undefined,
                tipo: filtros.tipo || undefined,
                fechaInicial: filtros.fechaInicial || undefined,
                fechaFinal: filtros.fechaFinal || undefined,
            },
            preserveScroll: true,
            onError: () =>
                toast.error('No se pudieron eliminar las incidencias.'),
        });
    }

    const paginas = construirPaginas(
        listado.meta.paginaActual,
        listado.meta.ultimaPagina,
    );

    return (
        <>
            <Head title="Incidencias" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Informe de Incidencias
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Intentos de acceso rechazados o irregulares en la
                        biblioteca.
                    </p>
                </div>

                <Fieldset>
                    <FieldsetLegend>Búsqueda por</FieldsetLegend>

                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div className="flex flex-1 flex-wrap items-end gap-4">
                            <div className="relative min-w-56 flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={busqueda}
                                    onChange={(evento) =>
                                        setBusqueda(evento.target.value)
                                    }
                                    placeholder="Buscar por número de control o mensaje..."
                                    className="pl-9"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Tipo
                                </span>
                                <Select
                                    value={filtros.tipo || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        navegar({
                                            tipo:
                                                valor === FILTRO_TODOS
                                                    ? ''
                                                    : valor,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todos los tipos
                                        </SelectItem>
                                        {opciones.tipos.map((tipo) => (
                                            <SelectItem
                                                key={tipo.value}
                                                value={tipo.value}
                                            >
                                                {tipo.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Fecha inicial
                                </span>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={filtros.fechaInicial}
                                    onChange={(evento) =>
                                        navegar({
                                            fechaInicial: evento.target.value,
                                        })
                                    }
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Fecha final
                                </span>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={filtros.fechaFinal}
                                    onChange={(evento) =>
                                        navegar({
                                            fechaFinal: evento.target.value,
                                        })
                                    }
                                />
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={limpiarFiltros}
                            disabled={!hayFiltrosActivos}
                            aria-label="Limpiar filtros"
                            title="Limpiar filtros"
                            className="shrink-0"
                        >
                            <Eraser />
                        </Button>
                    </div>
                </Fieldset>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Incidencias registradas</FieldsetLegend>

                    {listado.data.length > 0 && (
                        <div className="flex justify-end">
                            <Button
                                type="button"
                                variant="destructive-outline"
                                size="sm"
                                onClick={eliminarTodas}
                            >
                                <Trash2 />
                                Eliminar todas
                            </Button>
                        </div>
                    )}

                    <div className="overflow-x-auto">
                        {listado.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                No se encontraron incidencias con los filtros
                                seleccionados.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Fecha
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Hora
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            No. control
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tipo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Mensaje
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {listado.data.map((incidencia) => (
                                        <tr
                                            key={incidencia.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {incidencia.fecha}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {incidencia.hora}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {incidencia.noDeControl ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {incidencia.nombre ??
                                                    'Sin identificar'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <BadgeTipo
                                                    tipo={incidencia.tipo}
                                                />
                                            </td>
                                            <td className="max-w-xs truncate px-3 py-2 text-muted-foreground">
                                                {incidencia.mensaje ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            setIncidenciaSeleccionada(
                                                                incidencia,
                                                            )
                                                        }
                                                    >
                                                        <Eye />
                                                        Ver
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="destructive-outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            eliminar(incidencia)
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Eliminar
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {listado.data.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {listado.meta.desde} a{' '}
                                {listado.meta.hasta} de {listado.meta.total}{' '}
                                incidencias
                            </p>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={listado.meta.paginaActual <= 1}
                                    onClick={() =>
                                        navegar({
                                            pagina:
                                                listado.meta.paginaActual - 1,
                                        })
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
                                                listado.meta.paginaActual
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="icon"
                                            onClick={() => navegar({ pagina })}
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
                                        listado.meta.paginaActual >=
                                        listado.meta.ultimaPagina
                                    }
                                    onClick={() =>
                                        navegar({
                                            pagina:
                                                listado.meta.paginaActual + 1,
                                        })
                                    }
                                >
                                    ›
                                </Button>
                            </div>
                        </div>
                    )}
                </Fieldset>
            </div>

            <DetalleIncidenciaDialog
                incidencia={incidenciaSeleccionada}
                onOpenChange={(abierto) =>
                    !abierto && setIncidenciaSeleccionada(null)
                }
            />
        </>
    );
}

Incidencias.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Incidencias', href: incidencias() },
    ],
};
