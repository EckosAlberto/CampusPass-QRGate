import { Plus } from 'lucide-react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type CreateButtonProps = Omit<ComponentProps<typeof Button>, 'variant'>;

/**
 * Botón de "crear" para pantallas de listado. Siempre naranja, según el
 * estándar de diseño de pantallas de tabla/listado.
 */
export function CreateButton({
    children,
    className,
    ...props
}: CreateButtonProps) {
    return (
        <Button
            className={cn(
                'bg-orange-500 text-white shadow-xs hover:bg-orange-600 focus-visible:ring-orange-500/30 dark:bg-orange-600 dark:hover:bg-orange-500',
                className,
            )}
            {...props}
        >
            <Plus />
            {children}
        </Button>
    );
}
