export type UsuarioEventos = {
    id: number;
    nombre: string;
    nombrePila: string;
    apellidoPaterno: string | null;
    apellidoMaterno: string | null;
    correo: string;
    rol: string | null;
    activo: boolean;
    fechaNacimiento: string | null;
    curp: string | null;
    rfc: string | null;
    telefono: string | null;
};

export type RolEventosResumen = {
    clave: string;
    nombre: string;
    permisos: number;
};

export type PermisoEventosFila = {
    id: string;
    etiqueta: string;
    roles: Record<string, boolean>;
};

export type ConfiguracionEventosProps = {
    usuarios: UsuarioEventos[];
    roles: RolEventosResumen[];
    permisos: PermisoEventosFila[];
};
