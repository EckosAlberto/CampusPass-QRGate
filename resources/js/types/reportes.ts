import type { AccesosPorDia } from './dashboard';
import type { PaginacionMeta } from './eventos-panel';
import type { OpcionFiltro } from './registro-del-dia';

export type TipoReporteBiblioteca = 'resumen_general' | 'detallado';

export type ReporteBibliotecaFila = {
    id: string;
    nombre: string;
    tipo: TipoReporteBiblioteca;
    tipoLabel: string;
    periodo: string;
    fechaGeneracion: string;
    generadoPor: string | null;
    registros: number;
    creadoPor: number | null;
};

export type ReportesBibliotecaProps = {
    usuarioId: number;
    reportes: {
        data: ReporteBibliotecaFila[];
        meta: PaginacionMeta;
    };
    catalogos: {
        carreras: OpcionFiltro[];
        semestres: OpcionFiltro[];
        sexos: OpcionFiltro[];
        movimientos: OpcionFiltro[];
        tipos: OpcionFiltro[];
    };
};

export type ReporteBibliotecaIndicadores = {
    entradas: number;
    salidas: number;
    usuariosUnicos: number;
    mujeres: number;
    hombres: number;
    totalAccesos: number;
};

export type ReporteBibliotecaDetalleFila = {
    fecha: string;
    hora: string;
    nombre: string;
    noDeControl: string | null;
    rfc: string | null;
    carrera: string | null;
    semestre: number | null;
    tipoMovimiento: 'ENTRADA' | 'SALIDA';
};

export type ReporteBibliotecaDatos = {
    indicadores: ReporteBibliotecaIndicadores;
    serieDiaria: AccesosPorDia[];
    detalle: ReporteBibliotecaDetalleFila[];
};

export type ReporteBibliotecaVistaPreviaProps = {
    reporte: ReporteBibliotecaFila;
    datos: ReporteBibliotecaDatos;
};
