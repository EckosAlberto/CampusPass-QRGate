import { Head, Link } from '@inertiajs/react';
import { LinkIcon } from 'lucide-react';
import { InstitutionHeader } from '@/components/institution-header';
import { Button } from '@/components/ui/button';
import { login } from '@/routes/graduacion';
import type { EnlaceNoDisponibleProps } from '@/types';

export default function GraduacionEnlaceNoDisponible({
    mensaje,
}: EnlaceNoDisponibleProps) {
    return (
        <>
            <Head title="Enlace no disponible" />

            <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
                <InstitutionHeader />

                <main className="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
                    <LinkIcon className="size-16 text-muted-foreground" />
                    <h1 className="mt-4 text-2xl font-bold tracking-tight">
                        Enlace no disponible
                    </h1>
                    <p className="mt-2 max-w-sm text-muted-foreground">
                        {mensaje}
                    </p>

                    <Button asChild className="mt-6">
                        <Link href={login()}>Regresar al inicio de sesión</Link>
                    </Button>
                </main>
            </div>
        </>
    );
}
