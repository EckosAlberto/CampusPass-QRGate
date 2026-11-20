import { Form, Head, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { panel } from '@/routes/eventos';
import eventosRoutes from '@/routes/eventos/eventos';
import type { EventoFila, GestionEventosProps, OpcionFiltro } from '@/types';

type ValoresFormulario = {
    nombre: string;
    fecha: string;
    fecha_fin: string;
    hora_inicio: string;
    hora_fin: string;
    horas: string;
    fk_id_tipo_evento: string;
    fk_id_periodo_tutorias: string;
    fk_id_tutor: string;
};

const VALORES_VACIOS: ValoresFormulario = {
    nombre: '',
    fecha: '',
    fecha_fin: '',
    hora_inicio: '',
    hora_fin: '',
    horas: '',
    fk_id_tipo_evento: '',
    fk_id_periodo_tutorias: '',
    fk_id_tutor: '',
};

function valoresDesdeEvento(evento: EventoFila): ValoresFormulario {
    return {
        nombre: evento.nombre ?? '',
        fecha: evento.fecha ?? '',
        fecha_fin: evento.fechaFin ?? '',
        hora_inicio: evento.horaInicio ?? '',
        hora_fin: evento.horaFin ?? '',
        horas: evento.horas ? String(evento.horas) : '',
        fk_id_tipo_evento: evento.tipoEvento?.value ?? '',
        fk_id_periodo_tutorias: evento.periodoTutorias?.value ?? '',
        fk_id_tutor: evento.tutor?.value ?? '',
    };
}

function SelectorCatalogo({
    etiqueta,
    valor,
    nombre,
    opciones,
    onChange,
}: {
    etiqueta: string;
    valor: string;
    nombre: string;
    opciones: OpcionFiltro[];
    onChange: (valor: string) => void;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label>{etiqueta}</Label>
            <input type="hidden" name={nombre} value={valor} />
            <Select value={valor} onValueChange={onChange}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="Selecciona una opción" />
                </SelectTrigger>
                <SelectContent>
                    {opciones.map((opcion) => (
                        <SelectItem key={opcion.value} value={opcion.value}>
                            {opcion.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function EventoFormDialog({
    evento,
    catalogos,
    open,
    onOpenChange,
}: {
    evento: EventoFila | null;
    catalogos: GestionEventosProps['catalogos'];
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [valores, setValores] = useState<ValoresFormulario>(
        evento ? valoresDesdeEvento(evento) : VALORES_VACIOS,
    );

    function abrir(siguiente: boolean) {
        if (siguiente) {
            setValores(evento ? valoresDesdeEvento(evento) : VALORES_VACIOS);
        }

        onOpenChange(siguiente);
    }

    function actualizar<K extends keyof ValoresFormulario>(
        campo: K,
        valor: string,
    ) {
        setValores((actual) => ({ ...actual, [campo]: valor }));
    }

    const formulario = evento
        ? eventosRoutes.update.form(evento.id)
        : eventosRoutes.store.form();

    return (
        <Dialog open={open} onOpenChange={abrir}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {evento ? 'Modificar evento' : 'Crear evento'}
                    </DialogTitle>
                </DialogHeader>

                <Form
                    {...formulario}
                    onSuccess={() => onOpenChange(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="nombre">
                                    Nombre del evento
                                </Label>
                                <Input
                                    id="nombre"
                                    name="nombre"
                                    value={valores.nombre}
                                    onChange={(evt) =>
                                        actualizar('nombre', evt.target.value)
                                    }
                                    required
                                />
                                {errors.nombre && (
                                    <p className="text-sm text-destructive">
                                        {errors.nombre}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="fecha">Fecha inicio</Label>
                                    <Input
                                        id="fecha"
                                        name="fecha"
                                        type="date"
                                        value={valores.fecha}
                                        onChange={(evt) =>
                                            actualizar(
                                                'fecha',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.fecha && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="fecha_fin">Fecha fin</Label>
                                    <Input
                                        id="fecha_fin"
                                        name="fecha_fin"
                                        type="date"
                                        value={valores.fecha_fin}
                                        onChange={(evt) =>
                                            actualizar(
                                                'fecha_fin',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.fecha_fin && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha_fin}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="hora_inicio">
                                        Hora inicio
                                    </Label>
                                    <Input
                                        id="hora_inicio"
                                        name="hora_inicio"
                                        type="time"
                                        value={valores.hora_inicio}
                                        onChange={(evt) =>
                                            actualizar(
                                                'hora_inicio',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.hora_inicio && (
                                        <p className="text-sm text-destructive">
                                            {errors.hora_inicio}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="hora_fin">Hora fin</Label>
                                    <Input
                                        id="hora_fin"
                                        name="hora_fin"
                                        type="time"
                                        value={valores.hora_fin}
                                        onChange={(evt) =>
                                            actualizar(
                                                'hora_fin',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.hora_fin && (
                                        <p className="text-sm text-destructive">
                                            {errors.hora_fin}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="horas">
                                        Horas de duración
                                    </Label>
                                    <Input
                                        id="horas"
                                        name="horas"
                                        type="number"
                                        min={1}
                                        max={24}
                                        value={valores.horas}
                                        onChange={(evt) =>
                                            actualizar(
                                                'horas',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.horas && (
                                        <p className="text-sm text-destructive">
                                            {errors.horas}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <SelectorCatalogo
                                etiqueta="Tipo de evento"
                                nombre="fk_id_tipo_evento"
                                valor={valores.fk_id_tipo_evento}
                                opciones={catalogos.tiposEvento}
                                onChange={(valor) =>
                                    actualizar('fk_id_tipo_evento', valor)
                                }
                            />
                            {errors.fk_id_tipo_evento && (
                                <p className="text-sm text-destructive">
                                    {errors.fk_id_tipo_evento}
                                </p>
                            )}

                            <SelectorCatalogo
                                etiqueta="Periodo de tutorías"
                                nombre="fk_id_periodo_tutorias"
                                valor={valores.fk_id_periodo_tutorias}
                                opciones={catalogos.periodosTutorias}
                                onChange={(valor) =>
                                    actualizar('fk_id_periodo_tutorias', valor)
                                }
                            />
                            {errors.fk_id_periodo_tutorias && (
                                <p className="text-sm text-destructive">
                                    {errors.fk_id_periodo_tutorias}
                                </p>
                            )}

                            <SelectorCatalogo
                                etiqueta="Tutor"
                                nombre="fk_id_tutor"
                                valor={valores.fk_id_tutor}
                                opciones={catalogos.tutores}
                                onChange={(valor) =>
                                    actualizar('fk_id_tutor', valor)
                                }
                            />
                            {errors.fk_id_tutor && (
                                <p className="text-sm text-destructive">
                                    {errors.fk_id_tutor}
                                </p>
                            )}

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function DetalleEventoDialog({
    evento,
    onOpenChange,
}: {
    evento: EventoFila | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={evento !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {evento && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{evento.nombre}</DialogTitle>
                        </DialogHeader>

                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha inicio
                                </dt>
                                <dd className="font-medium">{evento.fecha}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha fin
                                </dt>
                                <dd className="font-medium">
                                    {evento.fechaFin}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Hora inicio
                                </dt>
                                <dd className="font-medium">
                                    {evento.horaInicio}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Hora fin
                                </dt>
                                <dd className="font-medium">
                                    {evento.horaFin}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Horas de duración
                                </dt>
                                <dd className="font-medium">{evento.horas}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Tipo de evento
                                </dt>
                                <dd className="font-medium">
                                    {evento.tipoEvento?.label ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Periodo tutorías
                                </dt>
                                <dd className="font-medium">
                                    {evento.periodoTutorias?.label ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Tutor</dt>
                                <dd className="font-medium">
                                    {evento.tutor?.label ?? '—'}
                                </dd>
                            </div>
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function GestionEventos({
    usuarioId,
    eventos: listado,
    catalogos,
}: GestionEventosProps) {
    const [dialogoCreacionAbierto, setDialogoCreacionAbierto] = useState(false);
    const [eventoAEditar, setEventoAEditar] = useState<EventoFila | null>(null);
    const [eventoAVer, setEventoAVer] = useState<EventoFila | null>(null);

    function cambiarEstatus(evento: EventoFila, estatus: boolean) {
        router.patch(
            eventosRoutes.estatus(evento.id).url,
            { estatus },
            { preserveScroll: true },
        );
    }

    function eliminar(evento: EventoFila) {
        if (
            !window.confirm(
                `¿Eliminar el evento "${evento.nombre}"? Esta acción no se puede deshacer.`,
            )
        ) {
            return;
        }

        router.delete(eventosRoutes.destroy(evento.id).url, {
            preserveScroll: true,
            onError: (errores) =>
                toast.error(errores.evento ?? 'No se pudo eliminar el evento.'),
        });
    }

    return (
        <>
            <Head title="Gestión de eventos" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                            Gestión de eventos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Eventos Académicos y Tutorías
                        </p>
                    </div>

                    <Button
                        variant="accent"
                        onClick={() => setDialogoCreacionAbierto(true)}
                    >
                        <Plus />
                        Nuevo evento
                    </Button>
                </div>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Consultar eventos</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {listado.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Todavía no se han creado eventos.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Fecha
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Horario
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tipo evento
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tutor
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Activo
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {listado.data.map((evento) => (
                                        <tr
                                            key={evento.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2">
                                                {evento.nombre}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {evento.fecha}
                                                {evento.fechaFin !==
                                                    evento.fecha &&
                                                    ` – ${evento.fechaFin}`}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {evento.horaInicio} -{' '}
                                                {evento.horaFin}
                                            </td>
                                            <td className="px-3 py-2">
                                                {evento.tipoEvento?.label ??
                                                    '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {evento.tutor?.label ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        checked={evento.estatus}
                                                        onCheckedChange={(
                                                            valor,
                                                        ) =>
                                                            cambiarEstatus(
                                                                evento,
                                                                valor,
                                                            )
                                                        }
                                                    />
                                                    <Badge
                                                        variant={
                                                            evento.estatus
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {evento.estatus
                                                            ? 'Activo'
                                                            : 'Inactivo'}
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-1.5">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Ver"
                                                        onClick={() =>
                                                            setEventoAVer(
                                                                evento,
                                                            )
                                                        }
                                                    >
                                                        <Eye />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Modificar"
                                                        onClick={() =>
                                                            setEventoAEditar(
                                                                evento,
                                                            )
                                                        }
                                                    >
                                                        <Pencil />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title={
                                                            evento.creadoPor !==
                                                            usuarioId
                                                                ? 'Solo quien creó el evento puede eliminarlo'
                                                                : 'Eliminar'
                                                        }
                                                        disabled={
                                                            evento.creadoPor !==
                                                            usuarioId
                                                        }
                                                        onClick={() =>
                                                            eliminar(evento)
                                                        }
                                                        className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {listado.data.length > 0 && (
                        <p className="text-sm text-muted-foreground">
                            Mostrando {listado.meta.desde} a{' '}
                            {listado.meta.hasta} de {listado.meta.total} eventos
                        </p>
                    )}
                </Fieldset>
            </div>

            <EventoFormDialog
                evento={null}
                catalogos={catalogos}
                open={dialogoCreacionAbierto}
                onOpenChange={setDialogoCreacionAbierto}
            />

            <EventoFormDialog
                key={eventoAEditar?.id ?? 'sin-edicion'}
                evento={eventoAEditar}
                catalogos={catalogos}
                open={eventoAEditar !== null}
                onOpenChange={(abierto) => !abierto && setEventoAEditar(null)}
            />

            <DetalleEventoDialog
                evento={eventoAVer}
                onOpenChange={(abierto) => !abierto && setEventoAVer(null)}
            />
        </>
    );
}

GestionEventos.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'Gestión de eventos', href: eventosRoutes.index() },
    ],
};
