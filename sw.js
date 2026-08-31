const CACHE_NAME = 'pantera-v2';
const ASSETS_ESENCIALES = [
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png',
  '/loading.html'
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

// Para navegaciones (abrir la app / cambiar de pagina): si el servidor
// no responde rapido (por ejemplo, esta "dormido" en el plan gratis de
// Render), se muestra al instante la pantalla de carga con spinner en
// vez de dejar la pantalla en blanco/negra sin explicacion.
const TIMEOUT_MS = 2500;

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;

  if (e.request.mode === 'navigate') {
    e.respondWith(
      Promise.race([
        fetch(e.request),
        new Promise((resolve) => {
          setTimeout(() => resolve(null), TIMEOUT_MS);
        })
      ]).then((respuesta) => {
        if (respuesta) return respuesta;
        const destino = new URL(e.request.url).pathname;
        return caches.match('/loading.html').then((cacheada) => {
          if (cacheada) return cacheada;
          return fetch('/loading.html?destino=' + encodeURIComponent(destino));
        });
      }).catch(() => {
        return caches.match('/loading.html');
      })
    );
    return;
  }

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
