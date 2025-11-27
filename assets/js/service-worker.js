/**
 * Service Worker for Housekeeping PWA App
 *
 * Provides offline functionality and caching.
 *
 * @package Housekeeping_PWA_App
 */

const CACHE_NAME = 'hka-v1.0.1';
const urlsToCache = [
    '/',
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
 * Fetch event - network-first for app resources, cache-first for others.
 */
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Always fetch from network for AJAX and API calls
    if (event.request.url.includes('admin-ajax.php') ||
        event.request.url.includes('/wp-json/')) {
        return;
    }

    // Network-first strategy for app's own CSS/JS files
    const isAppResource = event.request.url.includes('/housekeeping-pwa-app/assets/');

    if (isAppResource) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Cache the new version
                    if (response && response.status === 200) {
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Fallback to cache if offline
                    return caches.match(event.request);
                })
        );
    } else {
        // Cache-first for everything else (fonts, images, etc.)
        event.respondWith(
            caches.match(event.request)
                .then(response => {
                    if (response) {
                        return response;
                    }

                    return fetch(event.request).then(response => {
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseToCache);
                        });

                        return response;
                    });
                })
                .catch(() => {
                    return caches.match('/offline.html');
                })
        );
    }
});

/**
 * Message event - handle commands from the app.
 */
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
