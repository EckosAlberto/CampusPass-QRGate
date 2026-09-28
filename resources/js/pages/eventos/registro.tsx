import { Form, Head, router } from '@inertiajs/react';
import { BarcodeFormat } from '@zxing/library';
import {
    CloudOff,
    LogIn,
    LogOut,
    ScanLine,
    UploadCloud,
    XCircle,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { EscanerCamara } from '@/components/escaner-camara';
import InputError from '@/components/input-error';
import { InstitutionHeader } from '@/components/institution-header';
import { Reloj } from '@/components/reloj';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useColaEscaneosOffline } from '@/hooks/use-cola-escaneos-offline';
import { cn } from '@/lib/utils';
import type { RegistroEventoProps, Resultado } from '@/types';

// Tiempo en milisegundos que se muestra el resultado antes de volver al formulario
const AUTO_RETORNO_MS = 4000;

// Tiempo en milisegundos que se muestra la confirmación de "guardado sin
// conexión" antes de volver a activar la cámara.
const CONFIRMACION_OFFLINE_MS = 3000;

const COMPONENTE_REGISTRO = 'eventos/registro';

// Las credenciales físicas del Tec solo traen un código de barras Code128
// legible con el número de control: su QR apunta a un portal externo del
// plantel (emergencias.tepic.tecnm.mx) que no podemos resolver.
const FORMATOS_ESCANER = [BarcodeFormat.CODE_128];

function iniciales(nombre: string) {
    return nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte[0]?.toUpperCase())
        .join('');
}

// Contenido del resultado, superpuesto dentro del cuadro de la cámara.
function ResultadoOverlay({ resultado }: { resultado: Resultado }) {
    if (resultado.estado === 'error') {
        return (
            <div className="flex flex-col items-center gap-2 text-center">
                <XCircle className="size-10 text-red-500" />
                <h2 className="text-lg font-bold tracking-tight text-red-600 dark:text-red-400">
                    {resultado.titulo}
                </h2>
                <p className="max-w-60 text-sm text-muted-foreground">
                    {resultado.mensaje}
                </p>
            </div>
        );
    }

    const esEntrada = resultado.tipoMovimiento === 'ENTRADA';

    return (
        <div className="flex flex-col items-center gap-2 text-center">
            <div className="flex size-14 items-center justify-center rounded-full bg-blue-900/10 text-lg font-bold text-blue-900 dark:bg-blue-400/10 dark:text-blue-300">
                {iniciales(resultado.alumno.nombre)}
            </div>

            <h2 className="text-lg font-bold tracking-tight">
                {resultado.alumno.nombre}
            </h2>
            <p className="text-xs text-muted-foreground">
                No. de control: {resultado.alumno.noDeControl}
                {resultado.alumno.carrera && (
                    <> &middot; {resultado.alumno.carrera}</>
                )}
            </p>

            <div
                className={cn(
                    'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold text-white',
                    esEntrada
                        ? 'bg-violet-400 dark:bg-violet-500'
                        : 'bg-emerald-600',
                )}
            >
                {esEntrada ? (
                    <LogIn className="size-3.5" />
                ) : (
                    <LogOut className="size-3.5" />
                )}
                {resultado.tipoMovimiento} &middot; {resultado.hora}
            </div>

            <p className="mt-1 max-w-60 text-sm font-medium">
                {resultado.mensaje}
            </p>
        </div>
    );
}

