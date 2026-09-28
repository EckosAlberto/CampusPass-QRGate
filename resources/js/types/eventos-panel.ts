import type { PaginacionMeta } from './pagination';
import type { Movimiento, OpcionFiltro } from './registro-del-dia';

export type { PaginacionMeta } from './pagination';

export type EventoActivo = {
    id: string;
    nombre: string | null;
    tipoEvento: string | null;
    tutor: string | null;
    horaInicio: string | null;
    horaFin: string | null;
};

export type ResumenGeneral = {
    eventoActivo: EventoActivo | null;
    asistentesHoy: number;
    entradasHoy: number;
    salidasHoy: number;
    pendientesSalida: number;
};

export type AsistenciaFila = {
    id: string;
    fecha: string;
    hora: string;
    nombre: string;
    noDeControl: string;
    evento: string | null;
    tipoMovimiento: Movimiento;
};

export type InicioProps = {
    resumen: ResumenGeneral;
    asistencia: {
        data: AsistenciaFila[];
        meta: PaginacionMeta;
    };
};

export type EventoFila = {
    id: string;
    nombre: string | null;
    fecha: string | null;
    fechaFin: string | null;
    horaInicio: string | null;
    horaFin: string | null;
    horas: number | null;
    tipoEvento: OpcionFiltro | null;
    periodoTutorias: OpcionFiltro | null;
    tutor: OpcionFiltro | null;
    estatus: boolean;
    creadoPor: number | null;
};

export type EventoFormValues = {
    nombre: string;
    fecha: string;
    fecha_fin: string;
    hora_inicio: string;
    hora_fin: string;
    horas: string;
    fk_id_tipo_evento: string;
    fk_id_periodo_tutorias: string;
    fk_id_tutor: string;
};

export type GestionEventosProps = {
    usuarioId: number;
    eventos: {
        data: EventoFila[];
        meta: PaginacionMeta;
    };
    catalogos: {
        tiposEvento: OpcionFiltro[];
        periodosTutorias: OpcionFiltro[];
        tutores: OpcionFiltro[];
    };
};
