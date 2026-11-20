import type { PaginacionMeta } from './pagination';

export type TipoBoleto = 'graduado' | 'invitado';

export type ResultadoGraduacionOk = {
    id: string;
    estado: 'ok';
    tipoBoleto: TipoBoleto;
    alumno: {
        nombre: string;
        noDeControl: string;
        carrera: string | null;
    };
    hora: string;
    invitadosAutorizados: number | null;
    invitadosRegistrados: number | null;
    lugaresDisponibles: number | null;
    mensaje: string;
};

export type ResultadoGraduacionError = {
    id: string;
    estado: 'error';
    titulo: string;
    mensaje: string;
};

export type ResultadoGraduacion =
    | ResultadoGraduacionOk
    | ResultadoGraduacionError;

export type RegistroGraduacionProps = {
    ceremonia: { nombre: string | null };
    urlActual: string;
    resultado?: ResultadoGraduacion | null;
};

export type EnlaceRemotoAccesoFila = {
    id: string;
    fecha: string;
    hora: string;
    nombre: string;
    carrera: string | null;
    noDeControl: string | undefined;
    estatus: 'Egresado' | 'Invitado';
};

export type EnlaceRemotoProps = {
    ceremonia: {
        nombre: string;
        fechaInicio: string;
        fechaFin: string;
    };
    egresadosRegistrados: number;
    invitadosRegistrados: number;
    totalAccesos: number;
    accesos: {
        data: EnlaceRemotoAccesoFila[];
        meta: Pick<PaginacionMeta, 'paginaActual' | 'ultimaPagina'>;
    };
};
