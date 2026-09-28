import { Form, Head, Link } from '@inertiajs/react';
import { Lock, User } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

// Tipos de props que recibe el componente de inicio de sesión.
type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

// Página de inicio de sesión.
export default function Login({
    status,
    canResetPassword,
    canRegister,
}: Props) {
    return (
        <>
            <Head title="Iniciar sesión" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className="font-bold text-blue-900 dark:text-blue-300"
                                >
                                    Usuario
                                </Label>
                                <div className="relative">
                                    <User className="pointer-events-none absolute inset-y-0 left-3 my-auto size-4 text-amber-500" />
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="Ingresa tu usuario"
                                        className="bg-neutral-100 pl-10 dark:bg-neutral-900"
                                    />
                                </div>
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className="font-bold text-blue-900 dark:text-blue-300"
                                >
                                    Contraseña
                                </Label>
                                <div className="relative">
                                    <Lock className="pointer-events-none absolute inset-y-0 left-3 z-10 my-auto size-4 text-amber-500" />
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Ingresa tu contraseña"
                                        className="bg-neutral-100 pl-10 dark:bg-neutral-900"
                                    />
                                </div>
                                <InputError message={errors.password} />
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="w-full bg-blue-900 font-bold text-white hover:bg-blue-800"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Iniciar sesión
                            </Button>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                    />
                                    <Label
                                        htmlFor="remember"
                                        className="text-sm font-normal text-muted-foreground"
                                    >
                                        Recordarme
                                    </Label>
                                </div>

                                {canResetPassword && (
                                    <Link
                                        href={request()}
                                        tabIndex={5}
                                        className="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
                                    >
                                        Recuperar contraseña
                                    </Link>
                                )}
                            </div>
                        </div>

                        {canRegister && (
                            <div className="text-center text-sm text-muted-foreground">
                                ¿No tienes cuenta?{' '}
                                <TextLink href={register()} tabIndex={6}>
                                    Regístrate
                                </TextLink>
                            </div>
                        )}
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Centro de Información',
    description: 'Inicia sesión para gestionar el acceso a la biblioteca.',
};
