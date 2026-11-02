/* SFB-afspiller · hjælpefil (service worker)
   Gemmer afspillerens egne filer på telefonen eller computeren, så den virker
   uden internet. Zip-filerne, man vælger, gemmes ikke her – de bliver, hvor de er.
   Ved en ny version tælles VERSION op, så den gamle kopi bliver skiftet ud. */
const VERSION = 'sfb-afspiller-0.2';
const FILER = ['./', './index.html', './manifest.webmanifest', './ikon-192.png', './ikon-512.png', './ikon-180.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(VERSION).then(c => c.addAll(FILER)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys()
    .then(navne => Promise.all(navne.filter(n => n !== VERSION).map(n => caches.delete(n))))
    .then(() => self.clients.claim()));
});

// Afspillerens egne filer hentes fra nettet, når der er forbindelse, så rettelser
// kommer med. Uden forbindelse bruges den gemte kopi.
self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET' || new URL(req.url).origin !== self.location.origin) return;
  e.respondWith(
    fetch(req).then(svar => {
      if (svar && svar.ok){ const kopi = svar.clone(); caches.open(VERSION).then(c => c.put(req, kopi)); }
      return svar;
    }).catch(() => caches.match(req, { ignoreSearch: true }).then(gemt => gemt || caches.match('./index.html')))
  );
});
