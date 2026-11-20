import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { GraduacionSidebar } from '@/components/graduacion-sidebar';
import { GraduacionSidebarHeader } from '@/components/graduacion-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function GraduacionSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <GraduacionSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <GraduacionSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
