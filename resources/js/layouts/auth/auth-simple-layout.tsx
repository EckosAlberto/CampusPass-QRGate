import { Link } from '@inertiajs/react';

import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-neutral-50 p-6 md:p-10 dark:bg-neutral-900">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col gap-4">
                        <Link
                            href={home()}
                            className="flex items-center justify-center gap-3"
                        >
                            <img
                                src="/images/logo-itt.png"
                                alt="Instituto Tecnológico de Tepic"
                                className="h-14 w-auto"
                            />
                        </Link>
                    </div>

                    <div className="w-full rounded-2xl border bg-white p-8 shadow-sm dark:bg-neutral-950">
                        {(title || description) && (
                            <div className="mb-6 space-y-1 text-center">
                                {title && (
                                    <h1 className="text-2xl font-bold text-blue-900 dark:text-blue-300">
                                        {title}
                                    </h1>
                                )}
                                {description && (
                                    <p className="text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                )}
                            </div>
                        )}

                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
