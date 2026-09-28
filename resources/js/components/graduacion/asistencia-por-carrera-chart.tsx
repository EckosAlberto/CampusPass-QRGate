import type { AsistenciaPorCarreraFila } from '@/types';

const PALETA = [
    'var(--color-chart-1)',
    'var(--color-chart-2)',
    'var(--color-chart-3)',
    'var(--color-chart-4)',
    'var(--color-chart-5)',
];

const RADIO = 60;
const GROSOR = 22;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;
const HUECO_PX = 3;

export function AsistenciaPorCarreraChart({
    data,
}: {
    data: AsistenciaPorCarreraFila[];
}) {
    const total = data.reduce((sum, d) => sum + d.total, 0);

    if (total === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                Sin asistencia registrada todavía.
            </div>
        );
    }

    const segmentos = [...data].sort((a, b) => b.total - a.total);

    const arcos = segmentos.reduce<
        Array<AsistenciaPorCarreraFila & { longitud: number; offset: number }>
    >((acc, d) => {
        const anterior = acc.at(-1);
        const acumulado = anterior ? -anterior.offset + anterior.longitud : 0;
        const longitud = (d.total / total) * CIRCUNFERENCIA;

        acc.push({ ...d, longitud, offset: -acumulado });

        return acc;
    }, []);

    return (
        <div className="flex min-h-48 flex-col items-center justify-center gap-4 py-2 sm:flex-row sm:gap-6">
            <svg
                viewBox="0 0 140 140"
                className="h-40 w-40 -rotate-90"
                role="img"
                aria-label="Asistencia por carrera"
            >
                {arcos.map((d, i) => (
                    <circle
                        key={d.carrera}
                        cx="70"
                        cy="70"
                        r={RADIO}
                        fill="none"
                        stroke={PALETA[i % PALETA.length]}
                        strokeWidth={GROSOR}
                        strokeDasharray={`${Math.max(d.longitud - HUECO_PX, 0)} ${CIRCUNFERENCIA}`}
                        strokeDashoffset={d.offset}
                        aria-label={`${d.carrera}: ${d.total} (${Math.round((d.total / total) * 100)}%)`}
                    />
                ))}
            </svg>

            <ul className="flex flex-col gap-2">
                {segmentos.map((d, i) => (
                    <li
                        key={d.carrera}
                        className="flex items-center gap-2 text-sm"
                    >
                        <span
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor: PALETA[i % PALETA.length],
                            }}
                        />
                        <span className="text-foreground">{d.carrera}</span>
                        <span className="text-muted-foreground tabular-nums">
                            {Math.round((d.total / total) * 100)}%
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
