import { Head } from '@inertiajs/react';
import { LogIn, LogOut, UserCheck, Users } from 'lucide-react';
import { AccesosPorDiaChart } from '@/components/dashboard/accesos-por-dia-chart';
import { DistribucionPorSexoChart } from '@/components/dashboard/distribucion-por-sexo-chart';
import { EntradasPorHoraChart } from '@/components/dashboard/entradas-por-hora-chart';
import { Badge } from '@/components/ui/badge';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types';

export default function Dashboard({
    resumen,
    entradasPorHora,
    distribucionPorSexo,
    accesosRecientes,
    accesosPorDia,
}: DashboardProps) {
    const tarjetas = [
        {
            label: 'Entradas hoy',
            value: resumen.entradasHoy,
            icon: LogIn,
            color: 'text-emerald-600 dark:text-emerald-400',
        },
        {
            label: 'Salidas hoy',
            value: resumen.salidasHoy,
            icon: LogOut,
            color: 'text-red-600 dark:text-red-400',
        },
        {
            label: 'Usuarios únicos',
            value: resumen.usuariosUnicos,
            icon: Users,
            color: 'text-blue-600 dark:text-blue-400',
        },
        {
            label: 'Personas dentro',
            value: resumen.personasDentro,
            icon: UserCheck,
            color: 'text-violet-600 dark:text-violet-400',
        },
    ] as const;

    return (
        <>
            <Head title="Centro de Información" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h1 className="text-center text-3xl font-extrabold tracking-tight text-primary uppercase">
                    Centro de Información
                </h1>

                <Fieldset>
                    <FieldsetLegend>Resumen general del día</FieldsetLegend>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {tarjetas.map((tarjeta) => (
                            <div
                                key={tarjeta.label}
                                className="flex flex-col items-center gap-2 rounded-lg border border-border px-4 py-6 text-center"
                            >
                                <tarjeta.icon
                                    className={cn('size-9', tarjeta.color)}
                                />
                                <span className="text-sm text-muted-foreground">
                                    {tarjeta.label}
                                </span>
                                <span className="text-3xl font-bold tabular-nums">
                                    {tarjeta.value.toLocaleString('es-MX')}
                                </span>
                            </div>
                        ))}
                    </div>
                </Fieldset>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Fieldset className="min-w-0 lg:col-span-2">
                        <FieldsetLegend>Entradas por hora hoy</FieldsetLegend>
                        <EntradasPorHoraChart data={entradasPorHora} />
                    </Fieldset>

                    <Fieldset>
                        <FieldsetLegend>Distribución por sexo</FieldsetLegend>
                        <DistribucionPorSexoChart data={distribucionPorSexo} />
                    </Fieldset>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Fieldset className="lg:col-span-2">
                        <FieldsetLegend>Accesos recientes</FieldsetLegend>
                        <div className="overflow-x-auto">
                            {accesosRecientes.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Aún no hay accesos registrados.
                                </p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                            <th className="py-2 pr-4 font-medium">
                                                Hora
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Nombre
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Carrera
                                            </th>
                                            <th className="py-2 font-medium">
                                                Movimiento
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {accesosRecientes.map(
                                            (acceso, index) => (
                                                <tr
                                                    key={index}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-2 pr-4 whitespace-nowrap tabular-nums">
                                                        {acceso.hora}
                                                    </td>
                                                    <td className="py-2 pr-4">
                                                        {acceso.nombre}
                                                    </td>
                                                    <td className="py-2 pr-4">
                                                        {acceso.carrera ?? '—'}
                                                    </td>
                                                    <td className="py-2">
                                                        <Badge
                                                            className={cn(
                                                                'rounded-full border-transparent font-semibold',
                                                                acceso.tipoMovimiento ===
                                                                    'ENTRADA'
                                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                                    : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                                                            )}
                                                        >
                                                            {acceso.tipoMovimiento ===
                                                            'ENTRADA'
                                                                ? 'Entrada'
                                                                : 'Salida'}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </Fieldset>

                    <Fieldset>
                        <FieldsetLegend>
                            Accesos por día (últimos 7 días)
                        </FieldsetLegend>
                        <AccesosPorDiaChart data={accesosPorDia} />
                    </Fieldset>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Centro de Información',
            href: dashboard(),
        },
    ],
};
