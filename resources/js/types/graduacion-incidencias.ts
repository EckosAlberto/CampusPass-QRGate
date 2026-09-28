import type { OpcionFiltro } from './registro-del-dia';

export type TipoIncidenciaGraduacion =
    | 'QR_INVALIDO'
    | 'NO_REGISTRADO'
    | 'INACTIVO'
    | 'CEREMONIA_NO_VIGENTE'
    | 'SIN_INVITACIONES'
    | 'YA_UTILIZADO'
    | 'DOBLE_ESCANEO';

export type IncidenciaGraduacionFila = {
    id: string;
    fecha: string;
    hora: string;
    codigo: string | null;
    tipo: TipoIncidenciaGraduacion;
    mensaje: string | null;
    ceremonia: string | null;
};

export type IncidenciasGraduacionMeta = {
    paginaActual: number;
    ultimaPagina: number;
    total: number;
    desde: number | null;
    hasta: number | null;
};

export type IncidenciasGraduacionFiltros = {
    buscar: string;
    tipo: string;
    fechaInicial: string;
    fechaFinal: string;
};

export type IncidenciasGraduacionProps = {
    incidencias: {
        data: IncidenciaGraduacionFila[];
        meta: IncidenciasGraduacionMeta;
    };
    filtros: IncidenciasGraduacionFiltros;
    opciones: {
        tipos: OpcionFiltro[];
    };
};
