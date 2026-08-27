const CACHE_NAME = 'pantera-v1';
const ASSETS_ESENCIALES = [
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png'
];

self.addEventListener('install', (e) => {
  self.skipWaiting();
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_ESENCIALES))
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((nombres) =>
      Promise.all(
        nombres
          .filter((nombre) => nombre !== CACHE_NAME)
          .map((nombre) => caches.delete(nombre))
      )
    )
  );
  self.clients.claim();
});

// Estrategia: red primero, caché como respaldo (para no servir datos
// desactualizados de cotizaciones/pagos, pero seguir funcionando si
// se pierde la conexión momentáneamente).
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;

  e.respondWith(
    fetch(e.request)
      .then((respuesta) => {
        const copia = respuesta.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(e.request, copia));
        return respuesta;
      })
      .catch(() => caches.match(e.request))
  );
});
