/**
 * Kayumanies Cake Shop - Service Worker
 * PWA Caching & Offline Support
 */

const CACHE_NAME = 'kayumanies-v2';
const DYNAMIC_CACHE = 'kayumanies-dynamic-v2';

// Assets to cache on install
const STATIC_ASSETS = [
    '/kayumanies/',
    '/kayumanies/index.php',
    '/kayumanies/manifest.json',
    '/kayumanies/assets/css/pembeli.css',
    '/kayumanies/assets/css/admin.css',
    '/kayumanies/assets/css/kasir.css',
    '/kayumanies/assets/js/pembeli.js',
    '/kayumanies/assets/images/icon-192x192.png',
    '/kayumanies/assets/images/icon-512x512.png',
    '/kayumanies/modules/auth/login.php',
    '/kayumanies/modules/auth/register.php',
    '/kayumanies/modules/pembeli/products.php',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];

// ==========================================
// INSTALL - Cache static assets
// ==========================================
self.addEventListener('install', function(event) {
    console.log('[SW] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(function() {
                console.log('[SW] Skip waiting');
                return self.skipWaiting();
            })
    );
});

// ==========================================
// ACTIVATE - Clean old caches
// ==========================================
self.addEventListener('activate', function(event) {
    console.log('[SW] Activating...');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME && cacheName !== DYNAMIC_CACHE) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            console.log('[SW] Claiming clients');
            return self.clients.claim();
        })
    );
});

// ==========================================
// FETCH - Network first, cache fallback
// ==========================================
self.addEventListener('fetch', function(event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;
    
    // Skip chrome-extension requests
    if (!event.request.url.startsWith('http')) return;
    
    // Skip API calls (always network)
    if (event.request.url.includes('/api/')) {
        return;
    }
    
    // Skip admin & kasir (always network for real-time data)
    if (event.request.url.includes('/admin/') || event.request.url.includes('/kasir/')) {
        return;
    }
    
    // For HTML pages: Network first, fallback to cache, then offline page
    if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(event.request)
                .then(function(response) {
                    // Cache the fresh response
                    var responseClone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(function(cache) {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                })
                .catch(function() {
                    // Try cache first
                    return caches.match(event.request)
                        .then(function(cachedResponse) {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            // Fallback to offline page
                            return caches.match('/kayumanies/offline.php');
                        });
                })
        );
        return;
    }
    
    // For static assets (CSS, JS, images): Cache first, network fallback
    if (event.request.url.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2|json)$/)) {
        event.respondWith(
            caches.match(event.request)
                .then(function(cachedResponse) {
                    if (cachedResponse) {
                        // Return cached, but update in background
                        fetch(event.request)
                            .then(function(response) {
                                caches.open(DYNAMIC_CACHE).then(function(cache) {
                                    cache.put(event.request, response);
                                });
                            })
                            .catch(function() {});
                        return cachedResponse;
                    }
                    // Not in cache, fetch from network
                    return fetch(event.request)
                        .then(function(response) {
                            var responseClone = response.clone();
                            caches.open(DYNAMIC_CACHE).then(function(cache) {
                                cache.put(event.request, responseClone);
                            });
                            return response;
                        });
                })
        );
        return;
    }
    
    // Default: Network first
    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                return response;
            })
            .catch(function() {
                return caches.match(event.request);
            })
    );
});

// ==========================================
// PUSH NOTIFICATION
// ==========================================
self.addEventListener('push', function(event) {
    var data = {};
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch(e) {
            data = {
                title: 'Kayumanies Cake Shop',
                body: event.data.text(),
                icon: '/kayumanies/assets/images/icon-192x192.png',
                badge: '/kayumanies/assets/images/icon-96x96.png'
            };
        }
    }
    
    var options = {
        body: data.body || 'Ada update baru!',
        icon: data.icon || '/kayumanies/assets/images/icon-192x192.png',
        badge: data.badge || '/kayumanies/assets/images/icon-96x96.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/kayumanies/',
            dateOfArrival: Date.now()
        },
        actions: data.actions || [
            {
                action: 'open',
                title: 'Lihat'
            },
            {
                action: 'close',
                title: 'Tutup'
            }
        ],
        tag: data.tag || 'kayumanies-notification',
        renotify: true,
        requireInteraction: data.requireInteraction || false
    };
    
    event.waitUntil(
        self.registration.showNotification(
            data.title || 'Kayumanies Cake Shop',
            options
        )
    );
});

// ==========================================
// NOTIFICATION CLICK
// ==========================================
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    var url = '/kayumanies/';
    
    if (event.notification.data && event.notification.data.url) {
        url = event.notification.data.url;
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function(windowClients) {
                // Check if there is already a window open
                for (var i = 0; i < windowClients.length; i++) {
                    var client = windowClients[i];
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// ==========================================
// BACKGROUND SYNC (for offline orders)
// ==========================================
self.addEventListener('sync', function(event) {
    if (event.tag === 'sync-cart') {
        event.waitUntil(syncCart());
    }
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOrders());
    }
});

function syncCart() {
    console.log('[SW] Background sync: cart');
    // Implement cart sync logic here
    return Promise.resolve();
}

function syncOrders() {
    console.log('[SW] Background sync: orders');
    // Implement orders sync logic here
    return Promise.resolve();
}

// ==========================================
// MESSAGE FROM CLIENT
// ==========================================
self.addEventListener('message', function(event) {
    if (event.data && event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.action === 'clearCache') {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
});