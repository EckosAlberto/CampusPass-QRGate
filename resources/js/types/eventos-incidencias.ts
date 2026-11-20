import type { OpcionFiltro } from './registro-del-dia';

export type TipoIncidenciaEventos =
    | 'QR_INVALIDO'
    | 'NO_REGISTRADO'
    | 'INACTIVO'
    | 'DOBLE_ESCANEO';

export type IncidenciaEventosFila = {
    id: string;
    fecha: string;
    hora: string;
    noDeControl: string | null;
    nombre: string | null;
    tipo: TipoIncidenciaEventos;
    mensaje: string | null;
    evento: string | null;
};

export type IncidenciasEventosMeta = {
    paginaActual: number;
    ultimaPagina: number;
    total: number;
    desde: number | null;
    hasta: number | null;
};

export type IncidenciasEventosFiltros = {
    buscar: string;
    tipo: string;
    fechaInicial: string;
    fechaFinal: string;
};

export type IncidenciasEventosProps = {
    incidencias: {
        data: IncidenciaEventosFila[];
        meta: IncidenciasEventosMeta;
    };
    filtros: IncidenciasEventosFiltros;
    opciones: {
        tipos: OpcionFiltro[];
    };
};
