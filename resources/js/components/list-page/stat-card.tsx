import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const statCardColors = {
    primary: { border: 'border-l-primary', icon: 'text-primary' },
    blue: {
        border: 'border-l-blue-900 dark:border-l-blue-400',
        icon: 'text-blue-900 dark:text-blue-300',
    },
    pink: {
        border: 'border-l-pink-600 dark:border-l-pink-400',
        icon: 'text-pink-600 dark:text-pink-400',
    },
    orange: {
        border: 'border-l-orange-500 dark:border-l-orange-400',
        icon: 'text-orange-500 dark:text-orange-400',
    },
    emerald: {
        border: 'border-l-emerald-600 dark:border-l-emerald-400',
        icon: 'text-emerald-600 dark:text-emerald-400',
    },
    violet: {
        border: 'border-l-violet-500 dark:border-l-violet-400',
        icon: 'text-violet-500 dark:text-violet-400',
    },
} as const;

export type StatCardColor = keyof typeof statCardColors;

export type StatCardItem = {
    label: string;
    value: string | number;
    icon: LucideIcon;
    color?: StatCardColor;
};

/**
 * Card de total individual (ej. "Hombres 389") con borde e icono de color,
 * usada en la fila de "cards de totales" de las pantallas de listado.
 */
export function StatCard({
    label,
    value,
    icon: Icon,
    color = 'primary',
}: StatCardItem) {
    const colors = statCardColors[color];

    return (
        <Card className={cn('border-l-4 py-4', colors.border)}>
            <CardContent className="flex items-center justify-between gap-3">
                <div className="flex flex-col gap-0.5">
                    <span className="text-sm font-medium text-muted-foreground">
                        {label}
                    </span>
                    <span className="text-2xl font-semibold tabular-nums">
                        {typeof value === 'number'
                            ? value.toLocaleString('es-MX')
                            : value}
                    </span>
                </div>
                <Icon className={cn('size-6 shrink-0', colors.icon)} />
            </CardContent>
        </Card>
    );
}

/**
 * Fila de cards de totales para pantallas de listado.
 */
export function StatCardsRow({
    items,
    className,
}: {
    items: StatCardItem[];
    className?: string;
}) {
    return (
        <div
            className={cn(
                'grid gap-4 sm:grid-cols-2 lg:grid-cols-4',
                className,
            )}
        >
            {items.map((item) => (
                <StatCard key={item.label} {...item} />
            ))}
        </div>
    );
}