// Escanea credenciales con la cámara y envía cada escaneo a la URL firmada
// actual (no hay ruta Wayfinder posible: el path incluye una firma dinámica).
// Si no hay conexión, el escaneo se guarda localmente (IndexedDB) y se
// sincroniza solo en cuanto vuelve la red, para que el control de acceso no
// se detenga ante una falla de internet. El resultado del último escaneo se
// superpone dentro del cuadro de la cámara (en vez de reemplazar toda la
// pantalla) durante AUTO_RETORNO_MS.
function EscanerForm({
    nombreEvento,
    tipo,
    urlActual,
    resultadoDelServidor,
}: {
    nombreEvento: string | null;
    tipo: 'ENTRADA' | 'SALIDA';
    urlActual: string;
    resultadoDelServidor?: Resultado | null;
}) {
    const [resultado, setResultado] = useState(resultadoDelServidor ?? null);
    const [enviando, setEnviando] = useState(false);
    const [mostrarManual, setMostrarManual] = useState(false);
    const [guardadoOffline, setGuardadoOffline] = useState(false);

    const { enLinea, pendientes, guardarPendiente } = useColaEscaneosOffline({
        urlActual,
        componenteRegistro: COMPONENTE_REGISTRO,
    });

    useEffect(() => {
        if (!resultado) {
            return;
        }

        const timer = setTimeout(() => setResultado(null), AUTO_RETORNO_MS);

        return () => clearTimeout(timer);
    }, [resultado]);

    // Manda un código al servidor para registrar la entrada/salida (ruta firmada del kiosco).
    const enviarCodigo = useCallback(
        (codigo: string) => {
            router.post(
                urlActual,
                { codigo },
                {
                    preserveScroll: true,
                    onFinish: () => setEnviando(false),
                },
            );
        },
        [urlActual],
    );

    // Se llama cada vez que la cámara lee un código: lo envía si hay internet, o lo guarda en la cola sin conexión.
    const manejarDecodificado = useCallback(
        (codigo: string) => {
            setEnviando((yaEnviando) => {
                if (yaEnviando) {
                    return yaEnviando;
                }

                if (!navigator.onLine) {
                    guardarPendiente(codigo).then(() => {
                        setGuardadoOffline(true);
                        setTimeout(() => {
                            setGuardadoOffline(false);
                            setEnviando(false);
                        }, CONFIRMACION_OFFLINE_MS);
                    });
                } else {
                    enviarCodigo(codigo);
                }

                return true;
            });
        },
        [enviarCodigo, guardarPendiente],
    );

    return (
        <>
            <h1 className="text-4xl font-extrabold tracking-tight text-blue-900 uppercase dark:text-blue-300">
                {nombreEvento ?? 'Registro de acceso'}
            </h1>
            <p className="mt-1 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                {tipo === 'ENTRADA'
                    ? 'Registro de entrada'
                    : 'Registro de salida'}
            </p>

            <div className="mt-6">
                <Reloj />
            </div>

            {!enLinea && (
                <div className="mt-4 flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                    <CloudOff className="size-4 shrink-0" />
                    Sin conexión: los escaneos se guardan y se sincronizan solos
                    al volver internet.
                </div>
            )}

            {enLinea && pendientes > 0 && (
                <div className="mt-4 flex items-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-primary dark:border-blue-900 dark:bg-blue-950/40">
                    <UploadCloud className="size-4 shrink-0 animate-pulse" />
                    Sincronizando {pendientes} escaneo
                    {pendientes === 1 ? '' : 's'} pendiente
                    {pendientes === 1 ? '' : 's'}…
                </div>
            )}

            <EscanerCamara
                activo={!enviando && !resultado}
                onDecodificado={manejarDecodificado}
                onError={() => setMostrarManual(true)}
                formatos={FORMATOS_ESCANER}
                overlay={
                    resultado ? (
                        <ResultadoOverlay resultado={resultado} />
                    ) : undefined
                }
            />

            {guardadoOffline && (
                <p className="mt-4 text-sm font-medium text-amber-700 dark:text-amber-400">
                    Guardado sin conexión. Se enviará solo cuando vuelva el
                    internet.
                </p>
            )}

            {enviando && !guardadoOffline && !resultado && (
                <p className="mt-4 text-sm font-medium text-muted-foreground">
                    Verificando credencial…
                </p>
            )}

            {!resultado &&
                (mostrarManual ? (
                    <Form
                        action={urlActual}
                        method="post"
                        resetOnSuccess={['codigo']}
                        className="mt-6 flex w-full max-w-sm flex-col items-center gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="w-full text-left">
                                    <Label htmlFor="codigo" className="sr-only">
                                        Código QR / número de control
                                    </Label>
                                    <Input
                                        id="codigo"
                                        name="codigo"
                                        autoFocus
                                        autoComplete="off"
                                        placeholder="Escribe tu número de control…"
                                        className="text-center"
                                    />
                                    <InputError message={errors.codigo} />
                                </div>

                                <Button
                                    type="submit"
                                    size="lg"
                                    disabled={processing}
                                    className="w-full gap-2 bg-emerald-600 text-white hover:bg-emerald-700"
                                >
                                    <ScanLine className="size-5" />
                                    Confirmar registro
                                </Button>
                            </>
                        )}
                    </Form>
                ) : (
                    <button
                        type="button"
                        onClick={() => setMostrarManual(true)}
                        className="mt-6 text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                    >
                        Ingresar número de control manualmente
                    </button>
                ))}
        </>
    );
}

export default function EventosRegistro({
    evento,
    tipo,
    urlActual,
    resultado,
}: RegistroEventoProps) {
    return (
        <>
            <Head title={`${evento.nombre ?? 'Evento'} — Registro de acceso`} />

            <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
                <InstitutionHeader />

                <main className="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
                    <EscanerForm
                        key={resultado?.id ?? 'scan'}
                        nombreEvento={evento.nombre}
                        tipo={tipo}
                        urlActual={urlActual}
                        resultadoDelServidor={resultado}
                    />
                </main>
            </div>
        </>
    );
}
