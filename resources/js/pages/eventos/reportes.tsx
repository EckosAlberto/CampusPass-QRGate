import { Head, Link, router } from '@inertiajs/react';
import {
    Download,
    Eraser,
    Eye,
    Printer,
    Sheet as SheetIcon,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import reportesRoutes from '@/routes/eventos/reportes';
import type { ReportesProps } from '@/types';

const FILTRO_TODOS = 'todos';

type FiltrosForm = {
    fk_id_evento: string;
    fecha_inicial: string;
    fecha_final: string;
    carrera: string;
    tipo: string;
};

const FILTROS_VACIOS: FiltrosForm = {
    fk_id_evento: '',
    fecha_inicial: '',
    fecha_final: '',
    carrera: '',
    tipo: 'resumen_general',
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

export default function Reportes({
    usuarioId,
    reportes,
    catalogos,
}: ReportesProps) {
    const [filtros, setFiltros] = useState<FiltrosForm>(FILTROS_VACIOS);
    const [generando, setGenerando] = useState(false);

    function actualizar<K extends keyof FiltrosForm>(campo: K, valor: string) {
        setFiltros((actual) => ({ ...actual, [campo]: valor }));
    }

    function limpiarFiltros() {
        setFiltros(FILTROS_VACIOS);
    }

    function generarReporte() {
        if (!filtros.fecha_inicial || !filtros.fecha_final) {
            toast.error('Selecciona la fecha inicial y final.');

            return;
        }

        setGenerando(true);

        router.post(
            reportesRoutes.store().url,
            {
                fk_id_evento: filtros.fk_id_evento || null,
                fecha_inicial: filtros.fecha_inicial,
                fecha_final: filtros.fecha_final,
                carrera: filtros.carrera || null,
                tipo: filtros.tipo,
            },
            {
                onError: (errores) =>
                    toast.error(
                        Object.values(errores)[0] ??
                            'No se pudo generar el reporte.',
                    ),
                onFinish: () => setGenerando(false),
            },
        );
    }

    function eliminar(id: string, nombre: string) {
        if (!window.confirm(`¿Eliminar el reporte "${nombre}"?`)) {
            return;
        }

        router.delete(reportesRoutes.destroy(id).url, {
            preserveScroll: true,
        });
    }

    const paginas = construirPaginas(
        reportes.meta.paginaActual,
        reportes.meta.ultimaPagina,
    );

    return (
        <>
            <Head title="Reportes" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Reportes
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Genera reportes de asistencia a partir de los registros
                        de acceso.
                    </p>
                </div>

                <Fieldset>
                    <FieldsetLegend>Búsqueda por</FieldsetLegend>

                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div className="flex flex-1 flex-wrap items-end gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Evento
                                </Label>
                                <Select
                                    value={filtros.fk_id_evento || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        actualizar(
                                            'fk_id_evento',
                                            valor === FILTRO_TODOS ? '' : valor,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todos
                                        </SelectItem>
                                        {catalogos.eventos.map((evento) => (
                                            <SelectItem
                                                key={evento.value}
                                                value={evento.value}
                                            >
                                                {evento.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Fecha inicial
                                </Label>
                                <Input
                                    type="date"
                                    value={filtros.fecha_inicial}
                                    onChange={(evt) =>
                                        actualizar(
                                            'fecha_inicial',
                                            evt.target.value,
                                        )
                                    }
                                    className="w-40"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Fecha final
                                </Label>
                                <Input
                                    type="date"
                                    value={filtros.fecha_final}
                                    onChange={(evt) =>
                                        actualizar(
                                            'fecha_final',
                                            evt.target.value,
                                        )
                                    }
                                    className="w-40"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Carrera
                                </Label>
                                <Select
                                    value={filtros.carrera || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        actualizar(
                                            'carrera',
                                            valor === FILTRO_TODOS ? '' : valor,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todas
                                        </SelectItem>
                                        {catalogos.carreras.map((carrera) => (
                                            <SelectItem
                                                key={carrera.value}
                                                value={carrera.value}
                                            >
                                                {carrera.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Tipo de reporte
                                </Label>
                                <Select
                                    value={filtros.tipo}
                                    onValueChange={(valor) =>
                                        actualizar('tipo', valor)
                                    }
                                >
                                    <SelectTrigger className="w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {catalogos.tipos.map((tipo) => (
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
                        </div>

                        <div className="flex shrink-0 items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={limpiarFiltros}
                                aria-label="Limpiar filtros"
                                title="Limpiar filtros"
                            >
                                <Eraser />
                            </Button>
                            <Button
                                type="button"
                                onClick={generarReporte}
                                disabled={generando}
                            >
                                Generar reporte
                            </Button>
                        </div>
                    </div>
                </Fieldset>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Reportes generados</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {reportes.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Todavía no se ha generado ningún reporte.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Nombre del reporte
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tipo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Periodo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Fecha de generación
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Generado por
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Registros
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reportes.data.map((reporte) => (
                                        <tr
                                            key={reporte.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2">
                                                {reporte.nombre}
                                            </td>
                                            <td className="px-3 py-2">
                                                {reporte.tipoLabel}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap">
                                                {reporte.periodo}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap">
                                                {reporte.fechaGeneracion}
                                            </td>
                                            <td className="px-3 py-2">
                                                {reporte.generadoPor ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {reporte.registros}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-1.5">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Ver"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={reportesRoutes.show(
                                                                reporte.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Imprimir"
                                                        asChild
                                                    >
                                                        <a
                                                            href={`${reportesRoutes.show(reporte.id).url}?imprimir=1`}
                                                        >
                                                            <Printer />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Descargar PDF"
                                                        asChild
                                                    >
                                                        <a
                                                            href={
                                                                reportesRoutes.pdf(
                                                                    reporte.id,
                                                                ).url
                                                            }
                                                        >
                                                            <Download />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Exportar Excel"
                                                        asChild
                                                    >
                                                        <a
                                                            href={
                                                                reportesRoutes.excel(
                                                                    reporte.id,
                                                                ).url
                                                            }
                                                        >
                                                            <SheetIcon />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title={
                                                            reporte.creadoPor !==
                                                            usuarioId
                                                                ? 'Solo quien generó el reporte puede eliminarlo'
                                                                : 'Eliminar'
                                                        }
                                                        disabled={
                                                            reporte.creadoPor !==
                                                            usuarioId
                                                        }
                                                        onClick={() =>
                                                            eliminar(
                                                                reporte.id,
                                                                reporte.nombre,
                                                            )
                                                        }
                                                        className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {reportes.data.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {reportes.meta.desde} a{' '}
                                {reportes.meta.hasta} de {reportes.meta.total}{' '}
                                reportes
                            </p>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={reportes.meta.paginaActual <= 1}
                                    onClick={() =>
                                        router.get(
                                            reportesRoutes.index().url,
                                            {
                                                pagina:
                                                    reportes.meta.paginaActual -
                                                    1,
                                            },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
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
                                                reportes.meta.paginaActual
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="icon"
                                            onClick={() =>
                                                router.get(
                                                    reportesRoutes.index().url,
                                                    { pagina },
                                                    {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    },
                                                )
                                            }
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
                                        reportes.meta.paginaActual >=
                                        reportes.meta.ultimaPagina
                                    }
                                    onClick={() =>
                                        router.get(
                                            reportesRoutes.index().url,
                                            {
                                                pagina:
                                                    reportes.meta.paginaActual +
                                                    1,
                                            },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
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
        </>
    );
}
