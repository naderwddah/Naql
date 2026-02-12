const CACHE_NAME = "driver-card-pwa-fix-v1";
const FILES_TO_CACHE = [
  "./",
  "./index.html",
  "./manifest.json",
  
  // CSS (تأكد أن هذه الملفات موجودة في المسارات الصحيحة)
  "./assets/css/style.css",
  "./assets/css/login-style.css",
  
  // JS - تم تصحيح المسار هنا لأن الملف بجانب index.html
  "./app.js", 

  // الأيقونات
  "./assets/icons/icon-192.png",
  "./assets/icons/icon-512.png"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("Caching files...");
      // نستخدم الطريقة الآمنة لتجنب توقف العملية
      return Promise.all(
        FILES_TO_CACHE.map((file) => {
          return cache.add(file).catch((err) => {
            console.error("Failed to cache:", file);
          });
        })
      );
    })
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log("Removing old cache", key);
            return caches.delete(key);
          }
        })
      )
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});