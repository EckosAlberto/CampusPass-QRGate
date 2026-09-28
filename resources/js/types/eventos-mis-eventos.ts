import type { PaginacionMeta } from './eventos-panel';

export type EventoMisEventos = {
    id: string;
    nombre: string | null;
    fecha: string | null;
    fechaFin: string | null;
    horaInicio: string | null;
    horaFin: string | null;
    vigente: boolean;
    entradaHabilitada: boolean;
    salidaHabilitada: boolean;
    urlEntrada: string | null;
    urlSalida: string | null;
};

export type MisEventosProps = {
    eventos: {
        data: EventoMisEventos[];
        meta: PaginacionMeta;
    };
};

export type ResultadoOk = {
    id: string;
    estado: 'ok';
    tipoMovimiento: 'ENTRADA' | 'SALIDA';
    alumno: {
        nombre: string;
        noDeControl: string;
        carrera: string | null;
    };
    hora: string;
    mensaje: string;
};

export type ResultadoError = {
    id: string;
    estado: 'error';
    titulo: string;
    mensaje: string;
};

export type Resultado = ResultadoOk | ResultadoError;

export type RegistroEventoProps = {
    evento: { nombre: string | null };
    tipo: 'ENTRADA' | 'SALIDA';
    urlActual: string;
    resultado?: Resultado | null;
};

export type EnlaceNoDisponibleProps = {
    mensaje: string;
};
