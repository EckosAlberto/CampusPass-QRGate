import type { ReactNode } from 'react';

type Props = {
    right?: ReactNode;
};

// Componente de encabezado de la institución, que muestra el logotipo y un área opcional a la derecha.
export function InstitutionHeader({ right }: Props) {
    return (
        <header className="flex items-center justify-between border-b bg-white px-6 py-4 dark:bg-neutral-950">
            <div className="flex items-center gap-4">
                <img
                    src="/images/logo-itt.png"
                    alt="Instituto Tecnológico de Tepic"
                    className="h-14 w-auto"
                />
            </div>

            {right}
        </header>
    );
}
