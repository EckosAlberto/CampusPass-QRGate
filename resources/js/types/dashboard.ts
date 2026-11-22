export type ResumenDelDia = {
    entradasHoy: number;
    salidasHoy: number;
    usuariosUnicos: number;
    personasDentro: number;
};

export type EntradasPorHora = {
    hora: string;
    total: number;
};

export type DistribucionPorSexo = {
    sexo: string;
    total: number;
};

export type AccesoReciente = {
    hora: string;
    nombre: string;
    carrera: string | null;
    tipoMovimiento: 'ENTRADA' | 'SALIDA';
};

export type AccesosPorDia = {
    fecha: string;
    etiqueta: string;
    total: number;
};

export type DashboardProps = {
    resumen: ResumenDelDia;
    aforo: {
        actual: number;
        capacidad: number;
        porcentaje: number | null;
        estado: 'sin_limite' | 'lleno' | 'cerca' | 'disponible';
    };
    entradasPorHora: EntradasPorHora[];
    distribucionPorSexo: DistribucionPorSexo[];
    accesosRecientes: AccesoReciente[];
    accesosPorDia: AccesosPorDia[];
};
