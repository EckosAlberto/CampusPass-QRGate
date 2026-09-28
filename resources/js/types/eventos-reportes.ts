import type { AccesosPorDia } from './dashboard';
import type { PaginacionMeta } from './eventos-panel';
import type { OpcionFiltro } from './registro-del-dia';

export type TipoReporte = 'resumen_general' | 'detallado';

export type ReporteFila = {
    id: string;
    nombre: string;
    tipo: TipoReporte;
    tipoLabel: string;
    periodo: string;
    fechaGeneracion: string;
    generadoPor: string | null;
    registros: number;
    creadoPor: number | null;
};

export type ReportesProps = {
    usuarioId: number;
    reportes: {
        data: ReporteFila[];
        meta: PaginacionMeta;
    };
    catalogos: {
        eventos: OpcionFiltro[];
        carreras: OpcionFiltro[];
        tipos: OpcionFiltro[];
    };
};

export type ReporteIndicadores = {
    asistentesRegistrados: number;
    entradas: number;
    salidas: number;
};

export type ReportePorCarrera = {
    carrera: string;
    alumnos: number;
    entradas: number;
    salidas: number;
};

export type ReporteDetalleFila = {
    fecha: string;
    hora: string;
    nombre: string;
    noDeControl: string;
    carrera: string | null;
    evento: string | null;
    tipoMovimiento: 'ENTRADA' | 'SALIDA';
};

export type ReporteDatos = {
    indicadores: ReporteIndicadores;
    porCarrera: ReportePorCarrera[];
    serieDiaria: AccesosPorDia[];
    detalle?: ReporteDetalleFila[];
};

export type ReporteVistaPreviaProps = {
    reporte: ReporteFila;
    datos: ReporteDatos;
};
