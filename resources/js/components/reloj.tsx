import { useClock } from '@/hooks/use-clock';

const FORMATO_HORA = new Intl.DateTimeFormat('es-MX', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
});

const FORMATO_FECHA = new Intl.DateTimeFormat('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

export function Reloj() {
    const ahora = useClock();

    return (
        <div className="flex flex-col items-center leading-tight">
            <span className="text-5xl font-extrabold text-blue-900 tabular-nums dark:text-blue-300">
                {FORMATO_HORA.format(ahora)}
            </span>
            <span className="mt-1 text-lg font-semibold tracking-wide text-blue-900 uppercase dark:text-blue-300">
                {FORMATO_FECHA.format(ahora)}
            </span>
        </div>
    );
}
