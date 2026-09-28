import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    ClipboardList,
    FileText,
    LayoutGrid,
    Settings,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavLogout } from '@/components/nav-logout';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    configuracion,
    dashboard,
    estadisticas,
    incidencias,
    personasDentro,
    registroDelDia,
    reportes,
} from '@/routes';
import type { NavItem } from '@/types';

const navItemClassName =
    'border border-sidebar-border bg-sidebar shadow-xs hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground data-[active=true]:shadow-xs data-[active=true]:hover:bg-primary/90 data-[active=true]:hover:text-primary-foreground';

const mainNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const bibliotecaNavItems: NavItem[] = [
    {
        title: 'Registro del día',
        href: registroDelDia(),
        icon: ClipboardList,
    },
    {
        title: 'Personas dentro',
        href: personasDentro(),
        icon: UsersRound,
    },
    {
        title: 'Reportes',
        href: reportes(),
        icon: FileText,
    },
    {
        title: 'Estadísticas',
        href: estadisticas(),
        icon: BarChart3,
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
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" className="h-auto" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={mainNavItems}
                    itemClassName={navItemClassName}
                />
                <NavMain
                    items={bibliotecaNavItems}
                    label="Biblioteca"
                    itemClassName={navItemClassName}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavLogout />
            </SidebarFooter>
        </Sidebar>
    );
}
