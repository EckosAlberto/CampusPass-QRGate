import { BrowserMultiFormatReader } from '@zxing/browser';
import {
    BarcodeFormat,
    DecodeHintType,
    NotFoundException,
} from '@zxing/library';
import { Camera } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

// Props para el componente EscanerCamara
type Props = {
    activo: boolean;
    onDecodificado: (codigo: string) => void;
    onError?: () => void;
    
    formatos?: BarcodeFormat[];
    
    overlay?: ReactNode;
};
// Formatos de código de barras que se intentarán decodificar por defecto.
const FORMATOS_POR_DEFECTO = [BarcodeFormat.QR_CODE, BarcodeFormat.CODE_128];

// Retardo entre intentos de decodificación en milisegundos.
const RETARDO_ENTRE_INTENTOS_MS = 200;

// Tiempo máximo de espera para que la cámara se inicie y envíe una señal en milisegundos.
const TIEMPO_ESPERA_CAMARA_MS = 4000;

// Número máximo de reintentos para iniciar la cámara antes de mostrar un error.
const MAX_REINTENTOS = 1;

// Número máximo de reintentos cuando la cámara se congela antes de mostrar un error.
const MAX_REINTENTOS_CONGELADO = 5;

// Intervalo de vigilancia para detectar si la cámara se ha congelado en milisegundos.
const INTERVALO_VIGILANCIA_MS = 1500;

// Umbral de tiempo en milisegundos para considerar que la cámara está congelada.
const UMBRAL_CONGELADO_MS = 3000;

// Vista de cámara en vivo que decodifica credenciales (QR o código de
// barras) usando ZXing.
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
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
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
        <div className="mt-6 flex w-full max-w-sm flex-col items-center gap-3">
            <div className="relative w-full overflow-hidden rounded-2xl border-2 border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/30">
                <video
                    ref={videoRef}
                    className="aspect-square w-full object-cover"
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
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center p-8">
                                <div className="aspect-square w-full max-w-[80%] rounded-xl border-4 border-dashed border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.25)]" />
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
