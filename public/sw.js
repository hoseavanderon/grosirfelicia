/* Grosir Felicia PWA service worker — conservative caching only */
const CACHE_VERSION = "gf-static-v2";
const STATIC_CACHE = CACHE_VERSION;

const STATIC_PATH_PREFIXES = ["/css/", "/js/", "/icons/"];
const STATIC_EXTENSIONS = [
    ".css",
    ".js",
    ".png",
    ".jpg",
    ".jpeg",
    ".gif",
    ".svg",
    ".webp",
    ".ico",
    ".woff",
    ".woff2",
    ".ttf",
    ".map",
];

self.addEventListener("install", (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            );
            await self.clients.claim();
        })(),
    );
});

function isNonGet(request) {
    return request.method !== "GET" && request.method !== "HEAD";
}

function isNavigationRequest(request) {
    if (request.mode === "navigate") {
        return true;
    }

    const accept = request.headers.get("accept") || "";
    return accept.includes("text/html");
}

function isLivewireOrAdmin(url) {
    const path = url.pathname;

    if (path === "/livewire" || path.startsWith("/livewire/")) {
        return true;
    }

    if (path === "/admin" || path.startsWith("/admin/")) {
        return true;
    }

    return false;
}

function isSafeStaticAsset(url) {
    if (url.origin !== self.location.origin) {
        return false;
    }

    if (url.pathname.endsWith("/sw.js") || url.pathname === "/sw.js") {
        return false;
    }

    if (url.pathname === "/manifest.webmanifest") {
        return true;
    }

    for (const prefix of STATIC_PATH_PREFIXES) {
        if (url.pathname.startsWith(prefix)) {
            return true;
        }
    }

    const lowerPath = url.pathname.toLowerCase();
    return STATIC_EXTENSIONS.some((ext) => lowerPath.endsWith(ext));
}

self.addEventListener("fetch", (event) => {
    const { request } = event;

    // Never cache mutating requests.
    if (isNonGet(request)) {
        return;
    }

    let url;
    try {
        url = new URL(request.url);
    } catch (error) {
        return;
    }

    // Never cache HTML / document navigations.
    if (isNavigationRequest(request)) {
        return;
    }

    // Never cache Filament admin or Livewire traffic.
    if (isLivewireOrAdmin(url)) {
        return;
    }

    // Only optionally cache same-origin safe static assets.
    if (!isSafeStaticAsset(url)) {
        return;
    }

    event.respondWith(
        (async () => {
            const cache = await caches.open(STATIC_CACHE);
            const cached = await cache.match(request);
            const networkFetch = fetch(request)
                .then((response) => {
                    if (response && response.ok && response.type === "basic") {
                        cache.put(request, response.clone());
                    }

                    return response;
                })
                .catch(() => cached);

            // Serve cache immediately when present, then refresh in the background
            // so JS/CSS updates are not stuck on an old cache-first copy.
            return cached || networkFetch;
        })(),
    );
});
