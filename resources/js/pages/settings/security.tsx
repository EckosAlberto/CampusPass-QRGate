import { Form, Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Button } from '@/components/ui/button';
import UpdatePasswordForm from '@/components/update-password-form';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { edit } from '@/routes/security';
import {
    confirm,
    disable,
    enable,
    qrCode,
    recoveryCodes as recoveryCodesRoute,
    regenerateRecoveryCodes,
    secretKey,
} from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

// Página de configuración de seguridad del usuario
export default function Security({
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
}: Props) {
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth({
        qrCode,
        secretKey,
        recoveryCodes: recoveryCodesRoute,
    });
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }

        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    return (
        <>
            <Head title="Seguridad" />

            <h1 className="sr-only">Seguridad</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Actualizar contraseña"
                    description="Usa una contraseña larga y aleatoria para mantener tu cuenta segura"
                />

                <UpdatePasswordForm
                    updateForm={SecurityController.update.form}
                />
            </div>

            {canManageTwoFactor && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Autenticación en dos pasos"
                        description="Administra la autenticación en dos pasos de tu cuenta"
                    />
                    {twoFactorEnabled ? (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Se te pedirá un código seguro al iniciar sesión,
                                que puedes obtener desde una aplicación
                                autenticadora (TOTP) en tu teléfono.
                            </p>

                            <div className="relative inline">
                                <Form {...disable.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Desactivar 2FA
                                        </Button>
                                    )}
                                </Form>
                            </div>

                            <TwoFactorRecoveryCodes
                                recoveryCodesList={recoveryCodesList}
                                fetchRecoveryCodes={fetchRecoveryCodes}
                                errors={errors}
                                regenerateForm={regenerateRecoveryCodes.form}
                            />
                        </div>
                    ) : (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Al activar la autenticación en dos pasos, se te
                                pedirá un código seguro al iniciar sesión. Este
                                código se obtiene desde una aplicación
                                autenticadora (TOTP) en tu teléfono.
                            </p>

                            <div>
                                {hasSetupData ? (
                                    <Button
                                        onClick={() => setShowSetupModal(true)}
                                    >
                                        <ShieldCheck />
                                        Continuar configuración
                                    </Button>
                                ) : (
                                    <Form
                                        {...enable.form()}
                                        onSuccess={() =>
                                            setShowSetupModal(true)
                                        }
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Activar 2FA
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    )}

                    <TwoFactorSetupModal
                        isOpen={showSetupModal}
                        onClose={() => setShowSetupModal(false)}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        clearSetupData={clearSetupData}
                        fetchSetupData={fetchSetupData}
                        errors={errors}
                        confirmForm={confirm.form}
                    />
                </div>
            )}
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Seguridad',
            href: edit(),
        },
    ],
};
