import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import EventosSidebarLayout from '@/layouts/eventos/eventos-sidebar-layout';
import GraduacionSidebarLayout from '@/layouts/graduacion/graduacion-sidebar-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'portal':
            case name === 'biblioteca/public':
            case name === 'biblioteca/registro':
            case name === 'panel/reporte-vista-previa':
            case name === 'eventos/registro':
            case name === 'eventos/enlace-no-disponible':
            case name === 'eventos/reporte-vista-previa':
            case name === 'graduacion/registro':
            case name === 'graduacion/enlace-no-disponible':
            case name === 'graduacion/enlace-remoto':
            case name === 'graduacion/reporte-vista-previa':
                return null;
            case name.startsWith('auth/'):
            case name === 'eventos/login':
            case name === 'eventos/two-factor-challenge':
            case name === 'eventos/confirmar-password':
            case name === 'graduacion/login':
            case name === 'graduacion/two-factor-challenge':
            case name === 'graduacion/confirmar-password':
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('eventos/'):
                return EventosSidebarLayout;
            case name.startsWith('graduacion/'):
                return GraduacionSidebarLayout;
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});


initializeTheme();
