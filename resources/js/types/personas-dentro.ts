import type { OpcionFiltro } from './registro-del-dia';

export type PersonaDentroFila = {
    id: string;
    nombre: string;
    noDeControl: string | null;
    rfc: string | null;
    carrera: string | null;
    semestre: number | null;
    fechaEntrada: string;
    horaEntrada: string;
    estadoActual: string;
};

export type PersonasDentroMeta = {
    paginaActual: number;
    ultimaPagina: number;
    total: number;
    desde: number | null;
    hasta: number | null;
};

export type PersonasDentroFiltros = {
    buscar: string;
    carrera: string;
    semestre: string;
};

export type PersonasDentroProps = {
    personas: {
        data: PersonaDentroFila[];
        meta: PersonasDentroMeta;
    };
    filtros: PersonasDentroFiltros;
    opciones: {
        carreras: OpcionFiltro[];
        semestres: OpcionFiltro[];
    };
    totalDentro: number;
    actualizadoEn: string;
};
