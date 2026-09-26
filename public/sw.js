// Minimal service worker: makes the app installable. It deliberately does not
// cache pages — resumes are private and change on every edit — so every
// request goes to the network exactly as without a service worker.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});
