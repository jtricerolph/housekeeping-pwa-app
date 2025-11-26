/**
 * Service Worker for Housekeeping PWA App
 *
 * Provides offline functionality and caching.
 *
 * @package Housekeeping_PWA_App
 */

const CACHE_NAME = 'hka-v1.0.0';
const urlsToCache = [
    '/',
    '/wp-content/plugins/housekeeping-pwa-app/assets/css/app.css',
    '/wp-content/plugins/housekeeping-pwa-app/assets/js/app.js',
    '/wp-includes/js/jquery/jquery.min.js'
];

/**
 * Install event - cache assets.
 */
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch(err => {
                console.error('Cache install failed:', err);
            })
    );
    self.skipWaiting();
});

/**
 * Activate event - clean up old caches.
 */
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

/**
 * Fetch event - serve from cache, fallback to network.
 */
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip admin-ajax.php (always fetch from network)
    if (event.request.url.includes('admin-ajax.php')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }

                return fetch(event.request).then(response => {
                    // Don't cache non-successful responses
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone response for caching
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                });
            })
            .catch(() => {
                // Return offline page if available
                return caches.match('/offline.html');
            })
    );
});

/**
 * Message event - handle commands from the app.
 */
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
