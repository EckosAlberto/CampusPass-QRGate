import { Head, router } from '@inertiajs/react';
import {
    Archive,
    Bell,
    CalendarOff,
    Clock,
    Database,
    FileClock,
    Lock,
    Pencil,
    Plus,
    RefreshCw,
    Save,
    Settings,
    ShieldCheck,
    Trash2,
    UserCog,
    UserPlus,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ROLES_INSTITUCIONALES } from '@/lib/roles-institucionales';
import { configuracion, dashboard } from '@/routes';
import configuracionRoutes from '@/routes/configuracion';
import excepcionesRoutes from '@/routes/configuracion/horarios/excepciones';
import usuariosRoutes from '@/routes/configuracion/usuarios';
import type {
    AjustesBiblioteca,
    ConfiguracionProps,
    ExcepcionHorario,
    HorarioDia,
    NotificacionEvento,
    PermisoFila,
    RolResumen,
    UsuarioBiblioteca,
} from '@/types';

const PESTANAS = [
    { valor: 'general', etiqueta: 'General', icon: Settings },
    { valor: 'usuarios', etiqueta: 'Usuarios y roles', icon: UserCog },
    { valor: 'permisos', etiqueta: 'Permisos', icon: Lock },
    { valor: 'horarios', etiqueta: 'Horarios', icon: Clock },
    { valor: 'notificaciones', etiqueta: 'Notificaciones', icon: Bell },
    { valor: 'sistema', etiqueta: 'Sistema', icon: Settings },
] as const;

const ETIQUETAS_EVENTO: Record<string, string> = {
    incidencia: 'Nueva incidencia reportada',
    reporte: 'Reporte generado',
    'acceso-denegado': 'Acceso denegado',
    'capacidad-maxima': 'Capacidad máxima alcanzada',
    'resumen-diario': 'Resumen diario de actividad',
};

function InfoFila({ etiqueta, valor }: { etiqueta: string; valor: string }) {
    return (
        <div className="flex items-center justify-between border-b border-border/60 py-2 text-sm last:border-0">
            <span className="text-muted-foreground">{etiqueta}</span>
            <span className="font-medium">{valor}</span>
        </div>
    );
}

function CampoFila({
    etiqueta,
    children,
}: {
    etiqueta: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{etiqueta}</span>
            {children}
        </div>
    );
}

function guardarAjustes(
    valores: Partial<AjustesToPayload>,
    mensaje: string,
    onFinish?: () => void,
) {
    router.patch(configuracionRoutes.ajustes().url, mapearAjustes(valores), {
        preserveScroll: true,
        onSuccess: () => toast.success(mensaje),
        onError: (errores) =>
            toast.error(Object.values(errores)[0] ?? 'No se pudo guardar.'),
        onFinish,
    });
}

function proximamente(accion: string) {
    toast.info(`${accion} estará disponible próximamente.`);
}

// El backend usa snake_case; el front usa camelCase. Este tipo/función
// evitan tener que repetir el mapeo en cada fieldset.
type AjustesToPayload = AjustesBiblioteca;

function mapearAjustes(valores: Partial<AjustesToPayload>) {
    const mapa: Record<keyof AjustesToPayload, string> = {
        nombreSistema: 'nombre_sistema',
        institucion: 'institucion',
        nombreBiblioteca: 'nombre_biblioteca',
        capacidadMaxima: 'capacidad_maxima',
        horarioApertura: 'horario_apertura',
        horarioCierre: 'horario_cierre',
        qrActivo: 'qr_activo',
        qrValidezMinutos: 'qr_validez_minutos',
        qrPermitirReingreso: 'qr_permitir_reingreso',
        qrNotificarCorreo: 'qr_notificar_correo',
        notifIncidencias: 'notif_incidencias',
        notifReportes: 'notif_reportes',
        notifResumenDiario: 'notif_resumen_diario',
        notifCorreo: 'notif_correo',
        sesionInactividadMinutos: 'sesion_inactividad_minutos',
        sesionCerrarAuto: 'sesion_cerrar_auto',
        sesionMantenerActiva: 'sesion_mantener_activa',
        zonaHoraria: 'zona_horaria',
        idioma: 'idioma',
        formatoFecha: 'formato_fecha',
        nivelRegistro: 'nivel_registro',
        modoMantenimiento: 'modo_mantenimiento',
        canalCorreoActivo: 'canal_correo_activo',
        canalPushActivo: 'canal_push_activo',
        version: 'version',
        baseDeDatosConectada: 'base_de_datos_conectada',
        ultimaActualizacion: 'ultima_actualizacion',
    };

    const payload: Record<string, string | number | boolean | null> = {};

    for (const [clave, valor] of Object.entries(valores)) {
        const claveBackend = mapa[clave as keyof AjustesToPayload];

        if (
            claveBackend &&
            claveBackend !== 'version' &&
            claveBackend !== 'base_de_datos_conectada' &&
            claveBackend !== 'ultima_actualizacion'
        ) {
            payload[claveBackend] = valor;
        }
    }

    return payload;
}

