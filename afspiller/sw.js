/* SFB-afspiller · hjælpefil (service worker)
   Gemmer afspillerens egne filer på telefonen eller computeren, så den virker
   uden internet. Zip-filerne, man vælger, gemmes ikke her – de bliver, hvor de er.
   Ved en ny version tælles VERSION op, så den gamle kopi bliver skiftet ud. */
const VERSION = 'sfb-afspiller-0.7';
const FILER = ['./', './index.html', './manifest.webmanifest', './ikon-192.png', './ikon-512.png', './ikon-180.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(VERSION).then(c => c.addAll(FILER)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  // Gamle udgaver af afspilleren fjernes. Historierne i 'sfb-hylde' bliver.
  e.waitUntil(caches.keys()
    .then(navne => Promise.all(navne.filter(n => n !== VERSION && n !== 'sfb-hylde').map(n => caches.delete(n))))
    .then(() => self.clients.claim()));
});

// Afspillerens egne filer hentes fra nettet, når der er forbindelse, så rettelser
// kommer med. Uden forbindelse bruges den gemte kopi.
// Kun afspillerens egne filer håndteres her. Hylden (hylde.php og zip-filerne)
// går direkte til webhotellet, og hentede historier ligger i et lager for sig.
const EGNE = FILER.map(f => new URL(f, self.location.href).pathname);
self.addEventListener('fetch', e => {
  const req = e.request;
  const adr = new URL(req.url);
  if (req.method !== 'GET' || adr.origin !== self.location.origin || EGNE.indexOf(adr.pathname) === -1) return;
  e.respondWith(
    fetch(req).then(svar => {
      if (svar && svar.ok){ const kopi = svar.clone(); caches.open(VERSION).then(c => c.put(req, kopi)); }
      return svar;
    }).catch(() => caches.match(req, { ignoreSearch: true }).then(gemt => gemt || caches.match('./index.html')))
  );
});
