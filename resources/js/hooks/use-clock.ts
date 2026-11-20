import { useEffect, useState } from 'react';

export function useClock(intervalMs = 1000) {
    const [ahora, setAhora] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setAhora(new Date()), intervalMs);

        return () => clearInterval(timer);
    }, [intervalMs]);

    return ahora;
}
