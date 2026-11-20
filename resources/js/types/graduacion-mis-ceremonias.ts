export type CeremoniaMisCeremonias = {
    id: string;
    codigo: string;
    nombre: string;
    fechaInicio: string;
    fechaFin: string;
    estatus: boolean;
    vigente: boolean;
    urlRegistro: string | null;
    urlEnlace: string | null;
};

export type MisCeremoniasProps = {
    ceremonias: CeremoniaMisCeremonias[];
};
