import { Head, router } from '@inertiajs/react';
import { Eraser, LogIn, LogOut, UserCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { AccesosPorDiaChart } from '@/components/dashboard/accesos-por-dia-chart';
import { DistribucionPorSexoChart } from '@/components/dashboard/distribucion-por-sexo-chart';
import { EntradasPorHoraChart } from '@/components/dashboard/entradas-por-hora-chart';
import { Button } from '@/components/ui/button';
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
import { dashboard, estadisticas } from '@/routes';
import type { EstadisticasBibliotecaProps, EstadisticasFiltros } from '@/types';

const FILTRO_TODOS = 'todos';

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

function TarjetaEstadistica({
    etiqueta,
    valor,
    simbolo,
    color,
}: {
    etiqueta: string;
    valor: number;
    simbolo: ReactNode;
    color: string;
}) {
    return (
        <div
            className={cn(
                'rounded-lg border border-l-4 border-border bg-card p-4',
                color,
            )}
        >
            <div className="flex items-center justify-between gap-2">
                <span className="text-sm text-muted-foreground">
                    {etiqueta}
                </span>
                <span className="text-lg leading-none">{simbolo}</span>
            </div>
            <div className="mt-1 text-2xl font-bold tabular-nums">
                {valor.toLocaleString('es-MX')}
            </div>
        </div>
    );
}

function BarraHorizontal({
    etiqueta,
    total,
    maximo,
}: {
    etiqueta: string;
    total: number;
    maximo: number;
}) {
    return (
        <div className="flex items-center gap-3 text-sm">
            <span
                className="w-56 shrink-0 truncate text-muted-foreground"
                title={etiqueta}
            >
                {etiqueta}
            </span>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-chart-1"
                    style={{
                        width: `${maximo === 0 ? 0 : Math.max((total / maximo) * 100, total > 0 ? 3 : 0)}%`,
                    }}
                />
            </div>
            <span className="w-10 shrink-0 text-right font-medium tabular-nums">
                {total}
            </span>
        </div>
    );
}

export default function Estadisticas({
    filtros,
    opciones,
    datos,
}: EstadisticasBibliotecaProps) {
    function navegar(cambios: Partial<EstadisticasFiltros>) {
        const siguiente: EstadisticasFiltros = { ...filtros, ...cambios };
        const query: Record<string, string> = {};

        if (siguiente.fechaInicial) {
            query.fecha_inicial = siguiente.fechaInicial;
        }

        if (siguiente.fechaFinal) {
            query.fecha_final = siguiente.fechaFinal;
        }

        if (siguiente.carrera) {
            query.carrera = siguiente.carrera;
        }

        if (siguiente.semestre) {
            query.semestre = siguiente.semestre;
        }

        router.get(estadisticas().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function limpiarFiltros() {
        router.get(
            estadisticas().url,
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const maximoCarrera = Math.max(
        1,
        ...datos.accesosPorCarrera.map((item) => item.total),
    );
    const maximoSemestre = Math.max(
        1,
        ...datos.accesosPorSemestre.map((item) => item.total),
    );

    return (
        <>
            <Head title="Estadísticas" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-3xl font-extrabold tracking-tight text-primary uppercase">
                        Estadísticas
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Estadística general de accesos a la biblioteca.
                    </p>
                </div>

                <Fieldset>
                    <FieldsetLegend>Búsqueda por</FieldsetLegend>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <CampoFiltro etiqueta="Fecha inicial">
                            <Input
                                type="date"
                                value={filtros.fechaInicial}
                                onChange={(evento) =>
                                    navegar({
                                        fechaInicial: evento.target.value,
                                    })
                                }
                            />
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Fecha final">
                            <Input
                                type="date"
                                value={filtros.fechaFinal}
                                onChange={(evento) =>
                                    navegar({
                                        fechaFinal: evento.target.value,
                                    })
                                }
                            />
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Carrera">
                            <Select
                                value={filtros.carrera || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    navegar({
                                        carrera:
                                            valor === FILTRO_TODOS ? '' : valor,
                                    })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todas las carreras
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
                        </CampoFiltro>
                        <CampoFiltro etiqueta="Semestre">
                            <Select
                                value={filtros.semestre || FILTRO_TODOS}
                                onValueChange={(valor) =>
                                    navegar({
                                        semestre:
                                            valor === FILTRO_TODOS ? '' : valor,
                                    })
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={FILTRO_TODOS}>
                                        Todos los semestres
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
                        </CampoFiltro>
                    </div>

                    <div className="mt-4 flex justify-end">
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
                    </div>
                </Fieldset>

                <Fieldset>
                    <FieldsetLegend>Resumen general</FieldsetLegend>
                    <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <TarjetaEstadistica
                            etiqueta="Hombres"
                            valor={datos.resumen.hombres}
                            simbolo={<span className="text-blue-500">♂</span>}
                            color="border-l-blue-500"
                        />
                        <TarjetaEstadistica
                            etiqueta="Mujeres"
                            valor={datos.resumen.mujeres}
                            simbolo={<span className="text-pink-500">♀</span>}
                            color="border-l-pink-500"
                        />
                        <TarjetaEstadistica
                            etiqueta="Total accesos"
                            valor={datos.resumen.totalAlumnos}
                            simbolo={<span className="text-orange-500">∑</span>}
                            color="border-l-orange-500"
                        />
                        <TarjetaEstadistica
                            etiqueta="Entradas"
                            valor={datos.resumen.entradas}
                            simbolo={
                                <LogIn className="size-[1.1rem] text-emerald-600 dark:text-emerald-400" />
                            }
                            color="border-l-emerald-500"
                        />
                        <TarjetaEstadistica
                            etiqueta="Salidas"
                            valor={datos.resumen.salidas}
                            simbolo={
                                <LogOut className="size-[1.1rem] text-red-600 dark:text-red-400" />
                            }
                            color="border-l-red-500"
                        />
                        <TarjetaEstadistica
                            etiqueta="Personas dentro"
                            valor={datos.resumen.personasDentro}
                            simbolo={
                                <UserCheck className="size-[1.1rem] text-violet-600 dark:text-violet-400" />
                            }
                            color="border-l-violet-500"
                        />
                    </div>
                </Fieldset>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Fieldset className="min-w-0 lg:col-span-2">
                        <FieldsetLegend>Entradas por hora</FieldsetLegend>
                        <EntradasPorHoraChart data={datos.entradasPorHora} />
                    </Fieldset>

                    <Fieldset>
                        <FieldsetLegend>Distribución por sexo</FieldsetLegend>
                        <DistribucionPorSexoChart
                            data={datos.distribucionPorSexo}
                        />
                    </Fieldset>
                </div>

                <Fieldset>
                    <FieldsetLegend>
                        Accesos por día ({formatearRango(filtros)})
                    </FieldsetLegend>
                    <AccesosPorDiaChart data={datos.accesosPorDia} />
                </Fieldset>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Fieldset>
                        <FieldsetLegend>Accesos por carrera</FieldsetLegend>
                        <div className="flex flex-col gap-3">
                            {datos.accesosPorCarrera.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Sin datos en el periodo seleccionado.
                                </p>
                            ) : (
                                datos.accesosPorCarrera.map((item) => (
                                    <BarraHorizontal
                                        key={item.carrera}
                                        etiqueta={item.carrera}
                                        total={item.total}
                                        maximo={maximoCarrera}
                                    />
                                ))
                            )}
                        </div>
                    </Fieldset>

                    <Fieldset>
                        <FieldsetLegend>Accesos por semestre</FieldsetLegend>
                        <div className="flex flex-col gap-3">
                            {datos.accesosPorSemestre.map((item) => (
                                <BarraHorizontal
                                    key={item.semestre}
                                    etiqueta={`${item.semestre} semestre`}
                                    total={item.total}
                                    maximo={maximoSemestre}
                                />
                            ))}
                        </div>
                    </Fieldset>
                </div>
            </div>
        </>
    );
}

function formatearRango(filtros: EstadisticasFiltros): string {
    const formatear = (iso: string) => {
        const [anio, mes, dia] = iso.split('-');

        return anio && mes && dia ? `${dia}/${mes}/${anio}` : iso;
    };

    return `${formatear(filtros.fechaInicial)} al ${formatear(filtros.fechaFinal)}`;
}

Estadisticas.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Estadísticas', href: estadisticas() },
    ],
};
