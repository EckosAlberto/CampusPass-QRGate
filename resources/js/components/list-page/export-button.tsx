import { FileDown } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ExportButtonProps = Omit<ComponentProps<typeof Button>, 'variant'>;

/**
 * Boton de "exportar" para pantallas de listado, usado sobre RESULTADOS.
 */
export function ExportButton({
    children = 'Exportar',
    className,
    ...props
}: ExportButtonProps) {
    return (
        <Button
            variant="outline"
            className={cn(
                'border-emerald-600/40 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 dark:border-emerald-500/40 dark:text-emerald-400 dark:hover:bg-emerald-950',
                className,
            )}
            {...props}
        >
            <FileDown />
            {children}
        </Button>
    );
}
