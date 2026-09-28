export type AjustesBiblioteca = {
    nombreSistema: string;
    institucion: string;
    nombreBiblioteca: string;
    capacidadMaxima: number;
    horarioApertura: string;
    horarioCierre: string;
    qrActivo: boolean;
    qrValidezMinutos: number;
    qrPermitirReingreso: boolean;
    qrNotificarCorreo: boolean;
    notifIncidencias: boolean;
    notifReportes: boolean;
    notifResumenDiario: boolean;
    notifCorreo: boolean;
    sesionInactividadMinutos: number;
    sesionCerrarAuto: boolean;
    sesionMantenerActiva: boolean;
    zonaHoraria: string;
    idioma: 'es' | 'en';
    formatoFecha: 'DD/MM/AAAA' | 'MM/DD/AAAA';
    nivelRegistro: 'error' | 'warning' | 'info' | 'debug';
    modoMantenimiento: boolean;
    canalCorreoActivo: boolean;
    canalPushActivo: boolean;
    version: string;
    baseDeDatosConectada: boolean;
    ultimaActualizacion: string | null;
};

export type UsuarioBiblioteca = {
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

export type RolResumen = {
    clave: string;
    nombre: string;
    permisos: number;
};

export type PermisoFila = {
    id: string;
    etiqueta: string;
    roles: Record<string, boolean>;
};

export type HorarioDia = {
    dia: string;
    abierto: boolean;
    apertura: string;
    cierre: string;
};

export type ExcepcionHorario = {
    id: string;
    fecha: string;
    fechaLabel: string;
    motivo: string;
    detalle: string;
};

export type NotificacionEvento = {
    evento: string;
    correo: boolean;
    push: boolean;
};

export type ConfiguracionProps = {
    ajustes: AjustesBiblioteca;
    usuarios: UsuarioBiblioteca[];
    roles: RolResumen[];
    permisos: PermisoFila[];
    horarios: HorarioDia[];
    excepciones: ExcepcionHorario[];
    notificacionesEventos: NotificacionEvento[];
    canalCorreoDisponible: boolean;
};
