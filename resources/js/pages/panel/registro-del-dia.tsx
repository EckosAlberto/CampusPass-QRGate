import { Head, router } from '@inertiajs/react';
import { Eraser, Eye, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import { dashboard, registroDelDia } from '@/routes';
import type {
    RegistroDelDiaFila,
    RegistroDelDiaFiltros,
    RegistroDelDiaProps,
} from '@/types';

const FILTRO_TODOS = 'todos';

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
    movimiento: RegistroDelDiaFila['movimiento'];
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

function etiquetaSexo(sexo: RegistroDelDiaFila['sexo']): string {
    if (sexo === 'H') {
        return 'Hombre';
    }

    if (sexo === 'M') {
        return 'Mujer';
    }

    return '—';
}

function DetalleRegistroDialog({
    registro,
    onOpenChange,
}: {
    registro: RegistroDelDiaFila | null;
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
                                    {registro.noDeControl ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">RFC</dt>
                                <dd className="font-medium">
                                    {registro.rfc ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Carrera
                                </dt>
                                <dd className="font-medium">
                                    {registro.carrera ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Semestre
                                </dt>
                                <dd className="font-medium">
                                    {registro.semestre
                                        ? `${registro.semestre}°`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Movimiento
                                </dt>
                                <dd>
                                    <BadgeMovimiento
                                        movimiento={registro.movimiento}
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Tipo</dt>
                                <dd className="font-medium">{registro.tipo}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Sexo</dt>
                                <dd className="font-medium">
                                    {etiquetaSexo(registro.sexo)}
                                </dd>
                            </div>
                            <div className="col-span-2">
                                <dt className="text-muted-foreground">
                                    Tiempo de permanencia
                                </dt>
                                <dd className="font-medium">
                                    {registro.tiempoPermanencia ?? '—'}
                                </dd>
                            </div>
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function RegistroDelDia({
    registros,
    filtros,
    opciones,
}: RegistroDelDiaProps) {
    const [busqueda, setBusqueda] = useState(filtros.buscar);
    const [registroSeleccionado, setRegistroSeleccionado] =
        useState<RegistroDelDiaFila | null>(null);

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
        cambios: Partial<RegistroDelDiaFiltros> & { pagina?: number },
    ) {
        const siguiente: RegistroDelDiaFiltros = {
            buscar: cambios.buscar ?? filtros.buscar,
            carrera: cambios.carrera ?? filtros.carrera,
            semestre: cambios.semestre ?? filtros.semestre,
            sexo: cambios.sexo ?? filtros.sexo,
            movimiento: cambios.movimiento ?? filtros.movimiento,
        };

        const query: Record<string, string | number> = {};

        if (siguiente.buscar) {
            query.buscar = siguiente.buscar;
        }

        if (siguiente.carrera) {
            query.carrera = siguiente.carrera;
        }

        if (siguiente.semestre) {
            query.semestre = siguiente.semestre;
        }

        if (siguiente.sexo) {
            query.sexo = siguiente.sexo;
        }

        if (siguiente.movimiento) {
            query.movimiento = siguiente.movimiento;
        }

        if (cambios.pagina && cambios.pagina > 1) {
            query.pagina = cambios.pagina;
        }

        router.get(registroDelDia().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function limpiarFiltros() {
        navegar({
            buscar: '',
            carrera: '',
            semestre: '',
            sexo: '',
            movimiento: '',
        });
    }

    const hayFiltrosActivos =
        Boolean(filtros.buscar) ||
        Boolean(filtros.carrera) ||
        Boolean(filtros.semestre) ||
        Boolean(filtros.sexo) ||
        Boolean(filtros.movimiento);

    const paginas = construirPaginas(
        registros.meta.paginaActual,
        registros.meta.ultimaPagina,
    );

    return (
        <>
            <Head title="Registro del día" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Registros del día
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Centro de Información — Biblioteca
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
                                    placeholder="Buscar por nombre, número de control o RFC..."
                                    className="pl-9"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Carrera
                                </span>
                                <Select
                                    value={filtros.carrera || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        navegar({
                                            carrera:
                                                valor === FILTRO_TODOS
                                                    ? ''
                                                    : valor,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todas
                                        </SelectItem>
                                        {opciones.carreras.map((carrera) => (
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
                                <span className="text-xs font-medium text-muted-foreground">
                                    Semestre
                                </span>
                                <Select
                                    value={filtros.semestre || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        navegar({
                                            semestre:
                                                valor === FILTRO_TODOS
                                                    ? ''
                                                    : valor,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-32">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todos
                                        </SelectItem>
                                        {opciones.semestres.map((semestre) => (
                                            <SelectItem
                                                key={semestre.value}
                                                value={semestre.value}
                                            >
                                                {semestre.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Sexo
                                </span>
                                <Select
                                    value={filtros.sexo || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        navegar({
                                            sexo:
                                                valor === FILTRO_TODOS
                                                    ? ''
                                                    : valor,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-32">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todos
                                        </SelectItem>
                                        {opciones.sexos.map((sexo) => (
                                            <SelectItem
                                                key={sexo.value}
                                                value={sexo.value}
                                            >
                                                {sexo.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <span className="text-xs font-medium text-muted-foreground">
                                    Movimiento
                                </span>
                                <Select
                                    value={filtros.movimiento || FILTRO_TODOS}
                                    onValueChange={(valor) =>
                                        navegar({
                                            movimiento:
                                                valor === FILTRO_TODOS
                                                    ? ''
                                                    : valor,
                                        })
                                    }
                                >
                                    <SelectTrigger className="w-32">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={FILTRO_TODOS}>
                                            Todos
                                        </SelectItem>
                                        {opciones.movimientos.map(
                                            (movimiento) => (
                                                <SelectItem
                                                    key={movimiento.value}
                                                    value={movimiento.value}
                                                >
                                                    {movimiento.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
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
                    <FieldsetLegend>Registros</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {registros.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                No se encontraron registros con los filtros
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
                                            Nombre completo
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
                                            Sexo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Movimiento
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tipo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tiempo permanencia
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {registros.data.map((registro) => (
                                        <tr
                                            key={registro.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {registro.fecha}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {registro.hora}
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.nombre}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {registro.noDeControl ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {registro.rfc ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.carrera ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.semestre
                                                    ? `${registro.semestre}°`
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {etiquetaSexo(registro.sexo)}
                                            </td>
                                            <td className="px-3 py-2">
                                                <BadgeMovimiento
                                                    movimiento={
                                                        registro.movimiento
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                {registro.tipo}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {registro.tiempoPermanencia ??
                                                    '-'}
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

                    {registros.data.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {registros.meta.desde} a{' '}
                                {registros.meta.hasta} de {registros.meta.total}{' '}
                                registros
                            </p>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    disabled={registros.meta.paginaActual <= 1}
                                    onClick={() =>
                                        navegar({
                                            pagina:
                                                registros.meta.paginaActual - 1,
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
                                                registros.meta.paginaActual
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
                                        registros.meta.paginaActual >=
                                        registros.meta.ultimaPagina
                                    }
                                    onClick={() =>
                                        navegar({
                                            pagina:
                                                registros.meta.paginaActual + 1,
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

            <DetalleRegistroDialog
                registro={registroSeleccionado}
                onOpenChange={(abierto) =>
                    !abierto && setRegistroSeleccionado(null)
                }
            />
        </>
    );
}

RegistroDelDia.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Registro del día', href: registroDelDia() },
    ],
};