function EditarInfoSistemaDialog({
    ajustes,
    open,
    onOpenChange,
}: {
    ajustes: AjustesBiblioteca;
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [nombreSistema, setNombreSistema] = useState(ajustes.nombreSistema);
    const [institucion, setInstitucion] = useState(ajustes.institucion);
    const [guardando, setGuardando] = useState(false);

    function guardar() {
        setGuardando(true);
        guardarAjustes(
            { nombreSistema, institucion },
            'Información del sistema actualizada.',
            () => {
                setGuardando(false);
                onOpenChange(false);
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Información del sistema</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="nombre-sistema">
                            Nombre del sistema
                        </Label>
                        <Input
                            id="nombre-sistema"
                            value={nombreSistema}
                            onChange={(e) => setNombreSistema(e.target.value)}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="institucion">Institución</Label>
                        <Input
                            id="institucion"
                            value={institucion}
                            onChange={(e) => setInstitucion(e.target.value)}
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
                        disabled={guardando}
                        onClick={guardar}
                    >
                        {guardando && <Spinner />}
                        Guardar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function VerRegistrosDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [lineas, setLineas] = useState<string[] | null>(null);
    const [cargando, setCargando] = useState(false);

    function abrir(siguiente: boolean) {
        if (siguiente && lineas === null) {
            setCargando(true);
            fetch(configuracionRoutes.registros().url, {
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((datos) => setLineas(datos.lineas ?? []))
                .catch(() =>
                    toast.error('No se pudieron cargar los registros.'),
                )
                .finally(() => setCargando(false));
        }

        onOpenChange(siguiente);
    }

    return (
        <Dialog open={open} onOpenChange={abrir}>
            <DialogContent className="max-h-[80vh] overflow-hidden sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Registros del sistema</DialogTitle>
                </DialogHeader>

                <div className="max-h-[60vh] overflow-y-auto rounded-md bg-muted p-3">
                    {cargando ? (
                        <p className="text-sm text-muted-foreground">
                            Cargando…
                        </p>
                    ) : !lineas || lineas.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay registros disponibles todavía.
                        </p>
                    ) : (
                        <pre className="text-xs whitespace-pre-wrap">
                            {lineas.join('\n')}
                        </pre>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function PestanaGeneral({ ajustes }: { ajustes: AjustesBiblioteca }) {
    const [parametros, setParametros] = useState({
        nombreBiblioteca: ajustes.nombreBiblioteca,
        capacidadMaxima: ajustes.capacidadMaxima,
        horarioApertura: ajustes.horarioApertura,
        horarioCierre: ajustes.horarioCierre,
    });
    const [guardandoParametros, setGuardandoParametros] = useState(false);

    const [qr, setQr] = useState({
        qrActivo: ajustes.qrActivo,
        qrValidezMinutos: ajustes.qrValidezMinutos,
        qrPermitirReingreso: ajustes.qrPermitirReingreso,
        qrNotificarCorreo: ajustes.qrNotificarCorreo,
    });
    const [guardandoQr, setGuardandoQr] = useState(false);

    const [notif, setNotif] = useState({
        notifIncidencias: ajustes.notifIncidencias,
        notifReportes: ajustes.notifReportes,
        notifResumenDiario: ajustes.notifResumenDiario,
        notifCorreo: ajustes.notifCorreo,
    });
    const [guardandoNotif, setGuardandoNotif] = useState(false);

    const [sesion, setSesion] = useState({
        sesionInactividadMinutos: ajustes.sesionInactividadMinutos,
        sesionCerrarAuto: ajustes.sesionCerrarAuto,
        sesionMantenerActiva: ajustes.sesionMantenerActiva,
    });
    const [guardandoSesion, setGuardandoSesion] = useState(false);

    const [editarInfoAbierto, setEditarInfoAbierto] = useState(false);

    const accionesProximamente = [
        { etiqueta: 'Restaurar base de datos', icon: Archive },
        { etiqueta: 'Limpiar registros antiguos', icon: Archive },
        { etiqueta: 'Reiniciar sistema', icon: Settings },
    ];

    return (
        <div className="grid gap-4 lg:grid-cols-3">
            <Fieldset>
                <FieldsetLegend>Información del sistema</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Datos generales de la aplicación.
                </p>

                <InfoFila
                    etiqueta="Nombre del sistema"
                    valor={ajustes.nombreSistema}
                />
                <InfoFila etiqueta="Versión" valor={ajustes.version} />
                <InfoFila etiqueta="Institución" valor={ajustes.institucion} />
                <InfoFila
                    etiqueta="Base de datos"
                    valor={
                        ajustes.baseDeDatosConectada
                            ? 'Conectada'
                            : 'Sin conexión'
                    }
                />
                <InfoFila
                    etiqueta="Última actualización"
                    valor={ajustes.ultimaActualizacion ?? '—'}
                />

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setEditarInfoAbierto(true)}
                    >
                        <Pencil />
                        Editar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Parámetros de la biblioteca</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Configura los datos principales de la biblioteca.
                </p>

                <CampoFila etiqueta="Nombre de la biblioteca">
                    <Input
                        className="w-48"
                        value={parametros.nombreBiblioteca}
                        onChange={(evento) =>
                            setParametros((previo) => ({
                                ...previo,
                                nombreBiblioteca: evento.target.value,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Capacidad máxima">
                    <Input
                        type="number"
                        min={0}
                        className="w-24"
                        value={parametros.capacidadMaxima}
                        onChange={(evento) =>
                            setParametros((previo) => ({
                                ...previo,
                                capacidadMaxima: Number(evento.target.value),
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Horario de apertura">
                    <Input
                        type="time"
                        className="w-32"
                        value={parametros.horarioApertura}
                        onChange={(evento) =>
                            setParametros((previo) => ({
                                ...previo,
                                horarioApertura: evento.target.value,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Horario de cierre">
                    <Input
                        type="time"
                        className="w-32"
                        value={parametros.horarioCierre}
                        onChange={(evento) =>
                            setParametros((previo) => ({
                                ...previo,
                                horarioCierre: evento.target.value,
                            }))
                        }
                    />
                </CampoFila>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoParametros}
                        onClick={() => {
                            setGuardandoParametros(true);
                            guardarAjustes(
                                parametros,
                                'Parámetros de la biblioteca guardados.',
                                () => setGuardandoParametros(false),
                            );
                        }}
                    >
                        {guardandoParametros ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>QR y accesos</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Configura la validación de códigos QR.
                </p>

                <CampoFila etiqueta="Activar acceso con QR">
                    <Switch
                        checked={qr.qrActivo}
                        onCheckedChange={(valor) =>
                            setQr((previo) => ({ ...previo, qrActivo: valor }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Tiempo de validez del QR (minutos)">
                    <Input
                        type="number"
                        min={1}
                        className="w-20"
                        value={qr.qrValidezMinutos}
                        onChange={(evento) =>
                            setQr((previo) => ({
                                ...previo,
                                qrValidezMinutos: Number(evento.target.value),
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Permitir reingreso">
                    <Switch
                        checked={qr.qrPermitirReingreso}
                        onCheckedChange={(valor) =>
                            setQr((previo) => ({
                                ...previo,
                                qrPermitirReingreso: valor,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Notificar acceso por correo">
                    <Switch
                        checked={qr.qrNotificarCorreo}
                        onCheckedChange={(valor) =>
                            setQr((previo) => ({
                                ...previo,
                                qrNotificarCorreo: valor,
                            }))
                        }
                    />
                </CampoFila>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoQr}
                        onClick={() => {
                            setGuardandoQr(true);
                            guardarAjustes(
                                qr,
                                'Configuración de QR guardada.',
                                () => setGuardandoQr(false),
                            );
                        }}
                    >
                        {guardandoQr ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Notificaciones</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Gestiona las alertas del sistema.
                </p>

                <CampoFila etiqueta="Notificar incidencias">
                    <Switch
                        checked={notif.notifIncidencias}
                        onCheckedChange={(valor) =>
                            setNotif((previo) => ({
                                ...previo,
                                notifIncidencias: valor,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Notificar reportes generados">
                    <Switch
                        checked={notif.notifReportes}
                        onCheckedChange={(valor) =>
                            setNotif((previo) => ({
                                ...previo,
                                notifReportes: valor,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Enviar resumen diario">
                    <Switch
                        checked={notif.notifResumenDiario}
                        onCheckedChange={(valor) =>
                            setNotif((previo) => ({
                                ...previo,
                                notifResumenDiario: valor,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Notificaciones por correo">
                    <Switch
                        checked={notif.notifCorreo}
                        onCheckedChange={(valor) =>
                            setNotif((previo) => ({
                                ...previo,
                                notifCorreo: valor,
                            }))
                        }
                    />
                </CampoFila>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoNotif}
                        onClick={() => {
                            setGuardandoNotif(true);
                            guardarAjustes(
                                notif,
                                'Preferencias de notificaciones guardadas.',
                                () => setGuardandoNotif(false),
                            );
                        }}
                    >
                        {guardandoNotif ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Configuración de la sesión</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Ajusta el comportamiento de la sesión.
                </p>

                <CampoFila etiqueta="Tiempo de inactividad (minutos)">
                    <Input
                        type="number"
                        min={1}
                        className="w-20"
                        value={sesion.sesionInactividadMinutos}
                        onChange={(evento) =>
                            setSesion((previo) => ({
                                ...previo,
                                sesionInactividadMinutos: Number(
                                    evento.target.value,
                                ),
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Cerrar sesión automáticamente">
                    <Switch
                        checked={sesion.sesionCerrarAuto}
                        onCheckedChange={(valor) =>
                            setSesion((previo) => ({
                                ...previo,
                                sesionCerrarAuto: valor,
                            }))
                        }
                    />
                </CampoFila>
                <CampoFila etiqueta="Mantener sesión activa en el navegador">
                    <Switch
                        checked={sesion.sesionMantenerActiva}
                        onCheckedChange={(valor) =>
                            setSesion((previo) => ({
                                ...previo,
                                sesionMantenerActiva: valor,
                            }))
                        }
                    />
                </CampoFila>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoSesion}
                        onClick={() => {
                            setGuardandoSesion(true);
                            guardarAjustes(
                                sesion,
                                'Configuración de la sesión guardada.',
                                () => setGuardandoSesion(false),
                            );
                        }}
                    >
                        {guardandoSesion ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Mantenimiento y respaldo</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Realiza tareas de mantenimiento del sistema.
                </p>

                <div className="flex flex-col">
                    <a
                        href={configuracionRoutes.respaldo().url}
                        className="flex items-center justify-between border-b border-border/60 py-3 text-left text-sm last:border-0 hover:text-primary"
                    >
                        <span className="flex items-center gap-2">
                            <Database className="size-4 text-muted-foreground" />
                            Respaldar base de datos
                        </span>
                        <span className="text-muted-foreground">›</span>
                    </a>

                    {accionesProximamente.map((accion) => (
                        <button
                            key={accion.etiqueta}
                            type="button"
                            onClick={() => proximamente(accion.etiqueta)}
                            className="flex items-center justify-between border-b border-border/60 py-3 text-left text-sm last:border-0 hover:text-primary"
                        >
                            <span className="flex items-center gap-2">
                                <accion.icon className="size-4 text-muted-foreground" />
                                {accion.etiqueta}
                            </span>
                            <span className="text-muted-foreground">›</span>
                        </button>
                    ))}
                </div>
            </Fieldset>

            <EditarInfoSistemaDialog
                ajustes={ajustes}
                open={editarInfoAbierto}
                onOpenChange={setEditarInfoAbierto}
            />
        </div>
    );
}

function obtenerIniciales(nombre: string): string {
    const iniciales = nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0]?.toUpperCase())
        .join('');

    return iniciales || '?';
}

function NuevoUsuarioDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [nombre, setNombre] = useState('');
    const [apellidoPaterno, setApellidoPaterno] = useState('');
    const [apellidoMaterno, setApellidoMaterno] = useState('');
    const [correo, setCorreo] = useState('');
    const [rol, setRol] = useState('');
    const [fechaNacimiento, setFechaNacimiento] = useState('');
    const [curp, setCurp] = useState('');
    const [rfc, setRfc] = useState('');
    const [telefono, setTelefono] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [enviando, setEnviando] = useState(false);

    function limpiar() {
        setNombre('');
        setApellidoPaterno('');
        setApellidoMaterno('');
        setCorreo('');
        setRol('');
        setFechaNacimiento('');
        setCurp('');
        setRfc('');
        setTelefono('');
        setPassword('');
        setPasswordConfirmation('');
        setErrores({});
    }

    function crear() {
        setEnviando(true);

        router.post(
            usuariosRoutes.store().url,
            {
                name: nombre,
                apellido_paterno: apellidoPaterno,
                apellido_materno: apellidoMaterno,
                email: correo,
                tipo: rol,
                fecha_nacimiento: fechaNacimiento,
                curp,
                rfc,
                telefono,
                password,
                password_confirmation: passwordConfirmation,
            },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const mensaje = (
                        page.props.flash as { success?: string } | undefined
                    )?.success;
                    toast.success(mensaje ?? 'Usuario creado.');
                    limpiar();
                    onOpenChange(false);
                },
                onError: setErrores,
                onFinish: () => setEnviando(false),
            },
        );
    }

    const camposCompletos =
        nombre &&
        apellidoPaterno &&
        apellidoMaterno &&
        correo &&
        rol &&
        fechaNacimiento &&
        curp &&
        rfc &&
        telefono &&
        password &&
        passwordConfirmation;

    return (
        <Dialog
            open={open}
            onOpenChange={(abierto) => {
                if (!abierto) {
                    limpiar();
                }

                onOpenChange(abierto);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Nuevo usuario</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="nuevo-usuario-nombre">Nombre(s)</Label>
                        <Input
                            id="nuevo-usuario-nombre"
                            value={nombre}
                            onChange={(e) => setNombre(e.target.value)}
                        />
                        {errores.name && (
                            <p className="text-sm text-destructive">
                                {errores.name}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-apellido-paterno">
                                Apellido paterno
                            </Label>
                            <Input
                                id="nuevo-usuario-apellido-paterno"
                                value={apellidoPaterno}
                                onChange={(e) =>
                                    setApellidoPaterno(e.target.value)
                                }
                            />
                            {errores.apellido_paterno && (
                                <p className="text-sm text-destructive">
                                    {errores.apellido_paterno}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-apellido-materno">
                                Apellido materno
                            </Label>
                            <Input
                                id="nuevo-usuario-apellido-materno"
                                value={apellidoMaterno}
                                onChange={(e) =>
                                    setApellidoMaterno(e.target.value)
                                }
                            />
                            {errores.apellido_materno && (
                                <p className="text-sm text-destructive">
                                    {errores.apellido_materno}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="nuevo-usuario-correo">
                            Correo institucional
                        </Label>
                        <Input
                            id="nuevo-usuario-correo"
                            type="email"
                            value={correo}
                            onChange={(e) => setCorreo(e.target.value)}
                        />
                        {errores.email && (
                            <p className="text-sm text-destructive">
                                {errores.email}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-nacimiento">
                                Fecha de nacimiento
                            </Label>
                            <Input
                                id="nuevo-usuario-nacimiento"
                                type="date"
                                value={fechaNacimiento}
                                onChange={(e) =>
                                    setFechaNacimiento(e.target.value)
                                }
                            />
                            {errores.fecha_nacimiento && (
                                <p className="text-sm text-destructive">
                                    {errores.fecha_nacimiento}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-telefono">
                                Teléfono
                            </Label>
                            <Input
                                id="nuevo-usuario-telefono"
                                value={telefono}
                                maxLength={10}
                                placeholder="10 dígitos"
                                onChange={(e) => setTelefono(e.target.value)}
                            />
                            {errores.telefono && (
                                <p className="text-sm text-destructive">
                                    {errores.telefono}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-curp">CURP</Label>
                            <Input
                                id="nuevo-usuario-curp"
                                value={curp}
                                maxLength={18}
                                className="uppercase"
                                onChange={(e) =>
                                    setCurp(e.target.value.toUpperCase())
                                }
                            />
                            {errores.curp && (
                                <p className="text-sm text-destructive">
                                    {errores.curp}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-rfc">RFC</Label>
                            <Input
                                id="nuevo-usuario-rfc"
                                value={rfc}
                                maxLength={13}
                                className="uppercase"
                                onChange={(e) =>
                                    setRfc(e.target.value.toUpperCase())
                                }
                            />
                            {errores.rfc && (
                                <p className="text-sm text-destructive">
                                    {errores.rfc}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-password">
                                Contraseña
                            </Label>
                            <Input
                                id="nuevo-usuario-password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                            />
                            {errores.password && (
                                <p className="text-sm text-destructive">
                                    {errores.password}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="nuevo-usuario-password-confirmation">
                                Confirmar contraseña
                            </Label>
                            <Input
                                id="nuevo-usuario-password-confirmation"
                                type="password"
                                value={passwordConfirmation}
                                onChange={(e) =>
                                    setPasswordConfirmation(e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="nuevo-usuario-rol">Rol</Label>
                        <Select value={rol} onValueChange={setRol}>
                            <SelectTrigger
                                id="nuevo-usuario-rol"
                                className="w-full"
                            >
                                <SelectValue placeholder="Selecciona un rol" />
                            </SelectTrigger>
                            <SelectContent>
                                {ROLES_INSTITUCIONALES.map((nombreRol) => (
                                    <SelectItem
                                        key={nombreRol}
                                        value={nombreRol}
                                    >
                                        {nombreRol}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errores.tipo && (
                            <p className="text-sm text-destructive">
                                {errores.tipo}
                            </p>
                        )}
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
                        disabled={enviando || !camposCompletos}
                        onClick={crear}
                    >
                        {enviando && <Spinner />}
                        Crear usuario
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// Diálogo para editar un usuario existente: mismos campos que "Nuevo
// usuario" sin contraseña (esa se cambia desde la cuenta del propio
// usuario, en Seguridad).
function EditarUsuarioDialog({
    usuario,
    onOpenChange,
}: {
    usuario: UsuarioBiblioteca | null;
    onOpenChange: (abierto: boolean) => void;
}) {
    return (
        <Dialog open={usuario !== null} onOpenChange={onOpenChange}>
            <DialogContent>
                {usuario && (
                    <EditarUsuarioFormulario
                        key={usuario.id}
                        usuario={usuario}
                        onGuardado={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

// Se remonta con key={usuario.id} en vez de sincronizar el prop vía
// useEffect: así cada usuario abre con su propio estado inicial sin violar
// la regla react-hooks/set-state-in-effect.
function EditarUsuarioFormulario({
    usuario,
    onGuardado,
}: {
    usuario: UsuarioBiblioteca;
    onGuardado: () => void;
}) {
    const [nombre, setNombre] = useState(usuario.nombrePila);
    const [apellidoPaterno, setApellidoPaterno] = useState(
        usuario.apellidoPaterno ?? '',
    );
    const [apellidoMaterno, setApellidoMaterno] = useState(
        usuario.apellidoMaterno ?? '',
    );
    const [correo, setCorreo] = useState(usuario.correo);
    const [rol, setRol] = useState(usuario.rol ?? '');
    const [fechaNacimiento, setFechaNacimiento] = useState(
        usuario.fechaNacimiento ?? '',
    );
    const [curp, setCurp] = useState(usuario.curp ?? '');
    const [rfc, setRfc] = useState(usuario.rfc ?? '');
    const [telefono, setTelefono] = useState(usuario.telefono ?? '');
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [enviando, setEnviando] = useState(false);

    function guardar() {
        setEnviando(true);

        router.patch(
            usuariosRoutes.update(usuario.id).url,
            {
                name: nombre,
                apellido_paterno: apellidoPaterno,
                apellido_materno: apellidoMaterno,
                email: correo,
                tipo: rol,
                fecha_nacimiento: fechaNacimiento,
                curp,
                rfc,
                telefono,
            },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const mensaje = (
                        page.props.flash as { success?: string } | undefined
                    )?.success;
                    toast.success(mensaje ?? 'Usuario actualizado.');
                    onGuardado();
                },
                onError: setErrores,
                onFinish: () => setEnviando(false),
            },
        );
    }

    const camposCompletos =
        nombre &&
        apellidoPaterno &&
        apellidoMaterno &&
        correo &&
        rol &&
        fechaNacimiento &&
        curp &&
        rfc &&
        telefono;

    return (
        <>
            <DialogHeader>
                <DialogTitle>Editar usuario</DialogTitle>
            </DialogHeader>

            <div className="grid gap-4">
                <div className="grid gap-1.5">
                    <Label htmlFor="editar-usuario-nombre">Nombre(s)</Label>
                    <Input
                        id="editar-usuario-nombre"
                        value={nombre}
                        onChange={(e) => setNombre(e.target.value)}
                    />
                    {errores.name && (
                        <p className="text-sm text-destructive">
                            {errores.name}
                        </p>
                    )}
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-apellido-paterno">
                            Apellido paterno
                        </Label>
                        <Input
                            id="editar-usuario-apellido-paterno"
                            value={apellidoPaterno}
                            onChange={(e) => setApellidoPaterno(e.target.value)}
                        />
                        {errores.apellido_paterno && (
                            <p className="text-sm text-destructive">
                                {errores.apellido_paterno}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-apellido-materno">
                            Apellido materno
                        </Label>
                        <Input
                            id="editar-usuario-apellido-materno"
                            value={apellidoMaterno}
                            onChange={(e) => setApellidoMaterno(e.target.value)}
                        />
                        {errores.apellido_materno && (
                            <p className="text-sm text-destructive">
                                {errores.apellido_materno}
                            </p>
                        )}
                    </div>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="editar-usuario-correo">
                        Correo institucional
                    </Label>
                    <Input
                        id="editar-usuario-correo"
                        type="email"
                        value={correo}
                        onChange={(e) => setCorreo(e.target.value)}
                    />
                    {errores.email && (
                        <p className="text-sm text-destructive">
                            {errores.email}
                        </p>
                    )}
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-nacimiento">
                            Fecha de nacimiento
                        </Label>
                        <Input
                            id="editar-usuario-nacimiento"
                            type="date"
                            value={fechaNacimiento}
                            onChange={(e) => setFechaNacimiento(e.target.value)}
                        />
                        {errores.fecha_nacimiento && (
                            <p className="text-sm text-destructive">
                                {errores.fecha_nacimiento}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-telefono">
                            Teléfono
                        </Label>
                        <Input
                            id="editar-usuario-telefono"
                            value={telefono}
                            maxLength={10}
                            placeholder="10 dígitos"
                            onChange={(e) => setTelefono(e.target.value)}
                        />
                        {errores.telefono && (
                            <p className="text-sm text-destructive">
                                {errores.telefono}
                            </p>
                        )}
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-curp">CURP</Label>
                        <Input
                            id="editar-usuario-curp"
                            value={curp}
                            maxLength={18}
                            className="uppercase"
                            onChange={(e) =>
                                setCurp(e.target.value.toUpperCase())
                            }
                        />
                        {errores.curp && (
                            <p className="text-sm text-destructive">
                                {errores.curp}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="editar-usuario-rfc">RFC</Label>
                        <Input
                            id="editar-usuario-rfc"
                            value={rfc}
                            maxLength={13}
                            className="uppercase"
                            onChange={(e) =>
                                setRfc(e.target.value.toUpperCase())
                            }
                        />
                        {errores.rfc && (
                            <p className="text-sm text-destructive">
                                {errores.rfc}
                            </p>
                        )}
                    </div>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="editar-usuario-rol">Rol</Label>
                    <Select value={rol} onValueChange={setRol}>
                        <SelectTrigger
                            id="editar-usuario-rol"
                            className="w-full"
                        >
                            <SelectValue placeholder="Selecciona un rol" />
                        </SelectTrigger>
                        <SelectContent>
                            {ROLES_INSTITUCIONALES.map((nombreRol) => (
                                <SelectItem key={nombreRol} value={nombreRol}>
                                    {nombreRol}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errores.tipo && (
                        <p className="text-sm text-destructive">
                            {errores.tipo}
                        </p>
                    )}
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
                    disabled={enviando || !camposCompletos}
                    onClick={guardar}
                >
                    {enviando && <Spinner />}
                    Guardar cambios
                </Button>
            </DialogFooter>
        </>
    );
}

function RolSelectCelda({ usuario }: { usuario: UsuarioBiblioteca }) {
    function cambiar(nuevoRol: string) {
        if (nuevoRol === usuario.rol) {
            return;
        }

        router.patch(
            usuariosRoutes.update(usuario.id).url,
            { tipo: nuevoRol },
            {
                preserveScroll: true,
                onError: () => toast.error('No se pudo actualizar el rol.'),
            },
        );
    }

    return (
        <Select value={usuario.rol ?? undefined} onValueChange={cambiar}>
            <SelectTrigger className="w-44">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {ROLES_INSTITUCIONALES.map((nombreRol) => (
                    <SelectItem key={nombreRol} value={nombreRol}>
                        {nombreRol}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function PestanaUsuarios({
    usuarios,
    roles,
}: {
    usuarios: UsuarioBiblioteca[];
    roles: RolResumen[];
}) {
    const [dialogoAbierto, setDialogoAbierto] = useState(false);
    const [usuarioEditando, setUsuarioEditando] =
        useState<UsuarioBiblioteca | null>(null);

    function alternarActivo(usuario: UsuarioBiblioteca) {
        router.patch(
            usuariosRoutes.update(usuario.id).url,
            { activo: !usuario.activo },
            {
                preserveScroll: true,
                onError: (errores) =>
                    toast.error(
                        errores.activo ?? 'No se pudo actualizar el usuario.',
                    ),
            },
        );
    }

    function eliminar(usuario: UsuarioBiblioteca) {
        if (
            !window.confirm(
                `¿Eliminar al usuario "${usuario.nombre}"? Esta acción no se puede deshacer.`,
            )
        ) {
            return;
        }

        router.delete(usuariosRoutes.destroy(usuario.id).url, {
            preserveScroll: true,
        });
    }

    return (
        <div className="flex flex-col gap-4">
            <Fieldset>
                <FieldsetLegend>Usuarios del sistema</FieldsetLegend>
                <div className="mb-2 flex items-center justify-between gap-4">
                    <p className="text-sm text-muted-foreground">
                        Administra las cuentas del personal con acceso al panel.
                    </p>
                    <Button
                        type="button"
                        variant="accent"
                        size="sm"
                        onClick={() => setDialogoAbierto(true)}
                    >
                        <UserPlus />
                        Nuevo usuario
                    </Button>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-primary text-left text-xs font-semibold tracking-wide text-primary-foreground uppercase">
                                <th className="px-3 py-2 font-medium">
                                    Usuario
                                </th>
                                <th className="px-3 py-2 font-medium">Rol</th>
                                <th className="px-3 py-2 font-medium">
                                    Activo
                                </th>
                                <th className="px-3 py-2 font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {usuarios.map((usuario) => (
                                <tr
                                    key={usuario.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="flex items-center gap-3 px-3 py-2">
                                        <Avatar>
                                            <AvatarFallback>
                                                {obtenerIniciales(
                                                    usuario.nombre,
                                                )}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="flex flex-col">
                                            <span className="font-medium">
                                                {usuario.nombre}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {usuario.correo}
                                            </span>
                                            <span className="mt-0.5 flex flex-wrap gap-x-2 text-xs text-muted-foreground">
                                                {usuario.curp && (
                                                    <span>
                                                        CURP: {usuario.curp}
                                                    </span>
                                                )}
                                                {usuario.rfc && (
                                                    <span>
                                                        RFC: {usuario.rfc}
                                                    </span>
                                                )}
                                                {usuario.telefono && (
                                                    <span>
                                                        Tel: {usuario.telefono}
                                                    </span>
                                                )}
                                                {usuario.fechaNacimiento && (
                                                    <span>
                                                        Nac.:{' '}
                                                        {
                                                            usuario.fechaNacimiento
                                                        }
                                                    </span>
                                                )}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">
                                        <RolSelectCelda usuario={usuario} />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Switch
                                            checked={usuario.activo}
                                            onCheckedChange={() =>
                                                alternarActivo(usuario)
                                            }
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                aria-label="Editar usuario"
                                                onClick={() =>
                                                    setUsuarioEditando(usuario)
                                                }
                                            >
                                                <Pencil />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                aria-label="Eliminar usuario"
                                                onClick={() =>
                                                    eliminar(usuario)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Roles disponibles</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Cada rol agrupa un conjunto de permisos; ajústalos en la
                    pestaña Permisos.
                </p>

                <div className="grid gap-4 sm:grid-cols-3">
                    {roles.map((rol) => (
                        <div
                            key={rol.clave}
                            className="flex flex-col gap-2 rounded-lg border border-border p-4"
                        >
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="size-4 text-primary" />
                                <span className="font-medium">
                                    {rol.nombre}
                                </span>
                            </div>
                            <Badge variant="secondary" className="w-fit">
                                {rol.permisos} permisos
                            </Badge>
                        </div>
                    ))}
                </div>
            </Fieldset>

            <NuevoUsuarioDialog
                open={dialogoAbierto}
                onOpenChange={setDialogoAbierto}
            />

            <EditarUsuarioDialog
                usuario={usuarioEditando}
                onOpenChange={(abierto) => !abierto && setUsuarioEditando(null)}
            />
        </div>
    );
}

function PestanaPermisos({
    permisos,
    roles,
}: {
    permisos: PermisoFila[];
    roles: RolResumen[];
}) {
    const [matriz, setMatriz] = useState<
        Record<string, Record<string, boolean>>
    >(Object.fromEntries(permisos.map((p) => [p.id, { ...p.roles }])));
    const [guardando, setGuardando] = useState(false);

    function alternar(permisoId: string, rolClave: string) {
        setMatriz((previo) => ({
            ...previo,
            [permisoId]: {
                ...previo[permisoId],
                [rolClave]: !previo[permisoId][rolClave],
            },
        }));
    }

    function guardar() {
        setGuardando(true);

        router.patch(
            configuracionRoutes.permisos().url,
            { matriz },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Permisos actualizados.'),
                onError: () =>
                    toast.error('No se pudieron guardar los permisos.'),
                onFinish: () => setGuardando(false),
            },
        );
    }

    if (roles.length === 0) {
        return (
            <Fieldset>
                <FieldsetLegend>Matriz de permisos por rol</FieldsetLegend>
                <p className="py-6 text-center text-sm text-muted-foreground">
                    Todavía no hay roles creados. Crea un usuario con un rol en
                    la pestaña "Usuarios y roles" para poder asignarle permisos
                    aquí.
                </p>
            </Fieldset>
        );
    }

    return (
        <Fieldset>
            <FieldsetLegend>Matriz de permisos por rol</FieldsetLegend>
            <p className="mb-2 text-sm text-muted-foreground">
                Define qué puede hacer cada rol dentro del sistema.
            </p>

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="py-2 pr-4 font-medium">Permiso</th>
                            {roles.map((rol) => (
                                <th
                                    key={rol.clave}
                                    className="px-4 py-2 text-center font-medium"
                                >
                                    {rol.nombre}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {permisos.map((permiso) => (
                            <tr
                                key={permiso.id}
                                className="border-b last:border-0"
                            >
                                <td className="py-2 pr-4">
                                    {permiso.etiqueta}
                                </td>
                                {roles.map((rol) => (
                                    <td
                                        key={rol.clave}
                                        className="px-4 py-2 text-center"
                                    >
                                        <Checkbox
                                            checked={
                                                matriz[permiso.id]?.[
                                                    rol.clave
                                                ] ?? false
                                            }
                                            onCheckedChange={() =>
                                                alternar(permiso.id, rol.clave)
                                            }
                                        />
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex justify-end">
                <Button
                    type="button"
                    size="sm"
                    disabled={guardando}
                    onClick={guardar}
                >
                    {guardando ? <Spinner /> : <Save />}
                    Guardar
                </Button>
            </div>
        </Fieldset>
    );
}

function NuevaExcepcionDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (abierto: boolean) => void;
}) {
    const [fecha, setFecha] = useState('');
    const [motivo, setMotivo] = useState('');
    const [cerradoTodoDia, setCerradoTodoDia] = useState(true);
    const [apertura, setApertura] = useState('10:00');
    const [cierre, setCierre] = useState('14:00');
    const [enviando, setEnviando] = useState(false);

    function limpiar() {
        setFecha('');
        setMotivo('');
        setCerradoTodoDia(true);
        setApertura('10:00');
        setCierre('14:00');
    }

    function crear() {
        setEnviando(true);

        router.post(
            excepcionesRoutes.store().url,
            {
                fecha,
                motivo,
                cerrado_todo_dia: cerradoTodoDia,
                horario_apertura: cerradoTodoDia ? null : apertura,
                horario_cierre: cerradoTodoDia ? null : cierre,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Excepción de horario agregada.');
                    limpiar();
                    onOpenChange(false);
                },
                onError: (errores) =>
                    toast.error(
                        Object.values(errores)[0] ??
                            'No se pudo agregar la excepción.',
                    ),
                onFinish: () => setEnviando(false),
            },
        );
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(abierto) => {
                if (!abierto) {
                    limpiar();
                }

                onOpenChange(abierto);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Agregar excepción de horario</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="excepcion-fecha">Fecha</Label>
                        <Input
                            id="excepcion-fecha"
                            type="date"
                            value={fecha}
                            onChange={(e) => setFecha(e.target.value)}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="excepcion-motivo">Motivo</Label>
                        <Input
                            id="excepcion-motivo"
                            value={motivo}
                            onChange={(e) => setMotivo(e.target.value)}
                        />
                    </div>
                    <CampoFila etiqueta="Cerrado todo el día">
                        <Switch
                            checked={cerradoTodoDia}
                            onCheckedChange={setCerradoTodoDia}
                        />
                    </CampoFila>
                    {!cerradoTodoDia && (
                        <div className="flex items-center gap-2">
                            <Input
                                type="time"
                                className="w-32"
                                value={apertura}
                                onChange={(e) => setApertura(e.target.value)}
                            />
                            <span className="text-sm text-muted-foreground">
                                a
                            </span>
                            <Input
                                type="time"
                                className="w-32"
                                value={cierre}
                                onChange={(e) => setCierre(e.target.value)}
                            />
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        disabled={enviando || !fecha || !motivo}
                        onClick={crear}
                    >
                        {enviando && <Spinner />}
                        Agregar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function PestanaHorarios({
    horarios,
    excepciones,
}: {
    horarios: HorarioDia[];
    excepciones: ExcepcionHorario[];
}) {
    const [dias, setDias] = useState(horarios);
    const [guardando, setGuardando] = useState(false);
    const [dialogoAbierto, setDialogoAbierto] = useState(false);

    function actualizarDia(dia: string, cambios: Partial<HorarioDia>) {
        setDias((previo) =>
            previo.map((d) => (d.dia === dia ? { ...d, ...cambios } : d)),
        );
    }

    function guardar() {
        setGuardando(true);

        router.patch(
            configuracionRoutes.horarios().url,
            { dias },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Horario de atención guardado.'),
                onError: (errores) =>
                    toast.error(
                        Object.values(errores)[0] ??
                            'No se pudo guardar el horario.',
                    ),
                onFinish: () => setGuardando(false),
            },
        );
    }

    function eliminarExcepcion(id: string) {
        router.delete(excepcionesRoutes.destroy(id).url, {
            preserveScroll: true,
        });
    }

    return (
        <div className="flex flex-col gap-4">
            <Fieldset>
                <FieldsetLegend>Horario de atención</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Define los días y horas en que la biblioteca permanece
                    abierta.
                </p>

                <div className="flex flex-col">
                    {dias.map((horario) => (
                        <div
                            key={horario.dia}
                            className="flex flex-wrap items-center justify-between gap-3 border-b border-border/60 py-2 last:border-0"
                        >
                            <div className="flex w-40 items-center gap-2">
                                <Switch
                                    checked={horario.abierto}
                                    onCheckedChange={(valor) =>
                                        actualizarDia(horario.dia, {
                                            abierto: valor,
                                        })
                                    }
                                />
                                <span className="text-sm">{horario.dia}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Input
                                    type="time"
                                    className="w-32"
                                    disabled={!horario.abierto}
                                    value={horario.apertura}
                                    onChange={(evento) =>
                                        actualizarDia(horario.dia, {
                                            apertura: evento.target.value,
                                        })
                                    }
                                />
                                <span className="text-sm text-muted-foreground">
                                    a
                                </span>
                                <Input
                                    type="time"
                                    className="w-32"
                                    disabled={!horario.abierto}
                                    value={horario.cierre}
                                    onChange={(evento) =>
                                        actualizarDia(horario.dia, {
                                            cierre: evento.target.value,
                                        })
                                    }
                                />
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardando}
                        onClick={guardar}
                    >
                        {guardando ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Días festivos y excepciones</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Fechas en las que el horario habitual no aplica.
                </p>

                <div className="flex flex-col">
                    {excepciones.length === 0 ? (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            No hay excepciones registradas.
                        </p>
                    ) : (
                        excepciones.map((excepcion) => (
                            <div
                                key={excepcion.id}
                                className="flex items-center justify-between gap-4 border-b border-border/60 py-2 text-sm last:border-0"
                            >
                                <div className="flex items-center gap-2">
                                    <CalendarOff className="size-4 text-muted-foreground" />
                                    <div className="flex flex-col">
                                        <span className="font-medium">
                                            {excepcion.fechaLabel} —{' '}
                                            {excepcion.motivo}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {excepcion.detalle}
                                        </span>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label="Eliminar excepción"
                                    onClick={() =>
                                        eliminarExcepcion(excepcion.id)
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))
                    )}
                </div>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setDialogoAbierto(true)}
                    >
                        <Plus />
                        Agregar excepción
                    </Button>
                </div>
            </Fieldset>

            <NuevaExcepcionDialog
                open={dialogoAbierto}
                onOpenChange={setDialogoAbierto}
            />
        </div>
    );
}

function PestanaNotificaciones({
    ajustes,
    canalCorreoDisponible,
    notificacionesEventos,
}: {
    ajustes: AjustesBiblioteca;
    canalCorreoDisponible: boolean;
    notificacionesEventos: NotificacionEvento[];
}) {
    const [eventos, setEventos] = useState<
        Record<string, { correo: boolean; push: boolean }>
    >(
        Object.fromEntries(
            notificacionesEventos.map((e) => [
                e.evento,
                { correo: e.correo, push: e.push },
            ]),
        ),
    );
    const [guardando, setGuardando] = useState(false);

    function alternarEvento(id: string, canal: 'correo' | 'push') {
        setEventos((previo) => ({
            ...previo,
            [id]: { ...previo[id], [canal]: !previo[id][canal] },
        }));
    }

    function guardarEventos() {
        setGuardando(true);

        router.patch(
            configuracionRoutes.notificaciones().url,
            { eventos },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success('Preferencias de eventos guardadas.'),
                onFinish: () => setGuardando(false),
            },
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <Fieldset>
                <FieldsetLegend>Canales de notificación</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Activa los medios por los que el sistema puede avisar al
                    personal.
                </p>

                <CampoFila etiqueta="Correo electrónico">
                    <div className="flex items-center gap-3">
                        <Badge
                            variant={
                                canalCorreoDisponible ? 'secondary' : 'outline'
                            }
                        >
                            {canalCorreoDisponible
                                ? 'Conectado'
                                : 'No configurado'}
                        </Badge>
                        <Switch
                            checked={ajustes.canalCorreoActivo}
                            onCheckedChange={(valor) =>
                                guardarAjustes(
                                    { canalCorreoActivo: valor },
                                    'Canal de correo actualizado.',
                                )
                            }
                        />
                    </div>
                </CampoFila>
                <CampoFila etiqueta="Notificaciones push">
                    <div className="flex items-center gap-3">
                        <Badge variant="outline">No configurado</Badge>
                        <Switch checked={false} disabled />
                    </div>
                </CampoFila>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Eventos y destinatarios</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Elige por qué canal se notifica cada evento del sistema.
                </p>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="py-2 pr-4 font-medium">
                                    Evento
                                </th>
                                <th className="px-4 py-2 text-center font-medium">
                                    Correo
                                </th>
                                <th className="px-4 py-2 text-center font-medium">
                                    Push
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {notificacionesEventos.map((evento) => (
                                <tr
                                    key={evento.evento}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-2 pr-4">
                                        {ETIQUETAS_EVENTO[evento.evento] ??
                                            evento.evento}
                                    </td>
                                    <td className="px-4 py-2 text-center">
                                        <Checkbox
                                            checked={
                                                eventos[evento.evento].correo
                                            }
                                            onCheckedChange={() =>
                                                alternarEvento(
                                                    evento.evento,
                                                    'correo',
                                                )
                                            }
                                        />
                                    </td>
                                    <td className="px-4 py-2 text-center">
                                        <Checkbox
                                            checked={
                                                eventos[evento.evento].push
                                            }
                                            onCheckedChange={() =>
                                                alternarEvento(
                                                    evento.evento,
                                                    'push',
                                                )
                                            }
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardando}
                        onClick={guardarEventos}
                    >
                        {guardando ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>
        </div>
    );
}

function PestanaSistema({ ajustes }: { ajustes: AjustesBiblioteca }) {
    const [regional, setRegional] = useState({
        zonaHoraria: ajustes.zonaHoraria,
        idioma: ajustes.idioma,
        formatoFecha: ajustes.formatoFecha,
    });
    const [guardandoRegional, setGuardandoRegional] = useState(false);

    const [registro, setRegistro] = useState({
        nivelRegistro: ajustes.nivelRegistro,
        modoMantenimiento: ajustes.modoMantenimiento,
    });
    const [guardandoRegistro, setGuardandoRegistro] = useState(false);

    const [limpiandoCache, setLimpiandoCache] = useState(false);
    const [registrosAbierto, setRegistrosAbierto] = useState(false);

    function limpiarCache() {
        setLimpiandoCache(true);

        router.post(
            configuracionRoutes.limpiarCache().url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => toast.success('Caché del sistema limpiada.'),
                onFinish: () => setLimpiandoCache(false),
            },
        );
    }

    const acciones = [
        {
            etiqueta: 'Limpiar caché del sistema',
            icon: RefreshCw,
            onClick: limpiarCache,
            cargando: limpiandoCache,
        },
        {
            etiqueta: 'Ver registros del sistema',
            icon: FileClock,
            onClick: () => setRegistrosAbierto(true),
        },
        {
            etiqueta: 'Verificar actualizaciones',
            icon: Wrench,
            onClick: () => proximamente('Verificar actualizaciones'),
        },
    ];

    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <Fieldset>
                <FieldsetLegend>Preferencias regionales</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Ajusta el idioma, zona horaria y formato de fecha.
                </p>

                <CampoFila etiqueta="Zona horaria">
                    <Select
                        value={regional.zonaHoraria}
                        onValueChange={(valor) =>
                            setRegional((previo) => ({
                                ...previo,
                                zonaHoraria: valor,
                            }))
                        }
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="America/Mexico_City">
                                Ciudad de México
                            </SelectItem>
                            <SelectItem value="America/Mazatlan">
                                Mazatlán / Hermosillo
                            </SelectItem>
                            <SelectItem value="America/Tijuana">
                                Tijuana
                            </SelectItem>
                            <SelectItem value="America/Cancun">
                                Cancún
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CampoFila>
                <CampoFila etiqueta="Idioma del sistema">
                    <Select
                        value={regional.idioma}
                        onValueChange={(valor) =>
                            setRegional((previo) => ({
                                ...previo,
                                idioma: valor as 'es' | 'en',
                            }))
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="es">Español</SelectItem>
                            <SelectItem value="en">English</SelectItem>
                        </SelectContent>
                    </Select>
                </CampoFila>
                <CampoFila etiqueta="Formato de fecha">
                    <Select
                        value={regional.formatoFecha}
                        onValueChange={(valor) =>
                            setRegional((previo) => ({
                                ...previo,
                                formatoFecha: valor as
                                    | 'DD/MM/AAAA'
                                    | 'MM/DD/AAAA',
                            }))
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="DD/MM/AAAA">
                                DD/MM/AAAA
                            </SelectItem>
                            <SelectItem value="MM/DD/AAAA">
                                MM/DD/AAAA
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CampoFila>

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoRegional}
                        onClick={() => {
                            setGuardandoRegional(true);
                            guardarAjustes(
                                regional,
                                'Preferencias regionales guardadas.',
                                () => setGuardandoRegional(false),
                            );
                        }}
                    >
                        {guardandoRegional ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset>
                <FieldsetLegend>Registro y mantenimiento</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Controla el nivel de detalle de los registros y el acceso al
                    sistema.
                </p>

                <CampoFila etiqueta="Nivel de registro">
                    <Select
                        value={registro.nivelRegistro}
                        onValueChange={(valor) =>
                            setRegistro((previo) => ({
                                ...previo,
                                nivelRegistro:
                                    valor as typeof registro.nivelRegistro,
                            }))
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="error">Error</SelectItem>
                            <SelectItem value="warning">Advertencia</SelectItem>
                            <SelectItem value="info">Información</SelectItem>
                            <SelectItem value="debug">Depuración</SelectItem>
                        </SelectContent>
                    </Select>
                </CampoFila>
                <CampoFila etiqueta="Modo mantenimiento">
                    <Switch
                        checked={registro.modoMantenimiento}
                        onCheckedChange={(valor) =>
                            setRegistro((previo) => ({
                                ...previo,
                                modoMantenimiento: valor,
                            }))
                        }
                    />
                </CampoFila>

                {registro.modoMantenimiento && (
                    <p className="mt-2 text-xs text-amber-600 dark:text-amber-400">
                        Este ajuste queda guardado, pero por ahora no bloquea el
                        acceso al sistema — activarlo de verdad se implementará
                        por separado, con más cuidado.
                    </p>
                )}

                <div className="mt-4 flex justify-end">
                    <Button
                        type="button"
                        size="sm"
                        disabled={guardandoRegistro}
                        onClick={() => {
                            setGuardandoRegistro(true);
                            guardarAjustes(
                                registro,
                                'Configuración del sistema guardada.',
                                () => setGuardandoRegistro(false),
                            );
                        }}
                    >
                        {guardandoRegistro ? <Spinner /> : <Save />}
                        Guardar
                    </Button>
                </div>
            </Fieldset>

            <Fieldset className="lg:col-span-2">
                <FieldsetLegend>Acciones del sistema</FieldsetLegend>
                <p className="mb-2 text-sm text-muted-foreground">
                    Herramientas de mantenimiento para el equipo técnico.
                </p>

                <div className="flex flex-col">
                    {acciones.map((accion) => (
                        <button
                            key={accion.etiqueta}
                            type="button"
                            disabled={accion.cargando}
                            onClick={accion.onClick}
                            className="flex items-center justify-between border-b border-border/60 py-3 text-left text-sm last:border-0 hover:text-primary disabled:opacity-50"
                        >
                            <span className="flex items-center gap-2">
                                {accion.cargando ? (
                                    <Spinner className="size-4" />
                                ) : (
                                    <accion.icon className="size-4 text-muted-foreground" />
                                )}
                                {accion.etiqueta}
                            </span>
                            <span className="text-muted-foreground">›</span>
                        </button>
                    ))}
                </div>
            </Fieldset>

            <VerRegistrosDialog
                open={registrosAbierto}
                onOpenChange={setRegistrosAbierto}
            />
        </div>
    );
}

export default function Configuracion({
    ajustes,
    usuarios,
    roles,
    permisos,
    horarios,
    excepciones,
    notificacionesEventos,
    canalCorreoDisponible,
}: ConfiguracionProps) {
    return (
        <>
            <Head title="Configuración" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="flex items-center justify-center gap-2 text-3xl font-extrabold tracking-tight text-primary uppercase">
                        <Settings className="size-7" />
                        Configuración
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Administra las opciones del sistema y personaliza su
                        funcionamiento.
                    </p>
                </div>

                <Tabs defaultValue="general">
                    <TabsList className="h-auto flex-wrap justify-start gap-1 bg-transparent p-0">
                        {PESTANAS.map((pestana) => (
                            <TabsTrigger
                                key={pestana.valor}
                                value={pestana.valor}
                                className="rounded-lg border border-transparent px-3 py-1.5 data-[state=active]:border-border data-[state=active]:bg-background"
                            >
                                <pestana.icon />
                                {pestana.etiqueta}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    <TabsContent value="general">
                        <PestanaGeneral ajustes={ajustes} />
                    </TabsContent>
                    <TabsContent value="usuarios">
                        <PestanaUsuarios usuarios={usuarios} roles={roles} />
                    </TabsContent>
                    <TabsContent value="permisos">
                        <PestanaPermisos permisos={permisos} roles={roles} />
                    </TabsContent>
                    <TabsContent value="horarios">
                        <PestanaHorarios
                            horarios={horarios}
                            excepciones={excepciones}
                        />
                    </TabsContent>
                    <TabsContent value="notificaciones">
                        <PestanaNotificaciones
                            ajustes={ajustes}
                            canalCorreoDisponible={canalCorreoDisponible}
                            notificacionesEventos={notificacionesEventos}
                        />
                    </TabsContent>
                    <TabsContent value="sistema">
                        <PestanaSistema ajustes={ajustes} />
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

Configuracion.layout = {
    breadcrumbs: [
        { title: 'Centro de Información', href: dashboard() },
        { title: 'Configuración', href: configuracion() },
    ],
};
