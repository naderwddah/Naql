const CACHE_NAME = "driver-card-pwa-v1";

const FILES_TO_CACHE = [
  "/",
  "/index.html",

  // CSS
  "/assets/css/style.css",

  // JS
  "/assets/js/app.js",

  // Pages
  "/assets/pages/dashboard.html",
  "/assets/pages/card-template.html",

  // Fonts (أهمها)
  "/assets/fonts/Tajawal/Tajawal-Regular.ttf",
  "/assets/fonts/Tajawal/Tajawal-Bold.ttf",

  // PWA
  "/assets/PWA/manifest.json"
];

// Install
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(FILES_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      )
    )
  );
  self.clients.claim();
});

// Fetch
self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
