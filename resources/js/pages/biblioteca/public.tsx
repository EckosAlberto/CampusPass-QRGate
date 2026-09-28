import { Head, Link } from '@inertiajs/react';
import { ScanLine } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { registro } from '@/routes/biblioteca';

// Página pública del sistema bibliotecario, accesible sin autenticación.
export default function BibliotecaPublic() {
    return (
        <>
            <Head title="Biblioteca" />

            <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
                <main className="flex flex-1 flex-col items-center justify-start px-6 py-12 text-center">
                    <img
                        src="/images/logo-itt.png"
                        alt="Instituto Tecnológico de Tepic"
                        className="h-30 w-auto mb-14"
                    />
                    
                    <p className="mt-6 text-6xl font-bold tracking-tight text-primary dark:text-blue-300 mb-12">
                        CENTRO DE INFORMACIÓN
                    </p>
                    
                    <h1 className="mt-2 text-6xl font-bold text-emerald-600 dark:text-emerald-400 mb-12">
                        ¡Bienvenido!
                    </h1>
                    <p className="mt-3 text-muted-foreground">
                        Sistema de control de acceso
                    </p>

                    <Button
                        asChild
                        size="lg"
                        className="mt-10 w-full max-w-sm gap-2 bg-emerald-600 text-white hover:bg-emerald-700"
                    >
                        <Link href={registro()}>
                            <ScanLine className="size-5" />
                            REGISTRAR ACCESOS
                        </Link>
                    </Button>
                </main>
            </div>
        </>
    );
}
