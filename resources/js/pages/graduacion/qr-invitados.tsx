import { Head, router } from '@inertiajs/react';
import { Download, RefreshCw, Search, Sheet as SheetIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { panel } from '@/routes/graduacion';
import boletosRoutes from '@/routes/graduacion/boletos';
import type { BoletosIndexProps } from '@/types';

export default function QrInvitados({
    ceremonias,
    carreras,
    ceremoniaSeleccionada,
    resultadoGrupal,
    resultadoIndividual,
}: BoletosIndexProps) {
    const [modo, setModo] = useState<'grupal' | 'individual'>('grupal');
    const [ceremonia, setCeremonia] = useState(ceremoniaSeleccionada ?? '');
    const [carrera, setCarrera] = useState('');
    const [query, setQuery] = useState('');

    function buscarGrupal() {
        if (!ceremonia || !carrera) {
            toast.error('Selecciona una ceremonia y una carrera.');

            return;
        }

        const [cve, reticula] = carrera.split('-');

        router.get(
            boletosRoutes.index().url,
            {
                ceremonia,
                modo: 'grupal',
                carrera: cve,
                reticula,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function buscarIndividual() {
        if (!ceremonia || !query) {
            toast.error(
                'Selecciona una ceremonia y escribe un nombre o número de control.',
            );

            return;
        }

        router.get(
            boletosRoutes.index().url,
            { ceremonia, modo: 'individual', query },
            { preserveState: true, preserveScroll: true },
        );
    }

    function regenerar(boletoId: string) {
        router.post(
            boletosRoutes.regenerar(boletoId).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="QR para Invitados" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        QR para Invitados
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Descarga los códigos QR que servirán de boleto de
                        entrada a los invitados de cada egresado.
                    </p>
                </div>

                <Fieldset className="flex flex-col gap-4">
                    <FieldsetLegend>Búsqueda</FieldsetLegend>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-xs font-medium text-muted-foreground">
                            Ceremonia
                        </Label>
                        <Select value={ceremonia} onValueChange={setCeremonia}>
                            <SelectTrigger className="w-full sm:w-72">
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
                    </div>

                    <Tabs
                        value={modo}
                        onValueChange={(valor) =>
                            setModo(valor as 'grupal' | 'individual')
                        }
                    >
                        <TabsList>
                            <TabsTrigger value="grupal">Grupal</TabsTrigger>
                            <TabsTrigger value="individual">
                                Individual
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    {modo === 'grupal' ? (
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Carrera
                                </Label>
                                <Select
                                    value={carrera}
                                    onValueChange={setCarrera}
                                >
                                    <SelectTrigger className="w-56">
                                        <SelectValue placeholder="Selecciona una carrera" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {carreras.map((c) => (
                                            <SelectItem
                                                key={c.value}
                                                value={c.value}
                                            >
                                                {c.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="button" onClick={buscarGrupal}>
                                <Search />
                                Buscar
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-xs font-medium text-muted-foreground">
                                    Nombre o número de control
                                </Label>
                                <Input
                                    placeholder="Buscar por nombre o número de control…"
                                    value={query}
                                    onChange={(evt) =>
                                        setQuery(evt.target.value)
                                    }
                                    className="w-72"
                                />
                            </div>
                            <Button type="button" onClick={buscarIndividual}>
                                <Search />
                                Buscar
                            </Button>
                        </div>
                    )}
                </Fieldset>

                {resultadoGrupal && (
                    <Fieldset className="flex flex-col gap-4">
                        <FieldsetLegend>
                            {resultadoGrupal.carrera}
                        </FieldsetLegend>

                        <div className="overflow-x-auto">
                            {resultadoGrupal.alumnos.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No hay alumnos registrados en esta carrera.
                                </p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                            <th className="px-3 py-2 font-medium">
                                                No. Control
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                Nombre
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                Boleto de invitados
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {resultadoGrupal.alumnos.map(
                                            (alumno) => (
                                                <tr
                                                    key={alumno.noDeControl}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-3 py-2 tabular-nums">
                                                        {alumno.noDeControl}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        {alumno.nombre}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Badge
                                                            variant={
                                                                alumno.tieneBoleto
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {alumno.tieneBoleto
                                                                ? `Generado (${alumno.invitadosAutorizados})`
                                                                : 'Sin generar'}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            )}
                        </div>

                        {ceremonia && carrera && (
                            <Button type="button" variant="outline" asChild>
                                <a
                                    href={`${boletosRoutes.qrGrupal(ceremonia).url}?carrera=${carrera.split('-')[0]}&reticula=${carrera.split('-')[1]}`}
                                >
                                    <SheetIcon />
                                    Descargar QR de toda la carrera (PDF)
                                </a>
                            </Button>
                        )}
                    </Fieldset>
                )}

                {resultadoIndividual && (
                    <Fieldset className="flex flex-col gap-4">
                        <FieldsetLegend>
                            {resultadoIndividual.alumno.nombre}
                        </FieldsetLegend>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                <div>
                                    <dt className="text-muted-foreground">
                                        ID Único
                                    </dt>
                                    <dd className="font-medium">
                                        {
                                            resultadoIndividual.identificador
                                                .idUnico
                                        }
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        No. Control
                                    </dt>
                                    <dd className="font-medium">
                                        {resultadoIndividual.alumno.noDeControl}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        CURP
                                    </dt>
                                    <dd className="font-medium">
                                        {resultadoIndividual.identificador
                                            .curp ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Carrera
                                    </dt>
                                    <dd className="font-medium">
                                        {resultadoIndividual.alumno.carrera ??
                                            '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Fecha de registro
                                    </dt>
                                    <dd className="font-medium">
                                        {
                                            resultadoIndividual.identificador
                                                .fechaRegistro
                                        }
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Estatus
                                    </dt>
                                    <dd>
                                        <Badge
                                            variant={
                                                resultadoIndividual
                                                    .identificador.estatus
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {resultadoIndividual.identificador
                                                .estatus
                                                ? 'Activo'
                                                : 'Inactivo'}
                                        </Badge>
                                    </dd>
                                </div>
                                <div className="col-span-2">
                                    <dt className="text-muted-foreground">
                                        Invitados
                                    </dt>
                                    <dd className="font-medium">
                                        {
                                            resultadoIndividual.boletoInvitado
                                                .invitadosRegistrados
                                        }{' '}
                                        de{' '}
                                        {resultadoIndividual.boletoInvitado
                                            .invitadosAutorizados ?? 0}{' '}
                                        registrados
                                    </dd>
                                </div>
                            </dl>

                            <div className="flex flex-col items-center gap-3">
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Código QR de la credencial
                                </p>
                                <img
                                    src={
                                        boletosRoutes.qr(
                                            resultadoIndividual.boletoInvitado
                                                .id,
                                        ).url
                                    }
                                    alt="Código QR"
                                    className="size-40 rounded-lg border border-border p-2"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Generado:{' '}
                                    {
                                        resultadoIndividual.boletoInvitado
                                            .generadoEn
                                    }
                                </p>
                                <div className="flex flex-wrap justify-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        asChild
                                    >
                                        <a
                                            href={
                                                boletosRoutes.qr(
                                                    resultadoIndividual
                                                        .boletoInvitado.id,
                                                ).url
                                            }
                                            download
                                        >
                                            <Download />
                                            Descargar
                                        </a>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            window.open(
                                                boletosRoutes.qr(
                                                    resultadoIndividual
                                                        .boletoInvitado.id,
                                                ).url,
                                                '_blank',
                                            )
                                        }
                                    >
                                        Imprimir
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            regenerar(
                                                resultadoIndividual
                                                    .boletoInvitado.id,
                                            )
                                        }
                                    >
                                        <RefreshCw />
                                        Regenerar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Fieldset>
                )}
            </div>
        </>
    );
}

QrInvitados.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'QR para Invitados', href: boletosRoutes.index() },
    ],
};
