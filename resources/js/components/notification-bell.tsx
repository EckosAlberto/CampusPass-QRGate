import { useHttp } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { RouteDefinition } from '@/wayfinder';

type NotificacionItem = {
    id: string;
    tipoLabel: string;
    mensaje: string | null;
    fecha: string;
    hora: string;
};

type Props = {
    // Rutas del guard correspondiente — cada módulo (Biblioteca/Eventos/
    // Graduación) tiene su propio endpoint, ya que las incidencias de cada
    // uno se consultan por separado.
    indexRoute: () => RouteDefinition<'get'>;
    marcarVistasRoute: () => RouteDefinition<'post'>;
};

// Campana de notificaciones con las incidencias de acceso recientes del
// módulo (código inválido, no registrado, etc.). Al abrirse, marca las
// pendientes como vistas.
export function NotificationBell({ indexRoute, marcarVistasRoute }: Props) {
    const { submit } = useHttp();
    const [items, setItems] = useState<NotificacionItem[]>([]);
    const [noVistas, setNoVistas] = useState(0);
    const [marcando, setMarcando] = useState(false);

    // Pide al servidor las incidencias recientes y el conteo de no vistas.
    const cargar = useCallback(async () => {
        try {
            const datos = (await submit(indexRoute())) as {
                items: NotificacionItem[];
                noVistas: number;
            };

            setItems(datos.items);
            setNoVistas(datos.noVistas);
        } catch {
            // La campana no es crítica para el flujo del panel: si falla,
            // simplemente se queda sin datos hasta el siguiente intento.
        }
    }, [submit, indexRoute]);

    const cargarRef = useRef(cargar);

    useEffect(() => {
        cargarRef.current = cargar;
    }, [cargar]);

    useEffect(() => {
        cargarRef.current();
    }, []);

    // Al abrir el dropdown, marca las incidencias como vistas (pone el contador en 0).
    const manejarApertura = useCallback(
        (abierto: boolean) => {
            if (!abierto || noVistas === 0 || marcando) {
                return;
            }

            setMarcando(true);
            submit(marcarVistasRoute())
                .then(() => setNoVistas(0))
                .finally(() => setMarcando(false));
        },
        [submit, marcarVistasRoute, noVistas, marcando],
    );

    return (
        <DropdownMenu onOpenChange={manejarApertura}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    data-test="notification-bell-button"
                >
                    <Bell className="size-5" />
                    {noVistas > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -top-1 -right-1 h-4 min-w-4 justify-center rounded-full px-1 text-[10px]"
                        >
                            {noVistas > 9 ? '9+' : noVistas}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-80" align="end" side="bottom">
                <DropdownMenuLabel>Incidencias recientes</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {items.length === 0 ? (
                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                        Sin incidencias recientes.
                    </p>
                ) : (
                    <div className="max-h-80 space-y-1 overflow-y-auto">
                        {items.map((item) => (
                            <div
                                key={item.id}
                                className="rounded-md px-2 py-2 text-sm hover:bg-accent"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-medium">
                                        {item.tipoLabel}
                                    </span>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {item.hora}
                                    </span>
                                </div>
                                {item.mensaje && (
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {item.mensaje}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
