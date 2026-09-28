// Components
import { Form, Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

// Página de verificación de correo electrónico
export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Verificar correo" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    Se envió un nuevo enlace de verificación al correo
                    electrónico que proporcionaste al registrarte.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Reenviar correo de verificación
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Cerrar sesión
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verificar correo',
    description:
        'Verifica tu correo electrónico dando clic en el enlace que te acabamos de enviar.',
};
