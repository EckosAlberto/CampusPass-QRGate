import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type FilterSummaryItem = {
    label: string;
    value: string;
};

type FilterSummaryProps = {
    items: FilterSummaryItem[];
    className?: string;
};

export function FilterSummary({ items, className }: FilterSummaryProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {items.map((item) => (
                <Badge key={item.label} variant="secondary" className="gap-1">
                    <span className="text-muted-foreground">{item.label}:</span>
                    {item.value}
                </Badge>
            ))}
        </div>
    );
}
