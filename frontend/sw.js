// Registration Status Check Service Worker

const CACHE_NAME = 'sanfan-registration-status-v1';
const ASSETS_TO_CACHE = [
  '/school-management/frontend/check_registration.html',
  '/school-management/frontend/assets/css/styles.css',
  '/school-management/frontend/assets/css/registration-status.css',
  '/school-management/frontend/assets/js/registration-status.js',
  '/school-management/frontend/assets/js/tailwind-config.js',
  '/school-management/frontend/manifest.json',
  '/school-management/img/logo.png',
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'
];

// Install service worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service worker caching assets');
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
});

// Activate service worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service worker deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  
  // For API requests, try network first then fallback to offline message
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return new Response(
            JSON.stringify({
              error: 'You appear to be offline. Please check your internet connection and try again.'
            }),
            {
              headers: { 'Content-Type': 'application/json' }
            }
          );
        })
    );
    return;
  }

  // For other requests, try cache first then fallback to network
  event.respondWith(
    caches.match(event.request)
      .then((cachedResponse) => {
        // Return cached response if found
        if (cachedResponse) {
          return cachedResponse;
        }

        // Not in cache, fetch from network
        return fetch(event.request)
          .then((response) => {
            // Don't cache if response is not ok
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            // Add to cache for future
            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(event.request, responseToCache);
              });

            return response;
          })
          .catch(() => {
            // Fallback for HTML pages (show offline page)
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/school-management/frontend/offline.html');
            }
          });
      })
  );
});
