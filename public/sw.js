// Minimal service worker for PWA installability
// No offline caching — this is an online-only admin tool

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Intentionally do NOT call event.respondWith() — let the browser
    // handle every request natively. Re-fetching here would route the
    // request through the service worker's own context, where it is
    // governed by the CSP `connect-src` directive instead of
    // `script-src`/`style-src`. Since CSP is `connect-src 'self'`, that
    // breaks cross-origin assets (Google Fonts, the Alpine.js CDN).
    // An empty fetch handler still satisfies PWA installability.
});
