import { Form, Head, Link, router } from '@inertiajs/react';
import { Eye, LogIn, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Textarea } from '@/components/ui/textarea';
import { panel } from '@/routes/graduacion';
import ceremoniasRoutes from '@/routes/graduacion/ceremonias';
import type {
    CeremoniaFila,
    CeremoniaFormValues,
    GestionCeremoniasProps,
} from '@/types';

const VALORES_VACIOS: CeremoniaFormValues = {
    nombre: '',
    periodo: '',
    fecha_inicio: '',
    fecha_fin: '',
    tipo_acceso: 'qr',
    descripcion: '',
    minutos_anticipados_graduados: '10',
    minutos_anticipados_invitados: '10',
    duracion_horas_acceso: '5',
    invitados_por_defecto: '2',
    carreras: [],
};

function valoresDesdeCeremonia(ceremonia: CeremoniaFila): CeremoniaFormValues {
    return {
        nombre: ceremonia.nombre,
        periodo: ceremonia.periodo?.value ?? '',
        fecha_inicio: ceremonia.fechaInicio,
        fecha_fin: ceremonia.fechaFin,
        tipo_acceso: ceremonia.tipoAcceso,
        descripcion: ceremonia.descripcion ?? '',
        minutos_anticipados_graduados: String(
            ceremonia.minutosAnticipadosGraduados,
        ),
        minutos_anticipados_invitados: String(
            ceremonia.minutosAnticipadosInvitados,
        ),
        duracion_horas_acceso: String(ceremonia.duracionHorasAcceso),
        invitados_por_defecto: String(ceremonia.invitadosPorDefecto),
        carreras: ceremonia.carreras.map((c) => ({
            carrera: c.carrera,
            reticula: c.reticula,
        })),
    };
}

