import type { PaginacionMeta } from './pagination';
import type { OpcionFiltro } from './registro-del-dia';

export type CarreraCeremonia = {
    carrera: string;
    reticula: number;
    nombre: string;
};

export type CeremoniaFila = {
    id: string;
    codigo: string;
    nombre: string;
    periodo: OpcionFiltro | null;
    fechaInicio: string;
    fechaFin: string;
    fechaInicioLabel: string;
    fechaFinLabel: string;
    tipoAcceso: string;
    estatus: boolean;
    descripcion: string | null;
    minutosAnticipadosGraduados: number;
    minutosAnticipadosInvitados: number;
    duracionHorasAcceso: number;
    invitadosPorDefecto: number;
    creadoPor: number | null;
    creadorNombre: string | null;
    carreras: CarreraCeremonia[];
    vigente: boolean;
    pantallaHabilitada: boolean;
    egresadosRegistrados: number;
    invitadosRegistrados: number;
};

export type CeremoniaFormValues = {
    nombre: string;
    periodo: string;
    fecha_inicio: string;
    fecha_fin: string;
    tipo_acceso: string;
    descripcion: string;
    minutos_anticipados_graduados: string;
    minutos_anticipados_invitados: string;
    duracion_horas_acceso: string;
    invitados_por_defecto: string;
    carreras: { carrera: string; reticula: number }[];
};

export type GestionCeremoniasProps = {
    usuarioId: number;
    ceremonias: {
        data: CeremoniaFila[];
        meta: PaginacionMeta;
    };
    catalogos: {
        periodos: OpcionFiltro[];
        carreras: OpcionFiltro[];
    };
};

export type CeremoniaShowProps = {
    ceremonia: CeremoniaFila;
    urlRegistro: string | null;
    urlEnlace: string | null;
};
