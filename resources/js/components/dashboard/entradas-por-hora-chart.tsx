import type { EntradasPorHora } from '@/types';

export function EntradasPorHoraChart({ data }: { data: EntradasPorHora[] }) {
    const max = Math.max(1, ...data.map((d) => d.total));
    const maxIndex = data.reduce(
        (best, d, i) => (d.total > data[best].total ? i : best),
        0,
    );

    return (
        <div className="overflow-x-auto">
            <div className="flex h-48 min-w-160 items-end gap-1">
                {data.map((d, i) => (
                    <div
                        key={d.hora}
                        className="flex flex-1 flex-col items-center gap-1.5"
                    >
                        <span className="text-xs font-medium text-foreground tabular-nums">
                            {i === maxIndex && d.total > 0 ? d.total : ' '}
                        </span>
                        <div
                            className="flex w-full items-end justify-center"
                            style={{ height: '9.5rem' }}
                        >
                            <div
                                title={`${d.hora} — ${d.total} entrada${d.total === 1 ? '' : 's'}`}
                                className="w-full max-w-6 rounded-t bg-chart-1"
                                style={{
                                    height: `${Math.max((d.total / max) * 100, d.total > 0 ? 4 : 0)}%`,
                                }}
                            />
                        </div>
                        <span className="text-[10px] text-muted-foreground">
                            {d.hora}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
