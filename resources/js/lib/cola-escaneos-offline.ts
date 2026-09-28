// Cola local (IndexedDB) de escaneos capturados sin conexión, para Eventos
// Académicos y Graduación: el kiosco puede seguir leyendo credenciales
// aunque no haya internet, y los envía al servidor en cuanto vuelve la
// conexión. No se usa en Biblioteca.

const NOMBRE_BASE_DATOS = 'campuspass-offline';
const VERSION_BASE_DATOS = 1;
const ALMACEN = 'escaneos-pendientes';

export type EscaneoPendiente = {
    id: string;
    url: string;
    codigo: string;
    capturadoEn: string;
};

function abrirBaseDatos(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        if (typeof indexedDB === 'undefined') {
            reject(new Error('IndexedDB no está disponible en este entorno.'));

            return;
        }

        const solicitud = indexedDB.open(NOMBRE_BASE_DATOS, VERSION_BASE_DATOS);

        solicitud.onupgradeneeded = () => {
            const db = solicitud.result;

            if (!db.objectStoreNames.contains(ALMACEN)) {
                const almacen = db.createObjectStore(ALMACEN, {
                    keyPath: 'id',
                });
                almacen.createIndex('url', 'url', { unique: false });
            }
        };

        solicitud.onsuccess = () => resolve(solicitud.result);
        solicitud.onerror = () => reject(solicitud.error);
    });
}

export async function agregarEscaneoPendiente(
    item: EscaneoPendiente,
): Promise<void> {
    const db = await abrirBaseDatos();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(ALMACEN, 'readwrite');
        tx.objectStore(ALMACEN).add(item);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

export async function listarEscaneosPendientes(
    url: string,
): Promise<EscaneoPendiente[]> {
    const db = await abrirBaseDatos();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(ALMACEN, 'readonly');
        const solicitud = tx.objectStore(ALMACEN).index('url').getAll(url);

        solicitud.onsuccess = () => {
            const items = (solicitud.result as EscaneoPendiente[]).sort(
                (a, b) => a.capturadoEn.localeCompare(b.capturadoEn),
            );
            resolve(items);
        };
        solicitud.onerror = () => reject(solicitud.error);
    });
}

export async function eliminarEscaneoPendiente(id: string): Promise<void> {
    const db = await abrirBaseDatos();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(ALMACEN, 'readwrite');
        tx.objectStore(ALMACEN).delete(id);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

export async function contarEscaneosPendientes(url: string): Promise<number> {
    return (await listarEscaneosPendientes(url)).length;
}
