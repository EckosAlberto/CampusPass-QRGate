import { Head, Link } from '@inertiajs/react';
import { BookOpen, GraduationCap, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import { InstitutionHeader } from '@/components/institution-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { login } from '@/routes';
import { index as bibliotecaIndex } from '@/routes/biblioteca';
import { login as eventosLogin } from '@/routes/eventos';
import { login as graduacionLogin } from '@/routes/graduacion';

// Obtiene la fecha actual en formato "día de mes de año" en español
const today = new Date().toLocaleDateString('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

type Module = {
    icon: ReactNode;
    accent: string;
    title: string;
    tagline: string;
    description: string;
    actions: ReactNode;
};

// Lista de módulos disponibles en el portal
const modules: Module[] = [
    // Módulo de biblioteca
    {
        icon: <BookOpen className="size-8" />,
        accent: 'text-emerald-600 dark:text-emerald-400',
        title: 'Biblioteca',
        tagline: 'Control de acceso a la biblioteca',
        description:
            'Registra entradas y salidas diarias de estudiantes en la biblioteca. Consulta reportes y estadísticas en tiempo real.',
        actions: (
            <>
                <Button asChild className="w-full">
                    <Link href={bibliotecaIndex()}>Registro de acceso</Link>
                </Button>
                <Button asChild variant="outline" className="w-full">
                    <Link href={login()}>Centro de información</Link>
                </Button>
            </>
        ),
    },

    // Módulo de eventos académicos
    {
        icon: <Users className="size-8" />,
        accent: 'text-blue-600 dark:text-blue-400',
        title: 'Eventos Académicos',
        tagline: 'Control de asistencia a eventos y tutorías',
        description:
            'Gestiona eventos académicos, genera URLs de acceso y controla asistencia de estudiantes.',
        actions: (
            <Button asChild className="w-full">
                <Link href={eventosLogin()}>Iniciar sesión</Link>
            </Button>
        ),
    },

    // Módulo de graduación
    {
        icon: <GraduationCap className="size-8" />,
        accent: 'text-purple-600 dark:text-purple-400',
        title: 'Graduación',
        tagline: 'Control de acceso a ceremonias de graduación',
        description:
            'Administra ceremonias, registra egresados e invitados y genera reportes de asistencias.',
        actions: (
            <Button asChild className="w-full">
                <Link href={graduacionLogin()}>Iniciar sesión</Link>
            </Button>
        ),
    },
];

// Página principal del portal institucional
export default function Portal() {
    return (
        <>
            <Head title="CampusPass/QR Gate" />

            <div className="min-h-screen bg-neutral-50 dark:bg-neutral-900">
                <InstitutionHeader
                    right={
                        <span className="text-sm font-bold text-blue-900 dark:text-blue-300">
                            {today}
                        </span>
                    }
                />

                <main className="mx-auto max-w-6xl px-6 py-10">
                    <p className="text-sm font-bold tracking-wide text-blue-900 uppercase dark:text-blue-300">
                        Portal Principal
                    </p>
                    <h1 className="mt-2 text-4xl font-bold tracking-tight">
                        Bienvenido a CampusPass/QR Gate
                    </h1>
                    <p className="mt-3 max-w-2xl text-muted-foreground">
                        Plataforma institucional para el control de accesos
                        mediante códigos QR. Selecciona el módulo al que deseas
                        acceder.
                    </p>

                    <h2 className="mt-10 text-sm font-bold tracking-wide text-blue-900 uppercase dark:text-blue-300">
                        Módulos disponibles
                    </h2>

                    <div className="mt-4 grid gap-6 md:grid-cols-3">
                        {modules.map((module) => (
                            <Card
                                key={module.title}
                                className="justify-between"
                            >
                                <CardHeader>
                                    <div className={module.accent}>
                                        {module.icon}
                                    </div>
                                    <CardTitle
                                        className={`text-xl ${module.accent}`}
                                    >
                                        {module.title}
                                    </CardTitle>
                                    <CardDescription className="font-semibold text-foreground">
                                        {module.tagline}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-1 flex-col justify-between gap-6">
                                    <p className="text-sm text-muted-foreground">
                                        {module.description}
                                    </p>
                                    <div className="flex flex-col gap-2">
                                        {module.actions}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
