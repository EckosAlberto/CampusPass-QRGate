import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

// Página de configuración de apariencia del usuario
export default function Appearance() {
    return (
        <>
            <Head title="Apariencia" />

            <h1 className="sr-only">Apariencia</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Apariencia"
                    description="Actualiza la apariencia de tu cuenta"
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

// Define la configuración de diseño para la página de apariencia
Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Apariencia',
            href: editAppearance(),
        },
    ],
};
