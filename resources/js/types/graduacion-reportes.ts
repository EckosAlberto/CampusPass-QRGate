import type { PaginacionMeta } from './pagination';
import type { OpcionFiltro } from './registro-del-dia';

export type TipoReporteGraduacion = 'resumen_general' | 'detallado';

export type ReporteGraduacionFila = {
    id: string;
    nombre: string;
    tipo: TipoReporteGraduacion;
    tipoLabel: string;
    periodo: string;
    fechaGeneracion: string;
    generadoPor: string | null;
    registros: number;
    creadoPor: number | null;
};

export type ReportesGraduacionProps = {
    usuarioId: number;
    reportes: {
        data: ReporteGraduacionFila[];
        meta: PaginacionMeta;
    };
    catalogos: {
        ceremonias: OpcionFiltro[];
        carreras: OpcionFiltro[];
        tipos: OpcionFiltro[];
    };
};

export type ReporteGraduacionIndicadores = {
    egresadosRegistrados: number;
    invitadosRegistrados: number;
    totalAsistentes: number;
};

export type ReporteGraduacionPorCarrera = {
    carrera: string;
    egresados: number;
    invitados: number;
};

export type ReporteGraduacionPorCeremonia = {
    ceremonia: string;
    total: number;
};

export type ReporteGraduacionDetalleFila = {
    fecha: string;
    hora: string;
    nombre: string;
    noDeControl: string | undefined;
    carrera: string | null | undefined;
    ceremonia: string | null | undefined;
    tipo: 'Egresado' | 'Invitado';
};

export type ReporteGraduacionDatos = {
    indicadores: ReporteGraduacionIndicadores;
    porCarrera: ReporteGraduacionPorCarrera[];
    porCeremonia: ReporteGraduacionPorCeremonia[];
    detalle?: ReporteGraduacionDetalleFila[];
};

export type ReporteGraduacionVistaPreviaProps = {
    reporte: ReporteGraduacionFila;
    datos: ReporteGraduacionDatos;
};
