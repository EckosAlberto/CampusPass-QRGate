export type Movimiento = 'ENTRADA' | 'SALIDA';

export type RegistroDelDiaFila = {
    id: string;
    fecha: string;
    hora: string;
    nombre: string;
    noDeControl: string | null;
    rfc: string | null;
    carrera: string | null;
    semestre: number | null;
    movimiento: Movimiento;
    tipo: string;
    tiempoPermanencia: string | null;
    sexo: 'H' | 'M' | null;
};

export type RegistroDelDiaMeta = {
    paginaActual: number;
    ultimaPagina: number;
    total: number;
    desde: number | null;
    hasta: number | null;
};

export type RegistroDelDiaFiltros = {
    buscar: string;
    carrera: string;
    semestre: string;
    sexo: string;
    movimiento: string;
};

export type OpcionFiltro = {
    value: string;
    label: string;
};

export type RegistroDelDiaProps = {
    registros: {
        data: RegistroDelDiaFila[];
        meta: RegistroDelDiaMeta;
    };
    filtros: RegistroDelDiaFiltros;
    opciones: {
        carreras: OpcionFiltro[];
        semestres: OpcionFiltro[];
        sexos: OpcionFiltro[];
        movimientos: OpcionFiltro[];
    };
};
