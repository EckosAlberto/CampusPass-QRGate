import { Head, Link, router } from '@inertiajs/react';
import {
    Download,
    Eraser,
    Eye,
    FileText,
    Printer,
    Sheet as SheetIcon,
    Trash2,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard, reportes as reportesIndex } from '@/routes';
import reportes from '@/routes/reportes';
import type { ReporteBibliotecaFila, ReportesBibliotecaProps } from '@/types';

const FILTRO_TODOS = 'todos';

type FiltrosForm = {
    fecha_inicial: string;
    fecha_final: string;
    carrera: string;
    semestre: string;
    sexo: string;
    tipo_movimiento: string;
    tipo: string;
};

const FILTROS_VACIOS: FiltrosForm = {
    fecha_inicial: '',
    fecha_final: '',
    carrera: '',
    semestre: '',
    sexo: '',
    tipo_movimiento: '',
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

function CampoFiltro({
    etiqueta,
    children,
}: {
    etiqueta: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-xs font-medium text-muted-foreground">
                {etiqueta}
            </span>
            {children}
        </div>
    );
}

export default function Reportes({
    usuarioId,
    reportes: listado,
    catalogos,
}: ReportesBibliotecaProps) {
    const [filtros, setFiltros] = useState<FiltrosForm>(FILTROS_VACIOS);
    const [generando, setGenerando] = useState(false);
    const [reporteAEliminar, setReporteAEliminar] =
        useState<ReporteBibliotecaFila | null>(null);

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
            reportes.store().url,
            {
                fecha_inicial: filtros.fecha_inicial,
                fecha_final: filtros.fecha_final,
                carrera: filtros.carrera || null,
                semestre: filtros.semestre || null,
                sexo: filtros.sexo || null,
                tipo_movimiento: filtros.tipo_movimiento || null,
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

    function confirmarEliminar() {
        if (!reporteAEliminar) {
            return;
        }

        router.delete(reportes.destroy(reporteAEliminar.id).url, {
            preserveScroll: true,
            onSuccess: () => toast.success('Reporte eliminado.'),
        });
        setReporteAEliminar(null);
    }

    const paginas = construirPaginas(
        listado.meta.paginaActual,
        listado.meta.ultimaPagina,
    );

    return (
        <>
            <Head title="Reportes" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-3xl font-extrabold tracking-tight text-primary uppercase">
                        Consulta de reportes
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Genera y consulta los reportes de accesos a la
                        biblioteca.
                    </p>
                </div>

                <Fieldset>
                    <FieldsetLegend>Búsqueda por</FieldsetLegend>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <CampoFiltro etiqueta="Fecha inicial">
                            <Input
                                type="date"
                                value={filtros.fecha_inicial}
                                onChange={(evt) =>
                                    actualizar(
                                        'fecha_inicial',
                                        evt.target.value,
                                    )
                                }
                            />
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Fecha final">
                            <Input
                                type="date"
                                value={filtros.fecha_final}
                                onChange={(evt) =>
                                    actualizar('fecha_final', evt.target.value)
                                }
                            />
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Carrera">
                            <Select
                                value={filtros.carrera || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    actualizar(
                                        'carrera',
                                        valor === FILTRO_TODOS ? '' : valor,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todas las carreras
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
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Semestre">
                            <Select
                                value={filtros.semestre || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    actualizar(
                                        'semestre',
                                        valor === FILTRO_TODOS ? '' : valor,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todos los semestres
                                    </SelectItem>
                                    {catalogos.semestres.map((semestre) => (
                                        <SelectItem
                                            key={semestre.value}
                                            value={semestre.value}
                                        >
                                            {semestre.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Sexo">
                            <Select
                                value={filtros.sexo || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    actualizar(
                                        'sexo',
                                        valor === FILTRO_TODOS ? '' : valor,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todos
                                    </SelectItem>
                                    {catalogos.sexos.map((sexo) => (
                                        <SelectItem
                                            key={sexo.value}
                                            value={sexo.value}
                                        >
                                            {sexo.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Tipo de movimiento">
                            <Select
                                value={filtros.tipo_movimiento || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    actualizar(
                                        'tipo_movimiento',
                                        valor === FILTRO_TODOS ? '' : valor,
                                    )
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todos
                                    </SelectItem>
                                    {catalogos.movimientos.map((movimiento) => (
                                        <SelectItem
                                            key={movimiento.value}
                                            value={movimiento.value}
                                        >
                                            {movimiento.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Tipo de reporte">
                            <Select
                                value={filtros.tipo}
                                onValueChange={(valor) =>
                                    actualizar('tipo', valor)
                                }
                            >
                                <SelectTrigger className="w-full">
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
                        </CampoFiltro>
                    </div>

                    <div className="mt-4 flex items-center justify-end gap-2">
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
                            <FileText />
                            Generar reporte
                        </Button>
                    </div>
                </Fieldset>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Reportes generados</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {listado.data.length === 0 ? (
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
                                            Tipo de reporte
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
                                    {listado.data.map((reporte) => {
                                        const puedeEliminar =
                                            reporte.creadoPor === usuarioId;

                                        return (
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
                                                <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                    {reporte.fechaGeneracion}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {reporte.generadoPor ?? '—'}
                                                </td>
                                                <td className="px-3 py-2 tabular-nums">
                                                    {reporte.registros}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            aria-label="Ver reporte"
                                                            title="Ver reporte"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={reportes.show(
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
                                                            aria-label="Imprimir reporte"
                                                            title="Imprimir reporte"
                                                            asChild
                                                        >
                                                            <a
                                                                href={`${reportes.show(reporte.id).url}?imprimir=1`}
                                                            >
                                                                <Printer />
                                                            </a>
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            aria-label="Descargar PDF"
                                                            title="Descargar PDF"
                                                            asChild
                                                        >
                                                            <a
                                                                href={
                                                                    reportes.pdf(
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
                                                            aria-label="Exportar Excel"
                                                            title="Exportar Excel"
                                                            asChild
                                                        >
                                                            <a
                                                                href={
                                                                    reportes.excel(
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
                                                            aria-label="Eliminar reporte"
                                                            title={
                                                                puedeEliminar
                                                                    ? 'Eliminar reporte'
                                                                    : 'Solo quien generó este reporte puede eliminarlo'
                                                            }
                                                            disabled={
                                                                !puedeEliminar
                                                            }
                                                            onClick={() =>
                                                                setReporteAEliminar(
                                                                    reporte,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {listado.data.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {listado.meta.desde} a{' '}
                                {listado.meta.hasta} de {listado.meta.total}{' '}
                                reportes
                            </p>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={listado.meta.paginaActual <= 1}
                                    onClick={() =>
                                        router.get(
                                            reportesIndex().url,
                                            {
                                                pagina:
                                                    listado.meta.paginaActual -
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
                                                listado.meta.paginaActual
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="icon"
                                            onClick={() =>
                                                router.get(
                                                    reportesIndex().url,
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
                                        listado.meta.paginaActual >=
                                        listado.meta.ultimaPagina
                                    }
                                    onClick={() =>
                                        router.get(
                                            reportesIndex().url,
                                            {
                                                pagina:
                                                    listado.meta.paginaActual +
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

            <Dialog
                open={reporteAEliminar !== null}
                onOpenChange={(abierto) =>
                    !abierto && setReporteAEliminar(null)
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar reporte</DialogTitle>
                    </DialogHeader>

                    <p className="text-sm text-muted-foreground">
                        ¿Seguro que deseas eliminar el reporte{' '}
                        <span className="font-medium text-foreground">
                            {reporteAEliminar?.nombre}
                        </span>
                        ? Esta acción no se puede deshacer.
                    </p>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmarEliminar}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Reportes.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Reportes', href: reportesIndex() },
    ],
};
