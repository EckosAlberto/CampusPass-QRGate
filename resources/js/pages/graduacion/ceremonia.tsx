import { Head, router } from '@inertiajs/react';
import { Copy, ExternalLink, Mail, Square } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
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
import { Label } from '@/components/ui/label';
import { panel } from '@/routes/graduacion';
import ceremoniasRoutes from '@/routes/graduacion/ceremonias';
import type { CeremoniaShowProps } from '@/types';

function copiarUrl(url: string) {
    navigator.clipboard
        .writeText(url)
        .then(() => toast.success('URL copiada al portapapeles.'))
        .catch(() => toast.error('No se pudo copiar la URL.'));
}

function BloqueUrl({
    titulo,
    url,
    onCompartir,
}: {
    titulo: string;
    url: string | null;
    onCompartir: () => void;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                URL de {titulo}
            </span>

            {url ? (
                <>
                    <code className="block w-full truncate rounded-md bg-muted px-2 py-1 text-xs">
                        {url}
                    </code>
                    <div className="flex items-center gap-1.5">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Copiar URL"
                            onClick={() => copiarUrl(url)}
                        >
                            <Copy />
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Abrir URL"
                            asChild
                        >
                            <a href={url} target="_blank" rel="noreferrer">
                                <ExternalLink />
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            title="Compartir por correo"
                            onClick={onCompartir}
                        >
                            <Mail />
                        </Button>
                    </div>
                </>
            ) : (
                <p className="text-sm text-muted-foreground">
                    No disponible en este momento.
                </p>
            )}
        </div>
    );
}

function DialogoCompartir({
    tipo,
    onOpenChange,
    onEnviar,
    enviando,
}: {
    tipo: 'registro' | 'enlace' | null;
    onOpenChange: (abierto: boolean) => void;
    onEnviar: (correo: string) => void;
    enviando: boolean;
}) {
    const [correo, setCorreo] = useState('');

    return (
        <Dialog
            open={tipo !== null}
            onOpenChange={(abierto) => {
                if (!abierto) {
                    setCorreo('');
                }

                onOpenChange(abierto);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Compartir URL de{' '}
                        {tipo === 'registro'
                            ? 'Registrar Acceso'
                            : 'Enlace remoto'}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="correo-compartir-ceremonia">Correo</Label>
                    <div className="relative">
                        <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="correo-compartir-ceremonia"
                            type="email"
                            className="pl-9"
                            value={correo}
                            onChange={(evento) =>
                                setCorreo(evento.target.value)
                            }
                        />
                    </div>
                </div>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        disabled={enviando || !correo}
                        onClick={() => onEnviar(correo)}
                    >
                        Enviar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Ceremonia({
    ceremonia,
    urlRegistro,
    urlEnlace,
}: CeremoniaShowProps) {
    const [compartirTipo, setCompartirTipo] = useState<
        'registro' | 'enlace' | null
    >(null);
    const [enviando, setEnviando] = useState(false);

    function enviarCorreo(correo: string) {
        if (!compartirTipo) {
            return;
        }

        setEnviando(true);

        router.post(
            ceremoniasRoutes.compartir(ceremonia.id).url,
            { tipo: compartirTipo, correo },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`URL enviada a ${correo}.`);
                    setCompartirTipo(null);
                },
                onError: (errores) =>
                    toast.error(
                        errores.tipo ??
                            errores.correo ??
                            'No se pudo enviar la URL.',
                    ),
                onFinish: () => setEnviando(false),
            },
        );
    }

    function finalizar() {
        if (!window.confirm(`¿Finalizar la ceremonia "${ceremonia.nombre}"?`)) {
            return;
        }

        router.post(
            ceremoniasRoutes.finalizar(ceremonia.id).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={ceremonia.nombre} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                            {ceremonia.nombre}
                        </h1>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground">
                            {ceremonia.codigo}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {ceremonia.fechaInicioLabel} –{' '}
                            {ceremonia.fechaFinLabel}
                        </p>
                    </div>
                    <Badge
                        variant={ceremonia.vigente ? 'default' : 'secondary'}
                    >
                        {ceremonia.vigente ? 'Vigente' : 'No vigente'}
                    </Badge>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="py-4">
                        <CardContent className="flex flex-col gap-0.5">
                            <span className="text-2xl font-semibold tabular-nums">
                                {ceremonia.egresadosRegistrados}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Egresados registrados
                            </span>
                        </CardContent>
                    </Card>
                    <Card className="py-4">
                        <CardContent className="flex flex-col gap-0.5">
                            <span className="text-2xl font-semibold tabular-nums">
                                {ceremonia.invitadosRegistrados}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Invitados registrados
                            </span>
                        </CardContent>
                    </Card>
                    <Card className="py-4">
                        <CardContent className="flex flex-col gap-0.5">
                            <span className="text-2xl font-semibold tabular-nums">
                                {ceremonia.invitadosPorDefecto}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Invitados por egresado
                            </span>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Fieldset className="flex min-w-0 flex-col gap-4">
                        <FieldsetLegend>
                            Registrar Acceso a la ceremonia
                        </FieldsetLegend>
                        <p className="text-sm text-muted-foreground">
                            Genera la URL pública para escanear el QR de las
                            credenciales de egresados e invitados. Se habilita{' '}
                            {ceremonia.minutosAnticipadosGraduados} minutos
                            antes de la entrada y caduca{' '}
                            {ceremonia.duracionHorasAcceso} horas después.
                        </p>
                        <BloqueUrl
                            titulo="Registro"
                            url={urlRegistro}
                            onCompartir={() => setCompartirTipo('registro')}
                        />
                    </Fieldset>

                    <Fieldset className="flex min-w-0 flex-col gap-4">
                        <FieldsetLegend>Enlace remoto</FieldsetLegend>
                        <p className="text-sm text-muted-foreground">
                            Enlace de consulta en tiempo real para
                            administradores o tutores. Caduca al finalizar la
                            ceremonia.
                        </p>
                        <BloqueUrl
                            titulo="Enlace remoto"
                            url={urlEnlace}
                            onCompartir={() => setCompartirTipo('enlace')}
                        />
                    </Fieldset>
                </div>

                <div>
                    <Button
                        type="button"
                        variant="outline"
                        className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                        onClick={finalizar}
                    >
                        <Square />
                        Finalizar ceremonia
                    </Button>
                </div>
            </div>

            <DialogoCompartir
                tipo={compartirTipo}
                onOpenChange={(abierto) => !abierto && setCompartirTipo(null)}
                onEnviar={enviarCorreo}
                enviando={enviando}
            />
        </>
    );
}

Ceremonia.layout = {
    breadcrumbs: [{ title: 'Inicio', href: panel() }],
};
