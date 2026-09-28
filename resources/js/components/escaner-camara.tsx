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
    // Formatos a decodificar. Por defecto ambos (QR + código de barras),
    // pero cada pantalla debe restringirlo al formato real de lo que
    // escanea: las credenciales físicas del Tec solo traen un código de
    // barras Code128 legible (su QR apunta a un portal externo que no
    // puedo resolver), mientras que los boletos propios de CampusPass
    // (graduación) son QR. Sin restringir, un QR ilegible que quede dentro
    // del cuadro (como el de la credencial física) puede "ganarle" al
    // código de barras que sí nos sirve.
    formatos?: BarcodeFormat[];
    // Contenido a mostrar cubriendo el cuadro de la cámara (p. ej. el
    // resultado de un escaneo) en vez de los overlays normales de
    // "cámara iniciando" / "marco de encuadre". El llamador sigue siendo
    // responsable de pausar la decodificación (prop `activo`) mientras lo
    // muestra.
    overlay?: ReactNode;
};

const FORMATOS_POR_DEFECTO = [BarcodeFormat.QR_CODE, BarcodeFormat.CODE_128];

// Milisegundos entre intentos de decodificación sucesivos, para no saturar
// el hilo principal ni disparar onDecodificado varias veces por el mismo
// cuadro de video.
const RETARDO_ENTRE_INTENTOS_MS = 200;

// Si la cámara no da señal (ni un solo intento de decodificación) dentro de
// este tiempo, se reinicia sola una vez — en algunos equipos, al navegar
// entre pantallas (sin recargar la página) el stream de video se queda
// "congelado" en gris y solo una recarga completa lo destrababa; este
// reintento automático reproduce el mismo efecto sin depender de que
// alguien recargue la página a mano.
const TIEMPO_ESPERA_CAMARA_MS = 4000;

const MAX_REINTENTOS = 1;

// Tope de reinicios automáticos por congelamiento antes de rendirse y pedir
// recargar la página — evita reintentar en silencio para siempre si el
// problema es de hardware y no se puede resolver reabriendo la cámara.
const MAX_REINTENTOS_CONGELADO = 5;

// ZXing dispara su callback de decodificación (con NotFoundException) sobre
// cada cuadro que logra capturar, incluso si ese cuadro es el mismo de
// siempre porque el <video> se quedó pintando una imagen fija — por eso el
// watchdog de arriba, que solo mira si "hubo señal de decodificación", no
// detecta este caso: cree que la cámara sigue viva aunque el video esté
// congelado. Para eso se vigila aparte que video.currentTime siga
// avanzando; si no avanza durante este umbral, se fuerza un reinicio
// completo (sin límite de reintentos, a diferencia del watchdog inicial,
// porque esto es un vídeo que sí llegó a funcionar y se puede recuperar).
const INTERVALO_VIGILANCIA_MS = 1500;
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
    // Los formatos no deben disparar un reinicio de cámara si el llamador
    // pasa un array nuevo en cada render (p. ej. un literal inline) — solo
    // se leen al montar el escáner.
    const formatosRef = useRef(formatos);

    const [error, setError] = useState<string | null>(null);
    const [listo, setListo] = useState(false);

    useEffect(() => {
        onDecodificadoRef.current = onDecodificado;
        onErrorRef.current = onError;
        formatosRef.current = formatos;
    }, [onDecodificado, onError, formatos]);

    // Se incrementa para forzar un reinicio manual/automático del efecto de
    // abajo sin depender de que cambien `activo` o `formatos`.
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

        // Cada vez que arranca una sesión de escaneo nueva (activo pasa a
        // true), se reinicia el contador de reintentos — si no, una sesión
        // anterior que ya reintentó una vez dejaría a la siguiente sin
        // margen.
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
            // Un código de barras 1D (como el de las credenciales físicas)
            // necesita bastante más resolución/precisión que un QR para
            // decodificarse desde una webcam genérica — sin esto, el lector
            // 1D casi nunca alcanza a leerlo a tiempo.
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

                    // decodeFromConstraints puede volver a llamar el
                    // callback con el mismo resultado antes de que el
                    // componente padre alcance a desactivar `activo`.
                    if (!yaDecodificado) {
                        yaDecodificado = true;
                        onDecodificadoRef.current(resultado.getText());
                    }

                    return;
                }

                // ZXing dispara NotFoundException en cada cuadro sin código
                // detectado: es el flujo normal mientras no hay nada frente
                // a la cámara, no un error real.
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

            // Sin condición de readyState: si el video nunca llega a tener
            // un cuadro real (se queda en 0 desde el inicio, no que se
            // pegue después de andar bien), readyState jamás avanzaría y
            // esta vigilancia nunca actuaría — el umbral de tiempo ya da
            // margen de sobra para un arranque normal.
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
