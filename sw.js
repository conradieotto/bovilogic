/**
 * BoviLogic Service Worker
 * Cache-first for static assets, Network-first for API, offline fallback.
 */

const CACHE_NAME     = 'bovilogic-v1';
const OFFLINE_URL    = '/offline/offline.html';

const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/offline/offline.html',
  '/manifest.json',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
];

// ─── Install ──────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ─── Activate ─────────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// ─── Fetch ────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET for non-API (let through for background sync)
  if (event.request.method !== 'GET' && !url.pathname.startsWith('/api/')) {
    return;
  }

  // API calls: network-first, queue mutations offline
  if (url.pathname.startsWith('/api/')) {
    if (event.request.method === 'GET') {
      event.respondWith(networkFirstWithCache(event.request));
    }
    return;
  }

  // Static assets: cache-first
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(event.request));
    return;
  }

  // HTML pages: network-first with offline fallback
  event.respondWith(networkFirstWithFallback(event.request));
});

// ─── Background Sync ──────────────────────────────────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'bl-sync') {
    event.waitUntil(processSyncQueue());
  }
});

self.addEventListener('message', event => {
  if (event.data?.type === 'SYNC_NOW') {
    processSyncQueue();
  }
});

// ─── Strategy Helpers ─────────────────────────────────────────────────────────
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return caches.match(OFFLINE_URL);
  }
}

async function networkFirstWithCache(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || new Response(JSON.stringify({ success: false, message: 'Offline', data: [] }), {
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

async function networkFirstWithFallback(request) {
  try {
    const response = await fetch(request);
    return response;
  } catch {
    const cached = await caches.match(request);
    return cached || caches.match(OFFLINE_URL);
  }
}

function isStaticAsset(pathname) {
  return /\.(css|js|png|jpg|jpeg|svg|ico|woff2?)$/.test(pathname);
}

// ─── Sync Queue Processor ─────────────────────────────────────────────────────
async function processSyncQueue() {
  // This communicates with the main thread to process the IndexedDB queue
  const clients = await self.clients.matchAll({ type: 'window' });
  clients.forEach(client => {
    client.postMessage({ type: 'PROCESS_SYNC_QUEUE' });
  });
}
