import { useEffect, useState } from 'react';

// Hook que expone la hora actual y se actualiza cada `intervalMs` milisegundos.
export function useClock(intervalMs = 1000) {
    const [ahora, setAhora] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setAhora(new Date()), intervalMs);

        return () => clearInterval(timer);
    }, [intervalMs]);

    return ahora;
}
