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
import { panel } from '@/routes/eventos';
import eventosRoutes from '@/routes/eventos/eventos';
import misEventosRoutes from '@/routes/eventos/mis-eventos';
import type { EventoMisEventos, MisEventosProps } from '@/types';

// Copia la URL al portapapeles y muestra un toast de éxito o error.
function copiarUrl(url: string) {
    navigator.clipboard
        .writeText(url)
        .then(() => toast.success('URL copiada al portapapeles.'))
        .catch(() => toast.error('No se pudo copiar la URL.'));
}

function BloqueUrl({
    titulo,
    url,
    puedeGenerar,
    onGenerar,
    onCompartir,
}: {
    titulo: string;
    url: string | null;
    puedeGenerar: boolean;
    onGenerar: () => void;
    onCompartir: () => void;
}) {
    if (!url) {
        return (
            <Button
                type="button"
                variant="outline"
                disabled={!puedeGenerar}
                onClick={onGenerar}
            >
                Generar URL de {titulo}
            </Button>
        );
    }

    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                URL de {titulo}
            </span>
            <div className="flex flex-wrap items-center gap-1.5">
                <code className="max-w-full truncate rounded-md bg-muted px-2 py-1 text-xs">
                    {url}
                </code>
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
        </div>
    );
}

// Diálogo para compartir la URL por correo.
function DialogoCompartir({
    tipo,
    onOpenChange,
    onEnviar,
    enviando,
}: {
    tipo: 'entrada' | 'salida' | null;
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
                        {tipo === 'entrada' ? 'Entrada' : 'Salida'}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="correo-compartir-evento">Correo</Label>
                    <div className="relative">
                        <Mail className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="correo-compartir-evento"
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

// Componente que representa un evento en la lista de mis eventos.
function EventoCard({ evento }: { evento: EventoMisEventos }) {
    const [compartirTipo, setCompartirTipo] = useState<
        'entrada' | 'salida' | null
    >(null);
    const [enviando, setEnviando] = useState(false);

    function generar(tipo: 'entrada' | 'salida') {
        router.post(
            misEventosRoutes.generarUrl(evento.id).url,
            { tipo },
            {
                preserveScroll: true,
                onError: (errores) =>
                    toast.error(errores.tipo ?? 'No se pudo generar la URL.'),
            },
        );
    }

    function enviarCorreo(correo: string) {
        if (!compartirTipo) {
            return;
        }

        setEnviando(true);

        router.post(
            misEventosRoutes.compartir(evento.id).url,
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
        if (!window.confirm(`¿Finalizar el evento "${evento.nombre}"?`)) {
            return;
        }

        router.patch(
            eventosRoutes.estatus(evento.id).url,
            { estatus: false },
            {
                preserveScroll: true,
                onSuccess: () => router.visit(panel().url),
            },
        );
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div>
                    <CardTitle>{evento.nombre}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {evento.fecha}
                        {evento.fechaFin !== evento.fecha &&
                            ` – ${evento.fechaFin}`}{' '}
                        · {evento.horaInicio} - {evento.horaFin}
                    </p>
                </div>
                <Badge variant={evento.vigente ? 'default' : 'secondary'}>
                    {evento.vigente ? 'Vigente' : 'No vigente'}
                </Badge>
            </CardHeader>

            <CardContent className="flex flex-col gap-4">
                {evento.vigente ? (
                    <>
                        <BloqueUrl
                            titulo="Entrada"
                            url={evento.urlEntrada}
                            puedeGenerar={evento.entradaHabilitada}
                            onGenerar={() => generar('entrada')}
                            onCompartir={() => setCompartirTipo('entrada')}
                        />
                        {!evento.urlEntrada && !evento.entradaHabilitada && (
                            <p className="text-xs text-muted-foreground">
                                La URL de entrada se habilita 5 minutos antes de
                                que inicie el evento.
                            </p>
                        )}

                        <BloqueUrl
                            titulo="Salida"
                            url={evento.urlSalida}
                            puedeGenerar={evento.salidaHabilitada}
                            onGenerar={() => generar('salida')}
                            onCompartir={() => setCompartirTipo('salida')}
                        />
                        {!evento.urlSalida && !evento.salidaHabilitada && (
                            <p className="text-xs text-muted-foreground">
                                La URL de salida se habilita 10 minutos antes de
                                que termine el evento.
                            </p>
                        )}

                        <p className="text-xs text-muted-foreground">
                            Cada URL generada dura 30 minutos; después caduca y
                            deberás generar una nueva.
                        </p>

                        <Button
                            type="button"
                            variant="outline"
                            className="w-fit text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                            onClick={finalizar}
                        >
                            <Square />
                            Finalizar evento
                        </Button>
                    </>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Este evento no está vigente. Actívalo o revisa sus
                        fechas desde Gestión de eventos para generar sus URLs.
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

// Componente principal de la página Mis eventos.
export default function MisEventos({ eventos }: MisEventosProps) {
    return (
        <>
            <Head title="Mis eventos" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Mis eventos
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Genera y comparte las URLs de entrada y salida de los
                        eventos que creaste.
                    </p>
                </div>

                {eventos.data.length === 0 ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        Todavía no has creado ningún evento.
                    </p>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {eventos.data.map((evento) => (
                            <EventoCard key={evento.id} evento={evento} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
