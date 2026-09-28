import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

// Componente para mostrar mensajes de error relacionados con inputs, como validaciones de formularios.
export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {message}
        </p>
    ) : null;
}
