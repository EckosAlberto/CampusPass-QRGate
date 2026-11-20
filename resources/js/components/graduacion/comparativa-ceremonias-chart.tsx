import type { ComparativaCeremonia } from '@/types';

const SERIES = [
    { key: 'egresados', label: 'Egresados', className: 'bg-chart-1' },
    { key: 'invitados', label: 'Invitados', className: 'bg-chart-2' },
    { key: 'asistentes', label: 'Asistentes', className: 'bg-chart-3' },
] as const;

export function ComparativaCeremoniasChart({
    data,
}: {
    data: ComparativaCeremonia[];
}) {
    if (data.length === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                Todavía no hay ceremonias para comparar.
            </div>
        );
    }

    const max = Math.max(
        1,
        ...data.flatMap((d) => [d.egresados, d.invitados, d.asistentes]),
    );

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                {SERIES.map((serie) => (
                    <span key={serie.key} className="flex items-center gap-1.5">
                        <span
                            className={`size-2.5 rounded-full ${serie.className}`}
                        />
                        {serie.label}
                    </span>
                ))}
            </div>

            <div className="flex flex-col gap-3 overflow-x-auto">
                {data.map((ceremonia) => (
                    <div key={ceremonia.id} className="flex flex-col gap-1">
                        <span className="text-sm font-medium">
                            {ceremonia.nombre}{' '}
                            <span className="font-normal text-muted-foreground">
                                · {ceremonia.fecha}
                            </span>
                        </span>
                        <div className="flex items-center gap-3">
                            {SERIES.map((serie) => (
                                <div
                                    key={serie.key}
                                    className="flex flex-1 items-center gap-2"
                                >
                                    <div className="h-3 flex-1 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className={`h-full rounded-full ${serie.className}`}
                                            style={{
                                                width: `${Math.max((ceremonia[serie.key] / max) * 100, ceremonia[serie.key] > 0 ? 4 : 0)}%`,
                                            }}
                                        />
                                    </div>
                                    <span className="w-8 shrink-0 text-right text-xs text-muted-foreground tabular-nums">
                                        {ceremonia[serie.key]}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
