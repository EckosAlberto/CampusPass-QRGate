import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

// Forma mínima que necesita este componente — así sirve tanto para el
// `User` de Biblioteca como para `EventosUsuario`/`GraduacionUsuario`, que
// no comparten todos los campos de `User` (email_verified_at, etc.).
type UsuarioBasico = {
    name: string;
    email: string;
    avatar?: string;
    rol_label: string | null;
};

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: UsuarioBasico;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.email}
                    </span>
                )}
                {user.rol_label && (
                    <span className="truncate text-xs text-muted-foreground">
                        {user.rol_label}
                    </span>
                )}
            </div>
        </>
    );
}
