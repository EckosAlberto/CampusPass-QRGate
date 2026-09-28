import { Form, Head } from '@inertiajs/react';
import { Lock, User } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/graduacion/login';

// Página de inicio de sesión del equipo de Graduación, separada por
// completo del login de Biblioteca (guard y tabla de usuarios propios).
export default function Login() {
    return (
        <>
            <Head title="Graduación — Iniciar sesión" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
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
                                    type="text"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="username"
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
                            tabIndex={3}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Iniciar sesión
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Graduación',
    description: 'Inicia sesión para administrar ceremonias de graduación.',
};
