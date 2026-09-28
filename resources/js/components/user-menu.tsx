import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import type { RouteDefinition } from '@/wayfinder';

type UsuarioBasico = {
    name: string;
    email: string;
    avatar?: string;
    rol_label: string | null;
};

type Props = {
    user: UsuarioBasico | null | undefined;
    settingsHref: RouteDefinition<'get'>;
    settingsLabel: string;
    logoutHref: RouteDefinition<'post'>;
};

// Menú de usuario mostrado en el header (esquina superior derecha). Recibe
// el usuario y las rutas de configuración/logout como props para servir a
// los tres guards (Biblioteca, Eventos, Graduación), cada uno con sus
// propias rutas.
export function UserMenu({
    user,
    settingsHref,
    settingsLabel,
    logoutHref,
}: Props) {
    if (!user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="h-auto gap-2 px-2 py-1.5"
                    data-test="header-user-menu-button"
                >
                    <UserInfo user={user} />
                    <ChevronDown className="size-4 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="w-56 rounded-lg"
                align="end"
                side="bottom"
            >
                <UserMenuContent
                    user={user}
                    settingsHref={settingsHref}
                    settingsLabel={settingsLabel}
                    logoutHref={logoutHref}
                />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
