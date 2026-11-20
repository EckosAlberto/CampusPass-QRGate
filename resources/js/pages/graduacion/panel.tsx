import { Head, Link } from '@inertiajs/react';
import { GraduationCap, Users, UserPlus, Eye } from 'lucide-react';
import { AsistenciaPorCarreraChart } from '@/components/graduacion/asistencia-por-carrera-chart';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import { panel } from '@/routes/graduacion';
import ceremonias from '@/routes/graduacion/ceremonias';
import type { InicioGraduacionProps } from '@/types';

export default function GraduacionPanel({
    resumen,
    asistenciaPorCarrera,
    ultimosAccesos,
}: InicioGraduacionProps) {
    return (
        <>
            <Head title="Inicio" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Resumen general
                    </h1>
                    <p className="text-sm text-muted-foreground">Graduación</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="border-l-4 border-l-blue-500 py-4 lg:col-span-1 dark:border-l-blue-400">
                        <CardContent className="flex flex-col gap-1">
                            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Ceremonia activa
                            </span>
                            {resumen.ceremoniaActiva ? (
                                <>
                                    <span className="font-semibold">
                                        {resumen.ceremoniaActiva.nombre}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {resumen.ceremoniaActiva.fechaInicio}
                                    </span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="mt-1 w-fit"
                                        asChild
                                    >
                                        <Link
                                            href={ceremonias.show(
                                                resumen.ceremoniaActiva.id,
                                            )}
                                        >
                                            <Eye />
                                            Ver
                                        </Link>
                                    </Button>
                                </>
                            ) : (
                                <span className="text-sm text-muted-foreground">
                                    Sin ceremonia activa
                                </span>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-emerald-500 py-4 dark:border-l-emerald-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950">
                                <GraduationCap className="size-6 text-emerald-500 dark:text-emerald-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.egresadosRegistrados}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Egresados registrados
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-amber-500 py-4 dark:border-l-amber-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950">
                                <UserPlus className="size-6 text-amber-500 dark:text-amber-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.invitadosRegistrados}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Invitados registrados
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-orange-500 py-4 dark:border-l-orange-400">
                        <CardContent className="flex items-center gap-4">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-950">
                                <Users className="size-6 text-orange-500 dark:text-orange-400" />
                            </div>
                            <div className="flex flex-col gap-0.5">
                                <span className="text-2xl font-semibold tabular-nums">
                                    {resumen.totalAsistentes}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Total de asistentes
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Fieldset className="flex flex-1 flex-col gap-4 lg:col-span-2">
                        <FieldsetLegend>
                            Últimos accesos registrados
                        </FieldsetLegend>

                        <div className="overflow-x-auto">
                            {ultimosAccesos.data.length === 0 ? (
                                <p className="py-10 text-center text-sm text-muted-foreground">
                                    Todavía no hay accesos registrados.
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
                                                Carrera
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                No. Control
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                Estatus
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {ultimosAccesos.data.map((acceso) => (
                                            <tr
                                                key={acceso.id}
                                                className="border-b last:border-0 hover:bg-muted/30"
                                            >
                                                <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                    {acceso.fecha} ·{' '}
                                                    {acceso.hora}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {acceso.nombre}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {acceso.carrera ?? '—'}
                                                </td>
                                                <td className="px-3 py-2 tabular-nums">
                                                    {acceso.noDeControl}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge
                                                        variant={
                                                            acceso.estatus ===
                                                            'Egresado'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {acceso.estatus}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </Fieldset>

                    <Fieldset>
                        <FieldsetLegend>Asistencia</FieldsetLegend>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Asistencia por carrera
                        </p>
                        <AsistenciaPorCarreraChart
                            data={asistenciaPorCarrera}
                        />
                    </Fieldset>
                </div>
            </div>
        </>
    );
}

GraduacionPanel.layout = {
    breadcrumbs: [{ title: 'Inicio', href: panel() }],
};
