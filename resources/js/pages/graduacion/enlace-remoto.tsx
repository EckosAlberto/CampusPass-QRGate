import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { InstitutionHeader } from '@/components/institution-header';
import { Badge } from '@/components/ui/badge';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import type { EnlaceRemotoProps } from '@/types';

// Intervalo de refresco para simular consulta "en tiempo real" sin
// necesidad de WebSockets, consistente con el resto del proyecto.
const INTERVALO_ACTUALIZACION_MS = 10000;

export default function EnlaceRemoto({
    ceremonia,
    egresadosRegistrados,
    invitadosRegistrados,
    totalAccesos,
    accesos,
}: EnlaceRemotoProps) {
    useEffect(() => {
        const intervalo = setInterval(() => {
            router.reload({
                only: [
                    'egresadosRegistrados',
                    'invitadosRegistrados',
                    'totalAccesos',
                    'accesos',
                ],
            });
        }, INTERVALO_ACTUALIZACION_MS);

        return () => clearInterval(intervalo);
    }, []);

    return (
        <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
            <Head title={`${ceremonia.nombre} — Enlace remoto`} />

            <InstitutionHeader />

            <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-4 px-6 py-8">
                <div className="text-center">
                    <h1 className="text-2xl font-extrabold tracking-tight text-primary uppercase">
                        {ceremonia.nombre}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {ceremonia.fechaInicio} – {ceremonia.fechaFin}
                    </p>
                </div>

                <div className="grid grid-cols-3 gap-4">
                    <div className="flex flex-col items-center gap-1 rounded-lg border border-border px-2 py-4 text-center">
                        <span className="text-2xl font-bold tabular-nums">
                            {egresadosRegistrados}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            Egresados registrados
                        </span>
                    </div>
                    <div className="flex flex-col items-center gap-1 rounded-lg border border-border px-2 py-4 text-center">
                        <span className="text-2xl font-bold tabular-nums">
                            {invitadosRegistrados}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            Invitados registrados
                        </span>
                    </div>
                    <div className="flex flex-col items-center gap-1 rounded-lg border border-border px-2 py-4 text-center">
                        <span className="text-2xl font-bold tabular-nums">
                            {totalAccesos}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            Total de accesos
                        </span>
                    </div>
                </div>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Accesos</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {accesos.data.length === 0 ? (
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
                                    {accesos.data.map((acceso) => (
                                        <tr
                                            key={acceso.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {acceso.fecha} · {acceso.hora}
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
            </main>
        </div>
    );
}
