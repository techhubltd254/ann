/**
 * KICC 3D Platform — Service Worker (PWA offline support).
 *
 * Strategy:
 *   - App shell (3D pages, viewer JS, manifest): precached, stale-while-revalidate
 *   - CDN libraries (jsdelivr three.js/hls.js): stale-while-revalidate
 *   - HLS playlists (.m3u8): network-only (must always be fresh)
 *   - Video segments & media files (.ts/.m4s/.mp4/...): network-only passthrough
 *     (too large to cache safely; also avoids breaking Range requests)
 *   - Everything else same-origin GET: network-first with cache fallback
 */

const VERSION = 'kicc-v1';
const SHELL_CACHE = `${VERSION}-shell`;
const RUNTIME_CACHE = `${VERSION}-runtime`;

const PRECACHE = [
  '/manifest.json',
  '/3d/player.html',
  '/3d/mobile_3d_viewer.js',
  '/3d/index.html',
  '/3d/kenya_3d_map.html',
  '/3d/booth_viewer.html',
  '/3d/sector_map.html',
];

const CDN_HOSTS = ['cdn.jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com'];
const MEDIA_EXT = /\.(ts|m4s|mp4|webm|mov|m4v|mp3|aac|ogg|wav)(\?|$)/i;
const PLAYLIST_EXT = /\.m3u8(\?|$)/i;

/* ── Install: precache app shell (tolerant of individual failures) ─────── */
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE)
      .then((cache) => Promise.allSettled(PRECACHE.map((url) => cache.add(url))))
      .then(() => self.skipWaiting())
  );
});

/* ── Activate: drop old caches ─────────────────────────────────────────── */
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

/* ── Fetch ─────────────────────────────────────────────────────────────── */
self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Only handle GET; never interfere with Range (video seek) requests
  if (req.method !== 'GET' || req.headers.has('range')) return;

  const url = new URL(req.url);

  // HLS playlists must be fresh — network only
  if (PLAYLIST_EXT.test(url.pathname)) return;

  // Media segments/files: passthrough, no caching
  if (MEDIA_EXT.test(url.pathname)) return;

  // CDN libraries: stale-while-revalidate
  if (CDN_HOSTS.includes(url.hostname)) {
    event.respondWith(staleWhileRevalidate(req, RUNTIME_CACHE));
    return;
  }

  // Same-origin
  if (url.origin === self.location.origin) {
    // Navigations & shell assets: stale-while-revalidate from shell cache
    if (req.mode === 'navigate' || PRECACHE.includes(url.pathname)) {
      event.respondWith(staleWhileRevalidate(req, SHELL_CACHE));
      return;
    }
    // Other same-origin static assets: network-first, cache fallback
    event.respondWith(networkFirst(req, RUNTIME_CACHE));
  }
});

/* ── Strategies ────────────────────────────────────────────────────────── */
async function staleWhileRevalidate(req, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(req);
  const network = fetch(req)
    .then((res) => {
      if (res.ok) cache.put(req, res.clone());
      return res;
    })
    .catch(() => null);
  return cached || (await network) || Response.error();
}

async function networkFirst(req, cacheName) {
  const cache = await caches.open(cacheName);
  try {
    const res = await fetch(req);
    if (res.ok) cache.put(req, res.clone());
    return res;
  } catch (e) {
    const cached = await cache.match(req);
    return cached || Response.error();
  }
}
