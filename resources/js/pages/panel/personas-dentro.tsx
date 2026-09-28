import { Head, router } from '@inertiajs/react';
import { Eraser, Eye, Search, UsersRound } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { dashboard, personasDentro } from '@/routes';
import type {
    PersonaDentroFila,
    PersonasDentroFiltros,
    PersonasDentroProps,
} from '@/types';

const FILTRO_TODOS = 'todos';
const INTERVALO_ACTUALIZACION_MS = 15_000;

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

function obtenerIniciales(nombre: string): string {
    const iniciales = nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0]?.toUpperCase())
        .join('');

    return iniciales || '?';
}

function DetallePersonaDialog({
    persona,
    onOpenChange,
}: {
    persona: PersonaDentroFila | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={persona !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {persona && (
                    <>
                        <DialogHeader>
                            <div className="flex items-center gap-3">
                                <Avatar className="size-12">
                                    <AvatarFallback>
                                        {obtenerIniciales(persona.nombre)}
                                    </AvatarFallback>
                                </Avatar>
                                <DialogTitle>{persona.nombre}</DialogTitle>
                            </div>
                        </DialogHeader>

                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    No. de control
                                </dt>
                                <dd className="font-medium">
                                    {persona.noDeControl ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">RFC</dt>
                                <dd className="font-medium">
                                    {persona.rfc ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Carrera
                                </dt>
                                <dd className="font-medium">
                                    {persona.carrera ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Semestre
                                </dt>
                                <dd className="font-medium">
                                    {persona.semestre
                                        ? `${persona.semestre}°`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Estado actual
                                </dt>
                                <dd className="font-medium">
                                    {persona.estadoActual}
                                </dd>
                            </div>
                            <div className="col-span-2">
                                <dt className="text-muted-foreground">
                                    Última entrada
                                </dt>
                                <dd className="font-medium">
                                    {persona.fechaEntrada} ·{' '}
                                    {persona.horaEntrada}
                                </dd>
                            </div>
                        </dl>

                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cerrar
                                </Button>
                            </DialogClose>
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function PersonasDentro({
    personas,
    filtros,
    opciones,
    totalDentro,
    actualizadoEn,
}: PersonasDentroProps) {
    const [busqueda, setBusqueda] = useState(filtros.buscar);
    const [personaSeleccionada, setPersonaSeleccionada] =
        useState<PersonaDentroFila | null>(null);

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

    // Refresca la lista periódicamente para que las personas que salen
    // desaparezcan de la tabla sin que el usuario tenga que recargar.
    useEffect(() => {
        const intervalo = setInterval(() => {
            router.reload({
                only: [
                    'personas',
                    'filtros',
                    'opciones',
                    'totalDentro',
                    'actualizadoEn',
                ],
            });
        }, INTERVALO_ACTUALIZACION_MS);

        return () => clearInterval(intervalo);
    }, []);

    function navegar(
        cambios: Partial<PersonasDentroFiltros> & { pagina?: number },
    ) {
        const siguiente: PersonasDentroFiltros = {
            buscar: cambios.buscar ?? filtros.buscar,
            carrera: cambios.carrera ?? filtros.carrera,
            semestre: cambios.semestre ?? filtros.semestre,
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

        if (cambios.pagina && cambios.pagina > 1) {
            query.pagina = cambios.pagina;
        }

        router.get(personasDentro().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function limpiarFiltros() {
        navegar({ buscar: '', carrera: '', semestre: '' });
    }

    const hayFiltrosActivos =
        Boolean(filtros.buscar) ||
        Boolean(filtros.carrera) ||
        Boolean(filtros.semestre);

    const paginas = construirPaginas(
        personas.meta.paginaActual,
        personas.meta.ultimaPagina,
    );

    return (
        <>
            <Head title="Personas dentro" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Personas dentro de la biblioteca
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Personas que actualmente se encuentran dentro de la
                        biblioteca.
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-[280px_1fr]">
                    <Card className="border-l-4 border-l-orange-500 py-4 dark:border-l-orange-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-14 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-950">
                                <UsersRound className="size-7 text-orange-500 dark:text-orange-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-3xl font-semibold tabular-nums">
                                    {totalDentro.toLocaleString('es-MX')}
                                </span>
                                <span className="text-sm font-medium">
                                    Personas dentro
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Actualmente en la biblioteca
                                </span>
                            </div>
                        </CardContent>
                    </Card>

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
                                        <SelectTrigger className="w-48">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={FILTRO_TODOS}>
                                                Todas las carreras
                                            </SelectItem>
                                            {opciones.carreras.map(
                                                (carrera) => (
                                                    <SelectItem
                                                        key={carrera.value}
                                                        value={carrera.value}
                                                    >
                                                        {carrera.label}
                                                    </SelectItem>
                                                ),
                                            )}
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
                                            {opciones.semestres.map(
                                                (semestre) => (
                                                    <SelectItem
                                                        key={semestre.value}
                                                        value={semestre.value}
                                                    >
                                                        {semestre.label}
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
                </div>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Personas actualmente dentro</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {personas.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                No hay personas dentro de la biblioteca con los
                                filtros seleccionados.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Foto
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Número de control
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
                                            Hora de entrada
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {personas.data.map((persona) => (
                                        <tr
                                            key={persona.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2">
                                                <Avatar>
                                                    <AvatarFallback>
                                                        {obtenerIniciales(
                                                            persona.nombre,
                                                        )}
                                                    </AvatarFallback>
                                                </Avatar>
                                            </td>
                                            <td className="px-3 py-2">
                                                {persona.nombre}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {persona.noDeControl ?? '—'}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {persona.rfc ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {persona.carrera ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {persona.semestre
                                                    ? `${persona.semestre}°`
                                                    : '—'}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {persona.horaEntrada}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setPersonaSeleccionada(
                                                            persona,
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

                    <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                        <p className="text-xs text-muted-foreground">
                            Actualizado automáticamente · {actualizadoEn}
                        </p>

                        {personas.data.length > 0 && (
                            <div className="flex flex-wrap items-center gap-4">
                                <p className="text-sm text-muted-foreground">
                                    Mostrando {personas.meta.desde} a{' '}
                                    {personas.meta.hasta} de{' '}
                                    {personas.meta.total} personas
                                </p>

                                <div className="flex items-center gap-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        disabled={
                                            personas.meta.paginaActual <= 1
                                        }
                                        onClick={() =>
                                            navegar({
                                                pagina:
                                                    personas.meta.paginaActual -
                                                    1,
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
                                                    personas.meta.paginaActual
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="icon"
                                                onClick={() =>
                                                    navegar({ pagina })
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
                                            personas.meta.paginaActual >=
                                            personas.meta.ultimaPagina
                                        }
                                        onClick={() =>
                                            navegar({
                                                pagina:
                                                    personas.meta.paginaActual +
                                                    1,
                                            })
                                        }
                                    >
                                        ›
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </Fieldset>
            </div>

            <DetallePersonaDialog
                persona={personaSeleccionada}
                onOpenChange={(abierto) =>
                    !abierto && setPersonaSeleccionada(null)
                }
            />
        </>
    );
}

PersonasDentro.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Personas dentro', href: personasDentro() },
    ],
};
