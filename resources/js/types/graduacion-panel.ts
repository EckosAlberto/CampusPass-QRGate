import type { PaginacionMeta } from './pagination';

export type CeremoniaActiva = {
    id: string;
    nombre: string | null;
    fechaInicio: string;
    fechaFin: string;
};

export type ResumenGraduacion = {
    ceremoniaActiva: CeremoniaActiva | null;
    egresadosRegistrados: number;
    invitadosRegistrados: number;
    totalAsistentes: number;
};

export type AsistenciaPorCarreraFila = {
    carrera: string;
    total: number;
};

export type EstatusAsistente = 'Egresado' | 'Invitado';

export type AccesoGraduacionFila = {
    id: string;
    fecha: string;
    hora: string;
    nombre: string;
    carrera: string | null;
    noDeControl: string | undefined;
    estatus: EstatusAsistente;
};

export type InicioGraduacionProps = {
    resumen: ResumenGraduacion;
    asistenciaPorCarrera: AsistenciaPorCarreraFila[];
    ultimosAccesos: {
        data: AccesoGraduacionFila[];
        meta: PaginacionMeta;
    };
};
