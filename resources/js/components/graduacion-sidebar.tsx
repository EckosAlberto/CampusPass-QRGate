import { Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    CalendarCog,
    FileText,
    LayoutGrid,
    QrCode,
    Settings,
    ShieldCheck,
    Share2,
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
import { configuracion, estadisticas, panel } from '@/routes/graduacion';
import boletos from '@/routes/graduacion/boletos';
import ceremonias from '@/routes/graduacion/ceremonias';
import { index as incidencias } from '@/routes/graduacion/incidencias';
import misCeremonias from '@/routes/graduacion/mis-ceremonias';
import reportes from '@/routes/graduacion/reportes';
import { edit as editSeguridad } from '@/routes/graduacion/seguridad';
import type { NavItem } from '@/types';

const navItemClassName =
    'border border-sidebar-border bg-sidebar shadow-xs hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:text-primary-foreground data-[active=true]:shadow-xs data-[active=true]:hover:bg-primary/90 data-[active=true]:hover:text-primary-foreground';

const graduacionNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: panel(),
        icon: LayoutGrid,
    },
    {
        title: 'Gestión de ceremonia',
        href: ceremonias.index(),
        icon: CalendarCog,
    },
    {
        title: 'Ceremonias',
        href: misCeremonias.index(),
        icon: Share2,
    },
    {
        title: 'QR para Invitados',
        href: boletos.index(),
        icon: QrCode,
    },
    {
        title: 'Estadísticas',
        href: estadisticas(),
        icon: BarChart3,
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

// Sidebar propio del guard `graduacion` (equipo de Graduación), separado del
// AppSidebar de Biblioteca: usuarios, guard y logout distintos. El
// usuario/logout vive en GraduacionSidebarHeader (menú arriba a la
// derecha), no aquí.
export function GraduacionSidebar() {
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
                    items={graduacionNavItems}
                    label="Graduación"
                    itemClassName={navItemClassName}
                />
            </SidebarContent>
        </Sidebar>
    );
}
