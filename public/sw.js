importScripts('/js/wirechat/sw.js');

const CACHE_PREFIX = "3rdvn-crm-pwa-";

self.addEventListener("install", (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith(CACHE_PREFIX)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("push", (event) => {
  let payload = {};

  try {
    payload = event.data?.json() || {};
  } catch (error) {
    payload = { title: "3RDVN CRM", body: event.data?.text() || "Bạn có thông báo mới." };
  }

  event.waitUntil(
    self.registration.showNotification(payload.title || "3RDVN CRM", {
      body: payload.body || "Bạn có thông báo mới.",
      icon: "/icons/3rdvn-icon-192.png",
      badge: "/icons/3rdvn-icon-192.png",
      tag: payload.tag || `3rdvn-crm-${Date.now()}`,
      renotify: true,
      vibrate: [180, 90, 180],
      data: { url: payload.url || "/" },
    })
  );
});

self.addEventListener("notificationclick", (event) => {
  if (!event.notification.tag?.startsWith("3rdvn-crm-")) {
    return;
  }

  event.notification.close();
  const targetUrl = new URL(event.notification.data?.url || "/", self.location.origin).href;

  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((clients) => {
      const sameOriginClient = clients.find((client) => new URL(client.url).origin === self.location.origin);

      if (sameOriginClient) {
        return sameOriginClient.focus().then((client) => client.navigate(targetUrl));
      }

      return self.clients.openWindow(targetUrl);
    })
  );
});
