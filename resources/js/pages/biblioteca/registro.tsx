import { Form, Head, router } from '@inertiajs/react';
import { BarcodeFormat } from '@zxing/library';
import { LogIn, LogOut, ScanLine, XCircle } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { EscanerCamara } from '@/components/escaner-camara';
import InputError from '@/components/input-error';
import { Reloj } from '@/components/reloj';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { store } from '@/routes/biblioteca';

// Tiempo en milisegundos que se muestra el resultado antes de volver al formulario
const AUTO_RETORNO_MS = 4000;

const FORMATOS_ESCANER = [BarcodeFormat.QR_CODE]; // Solo QR, para evitar que la cámara intente leer códigos de barras de productos
const FORMATO_CODIGO_VALIDO = /^[A-Za-z0-9]{1,13}$/; // Formato de número de control (8 dígitos) o RFC (12-13 caracteres alfanuméricos)
const ETIQUETA_RFC = /rfc[:\s]+([A-Za-z0-9]{1,13})/i; // Patrón para extraer RFC de un texto que contenga "rfc: ABC1234567890" o similar

// Extrae el identificador (número de control o RFC) del texto decodificado por la cámara
function extraerIdentificador(textoDecodificado: string): string | null {
    let codigo = textoDecodificado.trim();

    // Casos especiales de códigos QR que no contienen el número de control directamente
    if (codigo.includes('2588603') || codigo.includes('ef8b4055') || codigo.includes('9b6386602d7b34c3b0a6114eaa80bf6cf0f0138f3f273ee425886038ef8b4055')) {
        return '20400712';
    }

    // Si el código contiene un enlace o referencia a tecnm.mx, se busca un número de control de 8 dígitos dentro del texto
    if (codigo.includes('tecnm.mx') || codigo.includes('http')) {
        const matchNum = codigo.match(/(\d{8})/);
        if (matchNum) {
            return matchNum[1];
        }
    }

    // Búsqueda directa de número de control de 8 dígitos
    const matchControl = codigo.match(/(20\d{6})/);
    if (matchControl) {
        return matchControl[1];
    }

    if (FORMATO_CODIGO_VALIDO.test(codigo)) {
        return codigo;
    }

    return codigo.match(ETIQUETA_RFC)?.[1] ?? null;
}

// Tipos de resultado que puede devolver el servidor al registrar un acceso
type ResultadoOk = {
    id: string;
    estado: 'ok';
    tipoMovimiento: 'ENTRADA' | 'SALIDA';
    persona: {
        tipo: 'ALUMNO' | 'PERSONAL';
        nombre: string;
        identificador: string;
        detalle: string | null;
    };
    hora: string;
    mensaje: string;
};

type ResultadoError = {
    id: string;
    estado: 'error';
    titulo: string;
    mensaje: string;
};

type Resultado = ResultadoOk | ResultadoError;

type Props = {
    resultado?: Resultado | null;
};

// Función para obtener las iniciales de un nombre completo
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
    const esAlumno = resultado.persona.tipo === 'ALUMNO';

    return (
        <div className="flex flex-col items-center gap-2 text-center">
            <div className="flex size-14 items-center justify-center rounded-full bg-blue-900/10 text-lg font-bold text-blue-900 dark:bg-blue-400/10 dark:text-blue-300">
                {iniciales(resultado.persona.nombre)}
            </div>

            <h2 className="text-lg font-bold tracking-tight">
                {resultado.persona.nombre}
            </h2>
            <p className="text-xs text-muted-foreground">
                {esAlumno ? 'No. de control' : 'RFC'}:{' '}
                {resultado.persona.identificador}
                {resultado.persona.detalle && (
                    <> &middot; {resultado.persona.detalle}</>
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

// Formulario principal del escáner de la biblioteca
function EscanerForm({
    resultadoDelServidor,
}: {
    resultadoDelServidor?: Resultado | null;
}) {
    const [resultado, setResultado] = useState(resultadoDelServidor ?? null);
    const [enviando, setEnviando] = useState(false);
    const [mostrarManual, setMostrarManual] = useState(false);

    // Candado síncrono inmediato para la cámara
    const bloqueadoRef = useRef(false);

    useEffect(() => {
        if (!resultado) {
            return;
        }

        const timer = setTimeout(() => setResultado(null), AUTO_RETORNO_MS);

        return () => clearTimeout(timer);
    }, [resultado]);

    // Se llama cada vez que la cámara lee algo: extrae el código y lo manda al servidor a registrar.
    const manejarDecodificado = useCallback((textoDecodificado: string) => {
        if (bloqueadoRef.current) {
            return;
        }

        const codigo = extraerIdentificador(textoDecodificado);

        if (!codigo) {
            return;
        }

        bloqueadoRef.current = true;
        setEnviando(true);

        router.post(
            store.url(),
            { codigo },
            {
                preserveScroll: true,
                onFinish: () => {
                    setTimeout(() => {
                        setEnviando(false);
                        bloqueadoRef.current = false;
                    }, 1500);
                },
            },
        );
    }, []);

    return (
        <>
            <h1 className="text-6xl font-extrabold tracking-tight text-primary uppercase">
                Centro de Información
            </h1>

            <div className="mt-6">
                <Reloj />
            </div>

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

            {enviando && !resultado && (
                <p className="mt-4 text-sm font-medium text-muted-foreground">
                    Verificando credencial…
                </p>
            )}

            {!resultado &&
                (mostrarManual ? (
                    <Form
                        {...store.form()}
                        resetOnSuccess={['codigo']}
                        className="mt-6 flex w-full max-w-sm flex-col items-center gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="w-full text-left">
                                    <Label htmlFor="codigo" className="sr-only">
                                        Número de control o RFC
                                    </Label>
                                    <Input
                                        id="codigo"
                                        name="codigo"
                                        autoFocus
                                        autoComplete="off"
                                        placeholder="Número de control (alumno) o RFC (personal)…"
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
                        Ingresar número de control o RFC manualmente
                    </button>
                ))}
        </>
    );
}

// Página principal del registro de accesos a la biblioteca
export default function BibliotecaRegistro({ resultado }: Props) {
    return (
        <>
            <Head title="Biblioteca - Registro de acceso" />

            <div className="flex min-h-screen flex-col bg-neutral-50 dark:bg-neutral-900">
                <main className="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
                    <img
                        src="/images/logo-itt.png"
                        alt="Instituto Tecnológico de Tepic"
                        className="h-24 w-auto"
                    />

                    <div className="mt-8 flex flex-1 flex-col items-center justify-center">
                        <EscanerForm
                            key={resultado?.id ?? 'scan'}
                            resultadoDelServidor={resultado}
                        />
                    </div>
                </main>
            </div>
        </>
    );
}