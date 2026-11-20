import { Head, router } from '@inertiajs/react';
import { Copy, ExternalLink, Mail, Square } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { panel } from '@/routes/graduacion';
import ceremoniasRoutes from '@/routes/graduacion/ceremonias';
import misCeremoniasRoutes from '@/routes/graduacion/mis-ceremonias';
import type { CeremoniaMisCeremonias, MisCeremoniasProps } from '@/types';

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
                    <Label htmlFor="correo-compartir-mis-ceremonias">
                        Correo
                    </Label>
                    <div className="relative">
                        <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="correo-compartir-mis-ceremonias"
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

function CeremoniaCard({ ceremonia }: { ceremonia: CeremoniaMisCeremonias }) {
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
            {
                preserveScroll: true,
                onSuccess: () => router.visit(misCeremoniasRoutes.index().url),
            },
        );
    }

    return (
        <Card className="min-w-0">
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle>{ceremonia.nombre}</CardTitle>
                    <p className="text-xs font-medium tracking-wide text-muted-foreground">
                        {ceremonia.codigo}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {ceremonia.fechaInicio} – {ceremonia.fechaFin}
                    </p>
                </div>
                <Badge variant={ceremonia.vigente ? 'default' : 'secondary'}>
                    {ceremonia.vigente ? 'Vigente' : 'No vigente'}
                </Badge>
            </CardHeader>

            <CardContent className="flex flex-col gap-4">
                {ceremonia.vigente ? (
                    <>
                        <BloqueUrl
                            titulo="Registro"
                            url={ceremonia.urlRegistro}
                            onCompartir={() => setCompartirTipo('registro')}
                        />

                        <BloqueUrl
                            titulo="Enlace remoto"
                            url={ceremonia.urlEnlace}
                            onCompartir={() => setCompartirTipo('enlace')}
                        />

                        <Button
                            type="button"
                            variant="outline"
                            className="w-fit text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                            onClick={finalizar}
                        >
                            <Square />
                            Finalizar ceremonia
                        </Button>
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Esta ceremonia no está vigente. Actívala o revisa sus
                        fechas desde Gestión de ceremonia.
                    </p>
                )}
            </CardContent>

            <DialogoCompartir
                tipo={compartirTipo}
                onOpenChange={(abierto) => !abierto && setCompartirTipo(null)}
                onEnviar={enviarCorreo}
                enviando={enviando}
            />
        </Card>
    );
}

export default function MisCeremonias({ ceremonias }: MisCeremoniasProps) {
    return (
        <>
            <Head title="Ceremonias" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Ceremonias
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Genera y comparte las URLs de Registrar Acceso y Enlace
                        remoto de las ceremonias que creaste.
                    </p>
                </div>

                {ceremonias.length === 0 ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        Todavía no has creado ninguna ceremonia.
                    </p>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {ceremonias.map((ceremonia) => (
                            <CeremoniaCard
                                key={ceremonia.id}
                                ceremonia={ceremonia}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

MisCeremonias.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'Ceremonias', href: misCeremoniasRoutes.index() },
    ],
};
