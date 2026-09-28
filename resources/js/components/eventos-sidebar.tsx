import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarCog,
    FileText,
    LayoutGrid,
    Settings,
    Share2,
    ShieldCheck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { configuracion, panel } from '@/routes/eventos';
import eventos from '@/routes/eventos/eventos';
import { index as incidencias } from '@/routes/eventos/incidencias';
import misEventos from '@/routes/eventos/mis-eventos';
import reportes from '@/routes/eventos/reportes';
import { edit as editSeguridad } from '@/routes/eventos/seguridad';
import type { NavItem } from '@/types';

const navItemClassName =
    'border border-sidebar-border bg-sidebar shadow-xs hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground data-[active=true]:shadow-xs data-[active=true]:hover:bg-primary/90 data-[active=true]:hover:text-primary-foreground';

const eventosNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: panel(),
        icon: LayoutGrid,
    },
    {
        title: 'Gestión de eventos',
        href: eventos.index(),
        icon: CalendarCog,
    },
    {
        title: 'Mis eventos',
        href: misEventos.index(),
        icon: Share2,
    },
    {
        title: 'Reportes',
        href: reportes.index(),
        icon: FileText,
    },
    {
        title: 'Incidencias',
        href: incidencias(),
        icon: AlertTriangle,
    },
    {
        title: 'Configuración',
        href: configuracion(),
        icon: Settings,
    },
    {
        title: 'Seguridad',
        href: editSeguridad(),
        icon: ShieldCheck,
    },
];

// Sidebar propio del guard `eventos` (equipo de Eventos Académicos), separado
// del AppSidebar de Biblioteca: usuarios, guard y logout distintos. El
// usuario/logout vive en EventosSidebarHeader (menú arriba a la derecha),
// no aquí.
export function EventosSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" className="h-auto" asChild>
                            <Link href={panel()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={eventosNavItems}
                    label="Eventos académicos"
                    itemClassName={navItemClassName}
                />
            </SidebarContent>
        </Sidebar>
    );
}
