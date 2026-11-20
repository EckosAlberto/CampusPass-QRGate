import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { EventosSidebar } from '@/components/eventos-sidebar';
import { EventosSidebarHeader } from '@/components/eventos-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function EventosSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <EventosSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <EventosSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
