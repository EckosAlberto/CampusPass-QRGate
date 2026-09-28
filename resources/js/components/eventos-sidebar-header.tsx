import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationBell } from '@/components/notification-bell';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenu } from '@/components/user-menu';
import { logout } from '@/routes/eventos';
import { index, marcarVistas } from '@/routes/eventos/notificaciones';
import { edit } from '@/routes/eventos/seguridad';
import type {
    BreadcrumbItem as BreadcrumbItemType,
    EventosUsuario,
} from '@/types';

// Header del panel de Eventos Académicos. Igual que AppSidebarHeader
// (Biblioteca), pero con las rutas propias del guard `eventos`: una vez
// autenticado, Laravel lo promueve a guard "por defecto" de la petición
// (Auth::shouldUse en el middleware auth:eventos), así que `auth.user` sí
// llega poblado aquí.
export function EventosSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage<{ auth: { user: EventosUsuario } }>().props;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-1">
                <NotificationBell
                    indexRoute={index}
                    marcarVistasRoute={marcarVistas}
                />
                <UserMenu
                    user={auth.user}
                    settingsHref={edit()}
                    settingsLabel="Seguridad"
                    logoutHref={logout()}
                />
            </div>
        </header>
    );
}
