import type { AccesosPorDia } from '@/types';

const WIDTH = 600;
const HEIGHT = 200;
const PADDING_X = 24;
const PADDING_TOP = 28;
const PADDING_BOTTOM = 28;

function escalaY(valor: number, max: number) {
    const utilizable = HEIGHT - PADDING_TOP - PADDING_BOTTOM;

    return (
        HEIGHT - PADDING_BOTTOM - (max === 0 ? 0 : (valor / max) * utilizable)
    );
}

export function AccesosPorDiaChart({ data }: { data: AccesosPorDia[] }) {
    if (data.length === 0) {
        return (
            <p className="py-6 text-center text-sm text-muted-foreground">
                Sin datos en el periodo seleccionado.
            </p>
        );
    }

    const maxReal = Math.max(0, ...data.map((d) => d.total));
    const max = Math.max(1, maxReal);
    const paso =
        data.length > 1 ? (WIDTH - PADDING_X * 2) / (data.length - 1) : 0;

    const puntos = data.map((d, i) => ({
        ...d,
        x: PADDING_X + i * paso,
        y: escalaY(d.total, max),
    }));

    const linea = puntos
        .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`)
        .join(' ');

    const area = `${linea} L ${puntos[puntos.length - 1].x} ${HEIGHT - PADDING_BOTTOM} L ${puntos[0].x} ${HEIGHT - PADDING_BOTTOM} Z`;

    const ultimo = puntos[puntos.length - 1];
    const ticks =
        maxReal === 0
            ? [{ fraccion: 1, valor: 0 }]
            : [
                  { fraccion: 0, valor: maxReal },
                  { fraccion: 1, valor: 0 },
              ];

    return (
        <svg
            viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
            className="h-48 w-full"
            role="img"
            aria-label="Accesos por día, últimos 7 días"
        >
            {ticks.map(({ fraccion, valor }) => {
                const y =
                    PADDING_TOP +
                    fraccion * (HEIGHT - PADDING_TOP - PADDING_BOTTOM);

                return (
                    <g key={fraccion}>
                        <line
                            x1={PADDING_X}
                            x2={WIDTH - PADDING_X}
                            y1={y}
                            y2={y}
                            className="stroke-border"
                            strokeWidth={1}
                        />
                        <text
                            x={0}
                            y={y + 3}
                            className="fill-muted-foreground text-[10px] tabular-nums"
                        >
                            {valor}
                        </text>
                    </g>
                );
            })}

            <path d={area} className="fill-chart-1" fillOpacity={0.1} />
            <path
                d={linea}
                fill="none"
                className="stroke-chart-1"
                strokeWidth={2}
                strokeLinecap="round"
                strokeLinejoin="round"
            />

            {puntos.map((p) => (
                <g key={p.fecha}>
                    <circle
                        cx={p.x}
                        cy={p.y}
                        r={5}
                        className="fill-chart-1 stroke-card"
                        strokeWidth={2}
                        aria-label={`${p.etiqueta}: ${p.total} acceso${p.total === 1 ? '' : 's'}`}
                    />
                    <text
                        x={p.x}
                        y={HEIGHT - 6}
                        textAnchor="middle"
                        className="fill-muted-foreground text-[10px]"
                    >
                        {p.etiqueta}
                    </text>
                </g>
            ))}

            <text
                x={ultimo.x}
                y={ultimo.y - 12}
                textAnchor="middle"
                className="fill-foreground text-xs font-medium tabular-nums"
            >
                {ultimo.total}
            </text>
        </svg>
    );
}
