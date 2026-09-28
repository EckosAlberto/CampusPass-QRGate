import type {
    AccesosPorDia,
    DistribucionPorSexo,
    EntradasPorHora,
} from './dashboard';

export type ResumenEstadisticas = {
    hombres: number;
    mujeres: number;
    totalAlumnos: number;
    entradas: number;
    salidas: number;
    personasDentro: number;
};

export type AccesosPorCarrera = {
    carrera: string;
    total: number;
};

export type AccesosPorSemestre = {
    semestre: string;
    total: number;
};

export type EstadisticasFiltros = {
    fechaInicial: string;
    fechaFinal: string;
    carrera: string;
    semestre: string;
};

export type EstadisticasOpcion = {
    value: string;
    label: string;
};

export type EstadisticasBibliotecaProps = {
    filtros: EstadisticasFiltros;
    opciones: {
        carreras: EstadisticasOpcion[];
        semestres: EstadisticasOpcion[];
    };
    datos: {
        resumen: ResumenEstadisticas;
        entradasPorHora: EntradasPorHora[];
        distribucionPorSexo: DistribucionPorSexo[];
        accesosPorDia: AccesosPorDia[];
        accesosPorCarrera: AccesosPorCarrera[];
        accesosPorSemestre: AccesosPorSemestre[];
    };
};
