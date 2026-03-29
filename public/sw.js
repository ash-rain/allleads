const CACHE_NAME = 'allleads-v1';
const OFFLINE_URL = '/offline';

// Assets to pre-cache on install
const PRE_CACHE = [
    '/offline',
    '/manifest.json',
];

// ─── Install ─────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRE_CACHE))
    );
    self.skipWaiting();
});

// ─── Activate ────────────────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// ─── Fetch ───────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    // Only handle GET requests; skip non-http(s) and cross-origin requests.
    if (
        event.request.method !== 'GET' ||
        !event.request.url.startsWith(self.location.origin)
    ) {
        return;
    }

    // Navigation requests: network-first, fall back to /offline.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() =>
                caches.match(OFFLINE_URL)
            )
        );
        return;
    }

    // Static assets (JS/CSS/images): cache-first.
    if (
        event.request.destination === 'script' ||
        event.request.destination === 'style' ||
        event.request.destination === 'image' ||
        event.request.destination === 'font'
    ) {
        event.respondWith(
            caches.match(event.request).then(
                (cached) =>
                    cached ||
                    fetch(event.request).then((response) => {
                        if (response && response.status === 200) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) =>
                                cache.put(event.request, clone)
                            );
                        }
                        return response;
                    })
            )
        );
    }
});
