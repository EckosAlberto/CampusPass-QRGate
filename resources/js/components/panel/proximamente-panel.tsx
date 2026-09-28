import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

export function ProximamentePanel({
    titulo,
    icon: Icon,
}: {
    titulo: string;
    icon: LucideIcon;
}) {
    return (
        <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    {titulo}
                </h1>
                <p className="text-sm text-muted-foreground">
                    Centro de Información — Biblioteca
                </p>
            </div>

            <Card className="flex flex-1 items-center justify-center">
                <CardContent className="flex flex-col items-center gap-3 text-center">
                    <Icon className="size-10 text-muted-foreground" />
                    <p className="text-sm text-muted-foreground">
                        E
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
