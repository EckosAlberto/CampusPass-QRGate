import type { OpcionFiltro } from './registro-del-dia';

export type ComparativaCeremonia = {
    id: string;
    nombre: string;
    fecha: string;
    egresados: number;
    invitados: number;
    asistentes: number;
};

export type TendenciaAccesosHora = {
    hora: string;
    total: number;
};

export type EstadisticasProps = {
    ceremonias: OpcionFiltro[];
    ceremoniaSeleccionada: string | null;
    comparativa: ComparativaCeremonia[];
    tendencia: TendenciaAccesosHora[];
};
