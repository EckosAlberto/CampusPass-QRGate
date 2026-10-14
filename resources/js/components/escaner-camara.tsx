import { BrowserMultiFormatReader } from '@zxing/browser';
import {
    BarcodeFormat,
    DecodeHintType,
    NotFoundException,
} from '@zxing/library';
import { Camera } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

type Props = {
    activo: boolean;
    onDecodificado: (codigo: string) => void;
    onError?: () => void;
    formatos?: BarcodeFormat[];
    overlay?: ReactNode;
};

const FORMATOS_POR_DEFECTO = [BarcodeFormat.QR_CODE, BarcodeFormat.CODE_128]; // Se incluyen los formatos más comunes para credenciales y códigos QR
const RETARDO_ENTRE_INTENTOS_MS = 100; // Reducido para agilizar la lectura
const TIEMPO_ESPERA_CAMARA_MS = 4000; // Tiempo máximo para que la cámara comience a transmitir antes de mostrar un error
const MAX_REINTENTOS = 1; // Se permite un solo reintento para evitar bucles de reinicio
const MAX_REINTENTOS_CONGELADO = 5; // Número máximo de reintentos permitidos cuando la cámara está congelada
const INTERVALO_VIGILANCIA_MS = 1500; // Intervalo para verificar si la cámara está congelada
const UMBRAL_CONGELADO_MS = 3000;

// Componente React para escanear códigos de barras y QR usando la cámara del dispositivo
export function EscanerCamara({
    activo,
    onDecodificado,
    onError,
    formatos = FORMATOS_POR_DEFECTO,
    overlay,
}: Props) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const onDecodificadoRef = useRef(onDecodificado);
    const onErrorRef = useRef(onError);
    const formatosRef = useRef(formatos);

    const [error, setError] = useState<string | null>(null);
    const [listo, setListo] = useState(false);

    useEffect(() => {
        onDecodificadoRef.current = onDecodificado;
        onErrorRef.current = onError;
        formatosRef.current = formatos;
    }, [onDecodificado, onError, formatos]);

    const [reinicio, setReinicio] = useState(0);
    const intentosRef = useRef(0);
    const reintentosCongeladoRef = useRef(0);
    const activoAnteriorRef = useRef(false);

    useEffect(() => {
        const video = videoRef.current;

        if (!video || !activo) {
            activoAnteriorRef.current = activo;
            return;
        }

        if (!activoAnteriorRef.current) {
            intentosRef.current = 0;
            reintentosCongeladoRef.current = 0;
        }

        activoAnteriorRef.current = true;
        setError(null);
        setListo(false);

        let cancelado = false;
        let yaDecodificado = false;
        let recibioSenal = false;
        let controlesActuales: Awaited<
            ReturnType<BrowserMultiFormatReader['decodeFromConstraints']>
        > | null = null;

        const hints = new Map<DecodeHintType, unknown>([
            [DecodeHintType.POSSIBLE_FORMATS, formatosRef.current],
            [DecodeHintType.TRY_HARDER, true],
        ]);
        const lector = new BrowserMultiFormatReader(hints, {
            delayBetweenScanAttempts: RETARDO_ENTRE_INTENTOS_MS,
            delayBetweenScanSuccess: 1000,
        });

        const controlesPromesa = lector.decodeFromConstraints(
            {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            },
            video,
            (resultado, err) => {
                if (cancelado) {
                    return;
                }

                if (resultado) {
                    recibioSenal = true;
                    setListo(true);
                    if (!yaDecodificado) {
                        yaDecodificado = true;
                        onDecodificadoRef.current(resultado.getText());
                    }
                    return;
                }

                if (err && !(err instanceof NotFoundException)) {
                    return;
                }

                recibioSenal = true;
                setListo(true);
            },
        );

        controlesPromesa
            .then((controles) => {
                controlesActuales = controles;
            })
            .catch(() => {
                if (cancelado) {
                    return;
                }
                setError(
                    'No se pudo acceder a la cámara. Verifica que el navegador tenga permiso de cámara e inténtalo de nuevo.',
                );
                onErrorRef.current?.();
            });

        const watchdog = setTimeout(() => {
            if (cancelado || recibioSenal) {
                return;
            }

            if (intentosRef.current < MAX_REINTENTOS) {
                intentosRef.current += 1;
                setReinicio((previo) => previo + 1);
                return;
            }

            setError(
                'No se pudo iniciar la cámara. Recarga la página e inténtalo de nuevo.',
            );
            onErrorRef.current?.();
        }, TIEMPO_ESPERA_CAMARA_MS);

        let ultimoTiempoVideo = -1;
        let ultimoAvanceEn = Date.now();

        const vigilancia = setInterval(() => {
            if (cancelado || yaDecodificado) {
                return;
            }

            if (video.currentTime !== ultimoTiempoVideo) {
                ultimoTiempoVideo = video.currentTime;
                ultimoAvanceEn = Date.now();
                return;
            }

            if (Date.now() - ultimoAvanceEn < UMBRAL_CONGELADO_MS) {
                return;
            }

            if (reintentosCongeladoRef.current >= MAX_REINTENTOS_CONGELADO) {
                setError(
                    'La cámara dejó de responder. Recarga la página e inténtalo de nuevo.',
                );
                onErrorRef.current?.();
                return;
            }

            reintentosCongeladoRef.current += 1;
            setReinicio((previo) => previo + 1);
        }, INTERVALO_VIGILANCIA_MS);

        return () => {
            cancelado = true;
            clearTimeout(watchdog);
            clearInterval(vigilancia);

            if (controlesActuales) {
                controlesActuales.stop();
            } else {
                controlesPromesa
                    .then((controles) => controles.stop())
                    .catch(() => {});
            }
        };
    }, [activo, reinicio]);

    if (error) {
        return (
            <p className="mt-6 max-w-sm text-sm text-red-600 dark:text-red-400">
                {error}
            </p>
        );
    }

    return (
        <div className="mt-6 flex w-full max-w-md flex-col items-center gap-3">
            <div className="relative w-full overflow-hidden rounded-2xl border-2 border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/30">
                <video
                    ref={videoRef}
                    className="aspect-video w-full object-cover"
                    muted
                    playsInline
                />
                {overlay ? (
                    <div className="absolute inset-0 flex items-center justify-center bg-white p-4 dark:bg-neutral-900">
                        {overlay}
                    </div>
                ) : (
                    <>
                        {!listo && (
                            <div className="absolute inset-0 flex items-center justify-center">
                                <Camera
                                    className="size-16 text-emerald-600/60 dark:text-emerald-400/40"
                                    strokeWidth={1.5}
                                />
                            </div>
                        )}
                        {listo && (
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center p-6">
                                {/* Cuadro de escaneo */}
                                <div className="h-48 w-full max-w-[85%] rounded-xl border-4 border-dashed border-white/90 shadow-[0_0_0_9999px_rgba(0,0,0,0.3)]" />
                            </div>
                        )}
                    </>
                )}
            </div>

            <p className="w-full rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-center font-medium text-blue-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-blue-300">
                Coloca tu código QR o credencial frente al escáner
            </p>
        </div>
    );
}