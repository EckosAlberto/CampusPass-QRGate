/**
 * Catálogo cerrado de roles institucionales, compartido por Biblioteca,
 * Eventos Académicos y Graduación (ver App\Support\RolesInstitucionales en
 * el backend). No incluye "Alumno": los alumnos no inician sesión en estos
 * paneles de personal.
 */
export const ROLES_INSTITUCIONALES = [
    'Administrador',
    'Docente',
    'Recursos humanos',
    'Servicios Escolares',
    'Personal de Servicio',
    'Invitado',
    'Tutor',
    
] as const;
