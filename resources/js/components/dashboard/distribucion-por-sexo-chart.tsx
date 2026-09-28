import type { DistribucionPorSexo } from '@/types';

const ORDEN_CATEGORIAS = ['Hombres', 'Mujeres', 'No especificado'];

const COLOR_POR_CATEGORIA: Record<string, string> = {
    Hombres: '#2563eb',
    Mujeres: '#db2777',
    'No especificado': '#9ca3af',
};

const RADIO = 60;
const GROSOR = 22;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;
const HUECO_PX = 3;

export function DistribucionPorSexoChart({
    data,
}: {
    data: DistribucionPorSexo[];
}) {
    const total = data.reduce((sum, d) => sum + d.total, 0);
    const segmentos = [...data].sort(
        (a, b) =>
            ORDEN_CATEGORIAS.indexOf(a.sexo) - ORDEN_CATEGORIAS.indexOf(b.sexo),
    );

    if (total === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                Sin accesos registrados hoy.
            </div>
        );
    }

    const arcos = segmentos.reduce<
        Array<DistribucionPorSexo & { longitud: number; offset: number }>
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
                aria-label="Distribución por sexo"
            >
                {arcos.map((d) => (
                    <circle
                        key={d.sexo}
                        cx="70"
                        cy="70"
                        r={RADIO}
                        fill="none"
                        stroke={
                            COLOR_POR_CATEGORIA[d.sexo] ??
                            'var(--color-chart-4)'
                        }
                        strokeWidth={GROSOR}
                        strokeDasharray={`${Math.max(d.longitud - HUECO_PX, 0)} ${CIRCUNFERENCIA}`}
                        strokeDashoffset={d.offset}
                        aria-label={`${d.sexo}: ${d.total} (${Math.round((d.total / total) * 100)}%)`}
                    />
                ))}
            </svg>

            <ul className="flex flex-col gap-2">
                {segmentos.map((d) => (
                    <li
                        key={d.sexo}
                        className="flex items-center gap-2 text-sm"
                    >
                        <span
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor:
                                    COLOR_POR_CATEGORIA[d.sexo] ??
                                    'var(--color-chart-4)',
                            }}
                        />
                        <span className="text-foreground">{d.sexo}</span>
                        <span className="text-muted-foreground tabular-nums">
                            {Math.round((d.total / total) * 100)}%
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
