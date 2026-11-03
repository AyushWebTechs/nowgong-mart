const CACHE_NAME = 'nowgong-mart-static-v2';
const OFFLINE_URL = '/offline.html';
const STATIC_FILES = [
  OFFLINE_URL,
  '/icons/icon-192.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_FILES))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  const isPublicAsset = url.pathname.startsWith('/build/')
    || url.pathname.startsWith('/icons/');

  if (isPublicAsset) {
    event.respondWith(
      caches.match(request).then(cached =>
        cached || fetch(request).then(response => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
          }
          return response;
        })
      )
    );
  }
});
