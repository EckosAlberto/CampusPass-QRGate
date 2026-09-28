import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    agregarEscaneoPendiente,
    contarEscaneosPendientes,
    eliminarEscaneoPendiente,
    listarEscaneosPendientes,
} from '@/lib/cola-escaneos-offline';

type Opciones = {
    /** URL firmada actual de esta pantalla de registro (Eventos/Graduación). */
    urlActual: string;
    /**
     * Nombre del componente Inertia que renderiza esta misma pantalla de
     * registro (p. ej. "eventos/registro"). Sirve para distinguir, al
     * sincronizar, si el servidor siguió respondiendo esta pantalla (el
     * escaneo se procesó, válido o no) de si nos mandó a "enlace no
     * disponible" (la sesión firmada ya expiró y no se puede sincronizar más).
     */
    componenteRegistro: string;
};

/**
 * Cola de escaneos pendientes por falta de conexión: guarda cada lectura en
 * IndexedDB mientras no hay internet y la reenvía en cuanto vuelve la
 * conexión, para que el control de acceso no se detenga ante una falla de
 * red. Solo para Eventos Académicos y Graduación (Biblioteca no lo usa).
 */
export function useColaEscaneosOffline({
    urlActual,
    componenteRegistro,
}: Opciones) {
    const [enLinea, setEnLinea] = useState(
        () => typeof navigator === 'undefined' || navigator.onLine,
    );
    const [pendientes, setPendientes] = useState(0);
    const sincronizandoRef = useRef(false);

    // Lee de IndexedDB cuántos escaneos siguen pendientes de esta URL.
    const actualizarConteo = useCallback(() => {
        contarEscaneosPendientes(urlActual)
            .then(setPendientes)
            .catch(() => {});
    }, [urlActual]);

    useEffect(() => {
        actualizarConteo();
    }, [actualizarConteo]);

    // Reenvía uno por uno los escaneos guardados mientras hubo internet.
    const sincronizar = useCallback(async () => {
        if (sincronizandoRef.current || !navigator.onLine) {
            return;
        }

        sincronizandoRef.current = true;

        try {
            const cola = await listarEscaneosPendientes(urlActual);
            let sincronizados = 0;
            let enlaceExpirado = false;

            for (const item of cola) {
                if (!navigator.onLine) {
                    break;
                }

                const resultado = await new Promise<
                    'procesado' | 'expirado' | 'fallo'
                >((resolve) => {
                    router.post(
                        urlActual,
                        { codigo: item.codigo, capturado_en: item.capturadoEn },
                        {
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess: (page) =>
                                resolve(
                                    page.component === componenteRegistro
                                        ? 'procesado'
                                        : 'expirado',
                                ),
                            onError: () => resolve('fallo'),
                        },
                    );
                });

                if (resultado === 'procesado') {
                    await eliminarEscaneoPendiente(item.id);
                    sincronizados++;

                    continue;
                }

                if (resultado === 'expirado') {
                    enlaceExpirado = true;
                }

                break;
            }

            if (sincronizados > 0) {
                toast.success(
                    `Se sincronizaron ${sincronizados} escaneo${sincronizados === 1 ? '' : 's'} pendiente${sincronizados === 1 ? '' : 's'}.`,
                );
            }

            if (enlaceExpirado) {
                toast.error(
                    'El enlace de esta sesión ya expiró: los escaneos restantes no se pudieron sincronizar.',
                );
            }

            actualizarConteo();
        } finally {
            sincronizandoRef.current = false;
        }
    }, [urlActual, componenteRegistro, actualizarConteo]);

    useEffect(() => {
        function alConectar() {
            setEnLinea(true);
            sincronizar();
        }

        function alDesconectar() {
            setEnLinea(false);
        }

        window.addEventListener('online', alConectar);
        window.addEventListener('offline', alDesconectar);

        if (navigator.onLine) {
            sincronizar();
        }

        return () => {
            window.removeEventListener('online', alConectar);
            window.removeEventListener('offline', alDesconectar);
        };
    }, [sincronizar]);

    // Guarda un escaneo en IndexedDB cuando no hay conexión, para sincronizarlo después.
    const guardarPendiente = useCallback(
        async (codigo: string) => {
            await agregarEscaneoPendiente({
                id: crypto.randomUUID(),
                url: urlActual,
                codigo,
                capturadoEn: new Date().toISOString(),
            });
            actualizarConteo();
        },
        [urlActual, actualizarConteo],
    );

    return { enLinea, pendientes, guardarPendiente };
}
