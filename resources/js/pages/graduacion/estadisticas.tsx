import { Head, router } from '@inertiajs/react';
import { ComparativaCeremoniasChart } from '@/components/graduacion/comparativa-ceremonias-chart';
import { TendenciaAccesosChart } from '@/components/graduacion/tendencia-accesos-chart';
import { Fieldset, FieldsetLegend } from '@/components/ui/fieldset';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { estadisticas as estadisticasRoute, panel } from '@/routes/graduacion';
import type { EstadisticasProps } from '@/types';

export default function Estadisticas({
    ceremonias,
    ceremoniaSeleccionada,
    comparativa,
    tendencia,
}: EstadisticasProps) {
    function cambiarCeremonia(ceremonia: string) {
        router.get(
            estadisticasRoute().url,
            { ceremonia },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Estadísticas" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Estadísticas
                    </h1>
                    <p className="text-sm text-muted-foreground">Graduación</p>
                </div>

                <Fieldset className="flex flex-col gap-4">
                    <FieldsetLegend>
                        Comparativa entre ceremonias
                    </FieldsetLegend>
                    <ComparativaCeremoniasChart data={comparativa} />
                </Fieldset>

                <Fieldset className="flex flex-col gap-4">
                    <FieldsetLegend>
                        Tendencia de accesos en el tiempo
                    </FieldsetLegend>

                    <Select
                        value={ceremoniaSeleccionada ?? undefined}
                        onValueChange={cambiarCeremonia}
                    >
                        <SelectTrigger className="w-72">
                            <SelectValue placeholder="Selecciona una ceremonia" />
                        </SelectTrigger>
                        <SelectContent>
                            {ceremonias.map((c) => (
                                <SelectItem key={c.value} value={c.value}>
                                    {c.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <TendenciaAccesosChart data={tendencia} />
                </Fieldset>
            </div>
        </>
    );
}

Estadisticas.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'Estadísticas', href: estadisticasRoute() },
    ],
};
