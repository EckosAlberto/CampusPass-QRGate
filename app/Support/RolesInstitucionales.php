<?php

namespace App\Support;

final class RolesInstitucionales
{
    public const ADMINISTRADOR = 'ADM';

    public const DOCENTE = 'DOC';

    public const RECURSOS_HUMANOS = 'DRH';

    public const SERVICIOS_ESCOLARES = 'ESC';

    public const PERSONAL_DE_SERVICIO = 'SRV';

    public const INVITADO = 'INV';

    /**
     * @var array<string, string>
     */
    public const CATALOGO = [
        self::ADMINISTRADOR => 'Administrador',
        self::DOCENTE => 'Docente',
        self::RECURSOS_HUMANOS => 'Recursos humanos',
        self::SERVICIOS_ESCOLARES => 'Servicios Escolares',
        self::PERSONAL_DE_SERVICIO => 'Personal de Servicio',
        self::INVITADO => 'Invitado',
    ];

    /**
     * @return list<string>
     */
    public static function nombres(): array
    {
        return array_values(self::CATALOGO);
    }

    public static function codigoPorNombre(string $nombre): ?string
    {
        $codigo = array_search($nombre, self::CATALOGO, true);

        return $codigo === false ? null : $codigo;
    }
}