function CeremoniaFormDialog({
    ceremonia,
    catalogos,
    open,
    onOpenChange,
}: {
    ceremonia: CeremoniaFila | null;
    catalogos: GestionCeremoniasProps['catalogos'];
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [valores, setValores] = useState<CeremoniaFormValues>(
        ceremonia ? valoresDesdeCeremonia(ceremonia) : VALORES_VACIOS,
    );

    function abrir(siguiente: boolean) {
        if (siguiente) {
            setValores(
                ceremonia ? valoresDesdeCeremonia(ceremonia) : VALORES_VACIOS,
            );
        }

        onOpenChange(siguiente);
    }

    function actualizar<K extends keyof CeremoniaFormValues>(
        campo: K,
        valor: CeremoniaFormValues[K],
    ) {
        setValores((actual) => ({ ...actual, [campo]: valor }));
    }

    function alternarCarrera(opcion: string, marcado: boolean) {
        const [carrera, reticula] = opcion.split('-');

        setValores((actual) => ({
            ...actual,
            carreras: marcado
                ? [...actual.carreras, { carrera, reticula: Number(reticula) }]
                : actual.carreras.filter(
                      (c) =>
                          !(
                              c.carrera === carrera &&
                              c.reticula === Number(reticula)
                          ),
                  ),
        }));
    }

    const formulario = ceremonia
        ? ceremoniasRoutes.update.form(ceremonia.id)
        : ceremoniasRoutes.store.form();

    return (
        <Dialog open={open} onOpenChange={abrir}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {ceremonia ? 'Modificar ceremonia' : 'Crear ceremonia'}
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
                                    Nombre de la ceremonia
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
                                    <Label htmlFor="fecha_inicio">
                                        Fecha y hora inicio
                                    </Label>
                                    <Input
                                        id="fecha_inicio"
                                        name="fecha_inicio"
                                        type="datetime-local"
                                        value={valores.fecha_inicio}
                                        onChange={(evt) =>
                                            actualizar(
                                                'fecha_inicio',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {errors.fecha_inicio && (
                                        <p className="text-sm text-destructive">
                                            {errors.fecha_inicio}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="fecha_fin">
                                        Fecha y hora fin
                                    </Label>
                                    <Input
                                        id="fecha_fin"
                                        name="fecha_fin"
                                        type="datetime-local"
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
                            </div>

                            <div className="grid gap-1.5">
                                <Label>Generación (periodo escolar)</Label>
                                <input
                                    type="hidden"
                                    name="periodo"
                                    value={valores.periodo}
                                />
                                <Select
                                    value={valores.periodo}
                                    onValueChange={(valor) =>
                                        actualizar('periodo', valor)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Selecciona un periodo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {catalogos.periodos.map((periodo) => (
                                            <SelectItem
                                                key={periodo.value}
                                                value={periodo.value}
                                            >
                                                {periodo.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.periodo && (
                                    <p className="text-sm text-destructive">
                                        {errors.periodo}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label>Carreras participantes</Label>
                                {valores.carreras.map((c, i) => (
                                    <input
                                        key={`${c.carrera}-${c.reticula}`}
                                        type="hidden"
                                        name={`carreras[${i}][carrera]`}
                                        value={c.carrera}
                                    />
                                ))}
                                {valores.carreras.map((c, i) => (
                                    <input
                                        key={`${c.carrera}-${c.reticula}-r`}
                                        type="hidden"
                                        name={`carreras[${i}][reticula]`}
                                        value={c.reticula}
                                    />
                                ))}
                                <div className="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto rounded-md border border-input p-3">
                                    {catalogos.carreras.map((carrera) => {
                                        const [cve, ret] =
                                            carrera.value.split('-');
                                        const marcado = valores.carreras.some(
                                            (c) =>
                                                c.carrera === cve &&
                                                c.reticula === Number(ret),
                                        );

                                        return (
                                            <label
                                                key={carrera.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={marcado}
                                                    onCheckedChange={(valor) =>
                                                        alternarCarrera(
                                                            carrera.value,
                                                            valor === true,
                                                        )
                                                    }
                                                />
                                                {carrera.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="minutos_anticipados_graduados">
                                        Min. anticipación egresados
                                    </Label>
                                    <Input
                                        id="minutos_anticipados_graduados"
                                        name="minutos_anticipados_graduados"
                                        type="number"
                                        min={0}
                                        value={
                                            valores.minutos_anticipados_graduados
                                        }
                                        onChange={(evt) =>
                                            actualizar(
                                                'minutos_anticipados_graduados',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="minutos_anticipados_invitados">
                                        Min. anticipación invitados
                                    </Label>
                                    <Input
                                        id="minutos_anticipados_invitados"
                                        name="minutos_anticipados_invitados"
                                        type="number"
                                        min={0}
                                        value={
                                            valores.minutos_anticipados_invitados
                                        }
                                        onChange={(evt) =>
                                            actualizar(
                                                'minutos_anticipados_invitados',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="duracion_horas_acceso">
                                        Horas de acceso
                                    </Label>
                                    <Input
                                        id="duracion_horas_acceso"
                                        name="duracion_horas_acceso"
                                        type="number"
                                        min={1}
                                        max={24}
                                        value={valores.duracion_horas_acceso}
                                        onChange={(evt) =>
                                            actualizar(
                                                'duracion_horas_acceso',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="invitados_por_defecto">
                                        Invitados por defecto
                                    </Label>
                                    <Input
                                        id="invitados_por_defecto"
                                        name="invitados_por_defecto"
                                        type="number"
                                        min={0}
                                        value={valores.invitados_por_defecto}
                                        onChange={(evt) =>
                                            actualizar(
                                                'invitados_por_defecto',
                                                evt.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>
                            </div>

                            <input
                                type="hidden"
                                name="tipo_acceso"
                                value={valores.tipo_acceso}
                            />

                            <div className="grid gap-1.5">
                                <Label htmlFor="descripcion">
                                    Descripción (opcional)
                                </Label>
                                <Textarea
                                    id="descripcion"
                                    name="descripcion"
                                    value={valores.descripcion}
                                    onChange={(evt) =>
                                        actualizar(
                                            'descripcion',
                                            evt.target.value,
                                        )
                                    }
                                />
                            </div>

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

function DetalleCeremoniaDialog({
    ceremonia,
    onOpenChange,
}: {
    ceremonia: CeremoniaFila | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={ceremonia !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {ceremonia && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{ceremonia.nombre}</DialogTitle>
                        </DialogHeader>

                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha y hora inicio
                                </dt>
                                <dd className="font-medium">
                                    {ceremonia.fechaInicioLabel}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Fecha y hora fin
                                </dt>
                                <dd className="font-medium">
                                    {ceremonia.fechaFinLabel}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Periodo
                                </dt>
                                <dd className="font-medium">
                                    {ceremonia.periodo?.label ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Creado por
                                </dt>
                                <dd className="font-medium">
                                    {ceremonia.creadorNombre ?? '—'}
                                </dd>
                            </div>
                            <div className="col-span-2">
                                <dt className="text-muted-foreground">
                                    Carreras participantes
                                </dt>
                                <dd className="font-medium">
                                    {ceremonia.carreras.length > 0
                                        ? ceremonia.carreras
                                              .map((c) => c.nombre)
                                              .join(', ')
                                        : '—'}
                                </dd>
                            </div>
                            {ceremonia.descripcion && (
                                <div className="col-span-2">
                                    <dt className="text-muted-foreground">
                                        Descripción
                                    </dt>
                                    <dd className="font-medium">
                                        {ceremonia.descripcion}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function GestionCeremonias({
    usuarioId,
    ceremonias: listado,
    catalogos,
}: GestionCeremoniasProps) {
    const [dialogoCreacionAbierto, setDialogoCreacionAbierto] = useState(false);
    const [ceremoniaAEditar, setCeremoniaAEditar] =
        useState<CeremoniaFila | null>(null);
    const [ceremoniaAVer, setCeremoniaAVer] = useState<CeremoniaFila | null>(
        null,
    );

    function cambiarEstatus(ceremonia: CeremoniaFila, estatus: boolean) {
        router.patch(
            ceremoniasRoutes.estatus(ceremonia.id).url,
            { estatus },
            { preserveScroll: true },
        );
    }

    function eliminar(ceremonia: CeremoniaFila) {
        if (
            !window.confirm(
                `¿Eliminar la ceremonia "${ceremonia.nombre}"? Esta acción no se puede deshacer.`,
            )
        ) {
            return;
        }

        router.delete(ceremoniasRoutes.destroy(ceremonia.id).url, {
            preserveScroll: true,
            onError: (errores) =>
                toast.error(
                    errores.ceremonia ?? 'No se pudo eliminar la ceremonia.',
                ),
        });
    }

    return (
        <>
            <Head title="Gestión de ceremonia" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                            Gestión de ceremonia
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Graduación
                        </p>
                    </div>

                    <Button
                        variant="accent"
                        onClick={() => setDialogoCreacionAbierto(true)}
                    >
                        <Plus />
                        Crear ceremonia
                    </Button>
                </div>

                <Fieldset className="flex flex-1 flex-col gap-4">
                    <FieldsetLegend>Consultar ceremonia</FieldsetLegend>

                    <div className="overflow-x-auto">
                        {listado.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Todavía no se han creado ceremonias.
                            </p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                        <th className="px-3 py-2 font-medium">
                                            Código
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Nombre
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Fecha y horario
                                        </th>
                                        <th className="px-3 py-2 font-medium">
                                            Tutores
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
                                    {listado.data.map((ceremonia) => (
                                        <tr
                                            key={ceremonia.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2 whitespace-nowrap text-muted-foreground tabular-nums">
                                                {ceremonia.codigo}
                                            </td>
                                            <td className="px-3 py-2">
                                                {ceremonia.nombre}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap tabular-nums">
                                                {ceremonia.fechaInicioLabel} –{' '}
                                                {ceremonia.fechaFinLabel}
                                            </td>
                                            <td className="px-3 py-2">
                                                {ceremonia.creadorNombre ?? '—'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        checked={
                                                            ceremonia.estatus
                                                        }
                                                        onCheckedChange={(
                                                            valor,
                                                        ) =>
                                                            cambiarEstatus(
                                                                ceremonia,
                                                                valor,
                                                            )
                                                        }
                                                    />
                                                    <Badge
                                                        variant={
                                                            ceremonia.estatus
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {ceremonia.estatus
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
                                                        title="Ir"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={ceremoniasRoutes.show(
                                                                ceremonia.id,
                                                            )}
                                                        >
                                                            <LogIn />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Ver"
                                                        onClick={() =>
                                                            setCeremoniaAVer(
                                                                ceremonia,
                                                            )
                                                        }
                                                    >
                                                        <Eye />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        title="Editar"
                                                        onClick={() =>
                                                            setCeremoniaAEditar(
                                                                ceremonia,
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
                                                            ceremonia.creadoPor !==
                                                            usuarioId
                                                                ? 'Solo quien creó la ceremonia puede eliminarla'
                                                                : 'Eliminar'
                                                        }
                                                        disabled={
                                                            ceremonia.creadoPor !==
                                                            usuarioId
                                                        }
                                                        onClick={() =>
                                                            eliminar(ceremonia)
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
                            {listado.meta.hasta} de {listado.meta.total}{' '}
                            ceremonias
                        </p>
                    )}
                </Fieldset>
            </div>

            <CeremoniaFormDialog
                ceremonia={null}
                catalogos={catalogos}
                open={dialogoCreacionAbierto}
                onOpenChange={setDialogoCreacionAbierto}
            />

            <CeremoniaFormDialog
                key={ceremoniaAEditar?.id ?? 'sin-edicion'}
                ceremonia={ceremoniaAEditar}
                catalogos={catalogos}
                open={ceremoniaAEditar !== null}
                onOpenChange={(abierto) =>
                    !abierto && setCeremoniaAEditar(null)
                }
            />

            <DetalleCeremoniaDialog
                ceremonia={ceremoniaAVer}
                onOpenChange={(abierto) => !abierto && setCeremoniaAVer(null)}
            />
        </>
    );
}

GestionCeremonias.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'Gestión de ceremonia', href: ceremoniasRoutes.index() },
    ],
};
