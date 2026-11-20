export type UsuarioGraduacion = {
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

export type RolGraduacionResumen = {
    clave: string;
    nombre: string;
    permisos: number;
};

export type PermisoGraduacionFila = {
    id: string;
    etiqueta: string;
    roles: Record<string, boolean>;
};

export type ConfiguracionGraduacionProps = {
    usuarios: UsuarioGraduacion[];
    roles: RolGraduacionResumen[];
    permisos: PermisoGraduacionFila[];
};
