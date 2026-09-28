import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import type { RouteDefinition } from '@/wayfinder';

type UsuarioBasico = {
    name: string;
    email: string;
    avatar?: string;
    rol_label: string | null;
};

type Props = {
    user: UsuarioBasico;
    // Rutas del guard correspondiente — Biblioteca usa /settings/profile,
    // Eventos/Graduación no tienen esa pantalla y usan su propia página de
    // Seguridad (2FA) en su lugar.
    settingsHref: RouteDefinition<'get'>;
    settingsLabel: string;
    logoutHref: RouteDefinition<'post'>;
};

// Contenido del menú de usuario, mostrando información del usuario y opciones de configuración y cierre de sesión.
export function UserMenuContent({
    user,
    settingsHref,
    settingsLabel,
    logoutHref,
}: Props) {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={settingsHref}
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        {settingsLabel}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logoutHref}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Cerrar sesión
                </Link>
            </DropdownMenuItem>
        </>
    );
}
