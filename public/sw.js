// Minimal service worker for PWA installability
// No offline caching — this is an online-only admin tool

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Pass through all requests to the network
    event.respondWith(fetch(event.request));
});
