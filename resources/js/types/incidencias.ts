import type { OpcionFiltro } from './registro-del-dia';

export type TipoIncidencia =
    | 'QR_INVALIDO'
    | 'NO_REGISTRADO'
    | 'INACTIVO'
    | 'DOBLE_ESCANEO';

export type IncidenciaFila = {
    id: string;
    fecha: string;
    hora: string;
    noDeControl: string | null;
    nombre: string | null;
    tipo: TipoIncidencia;
    mensaje: string | null;
    ubicacion: string | null;
};

export type IncidenciasMeta = {
    paginaActual: number;
    ultimaPagina: number;
    total: number;
    desde: number | null;
    hasta: number | null;
};

export type IncidenciasFiltros = {
    buscar: string;
    tipo: string;
    fechaInicial: string;
    fechaFinal: string;
};

export type IncidenciasProps = {
    incidencias: {
        data: IncidenciaFila[];
        meta: IncidenciasMeta;
    };
    filtros: IncidenciasFiltros;
    opciones: {
        tipos: OpcionFiltro[];
    };
};
