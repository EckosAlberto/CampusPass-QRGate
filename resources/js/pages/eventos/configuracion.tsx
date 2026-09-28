import { Head, router } from '@inertiajs/react';
import { Pencil, Save, ShieldCheck, Trash2, UserPlus } from 'lucide-react';
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
import { ROLES_INSTITUCIONALES } from '@/lib/roles-institucionales';
import { configuracion, panel } from '@/routes/eventos';
import configuracionRoutes from '@/routes/eventos/configuracion';
import usuariosRoutes from '@/routes/eventos/configuracion/usuarios';
import type {
    ConfiguracionEventosProps,
    PermisoEventosFila,
    RolEventosResumen,
    UsuarioEventos,
} from '@/types';

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
                rol,
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
                        {errores.rol && (
                            <p className="text-sm text-destructive">
                                {errores.rol}
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
    usuario: UsuarioEventos | null;
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
    usuario: UsuarioEventos;
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
                rol,
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
                    {errores.rol && (
                        <p className="text-sm text-destructive">
                            {errores.rol}
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

function RolSelectCelda({ usuario }: { usuario: UsuarioEventos }) {
    function cambiar(nuevoRol: string) {
        if (nuevoRol === usuario.rol) {
            return;
        }

        router.patch(
            usuariosRoutes.update(usuario.id).url,
            { rol: nuevoRol },
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

function PermisosMatriz({
    permisos,
    roles,
}: {
    permisos: PermisoEventosFila[];
    roles: RolEventosResumen[];
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
                    Todavía no hay roles creados. Crea un usuario con un rol
                    arriba para poder asignarle permisos aquí.
                </p>
            </Fieldset>
        );
    }

    return (
        <Fieldset>
            <FieldsetLegend>Matriz de permisos por rol</FieldsetLegend>
            <p className="mb-2 text-sm text-muted-foreground">
                Define qué puede hacer cada rol dentro del módulo de Eventos
                Académicos.
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

export default function ConfiguracionEventos({
    usuarios,
    roles,
    permisos,
}: ConfiguracionEventosProps) {
    const [dialogoAbierto, setDialogoAbierto] = useState(false);
    const [usuarioEditando, setUsuarioEditando] =
        useState<UsuarioEventos | null>(null);

    function alternarActivo(usuario: UsuarioEventos) {
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

    function eliminar(usuario: UsuarioEventos) {
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
        <>
            <Head title="Configuración" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold tracking-tight text-primary uppercase">
                        Configuración
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Usuarios, roles y permisos de Eventos Académicos.
                    </p>
                </div>

                <Fieldset>
                    <FieldsetLegend>Usuarios del sistema</FieldsetLegend>
                    <div className="mb-2 flex items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">
                            Administra las cuentas del personal con acceso al
                            panel.
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
                                    <th className="px-3 py-2 font-medium">
                                        Rol
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
                                                            Tel:{' '}
                                                            {usuario.telefono}
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
                                                        setUsuarioEditando(
                                                            usuario,
                                                        )
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

                {roles.length > 0 && (
                    <Fieldset>
                        <FieldsetLegend>Roles disponibles</FieldsetLegend>

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
                                    <Badge
                                        variant="secondary"
                                        className="w-fit"
                                    >
                                        {rol.permisos} permisos
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </Fieldset>
                )}

                <PermisosMatriz permisos={permisos} roles={roles} />
            </div>

            <NuevoUsuarioDialog
                open={dialogoAbierto}
                onOpenChange={setDialogoAbierto}
            />

            <EditarUsuarioDialog
                usuario={usuarioEditando}
                onOpenChange={(abierto) => !abierto && setUsuarioEditando(null)}
            />
        </>
    );
}

ConfiguracionEventos.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: panel() },
        { title: 'Configuración', href: configuracion() },
    ],
};
