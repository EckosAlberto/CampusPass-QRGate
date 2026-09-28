import type { OpcionFiltro } from './registro-del-dia';

export type AlumnoBoletoGrupal = {
    noDeControl: string;
    nombre: string;
    tieneBoleto: boolean;
    invitadosAutorizados: number | null;
};

export type ResultadoGrupal = {
    carrera: string;
    alumnos: AlumnoBoletoGrupal[];
};

export type IdentificadorInfo = {
    idUnico: string;
    curp: string | null;
    hashFoto: string | null;
    fechaRegistro: string | null;
    estatus: boolean;
};

export type BoletoInvitadoInfo = {
    id: string;
    invitadosAutorizados: number | null;
    invitadosRegistrados: number;
    generadoEn: string;
};

export type ResultadoIndividual = {
    alumno: {
        noDeControl: string;
        nombre: string;
        carrera: string | null;
    };
    identificador: IdentificadorInfo;
    boletoInvitado: BoletoInvitadoInfo;
};

export type BoletosIndexProps = {
    ceremonias: OpcionFiltro[];
    carreras: OpcionFiltro[];
    ceremoniaSeleccionada: string | null;
    resultadoGrupal: ResultadoGrupal | null;
    resultadoIndividual: ResultadoIndividual | null;
};
