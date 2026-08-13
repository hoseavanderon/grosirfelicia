(function () {
    if (!("serviceWorker" in navigator)) {
        return;
    }

    var scriptEl = document.currentScript;
    var swUrl = "/sw.js";

    if (scriptEl && scriptEl.src) {
        try {
            swUrl = new URL("../sw.js", scriptEl.src).href;
        } catch (e) {
            swUrl = "/sw.js";
        }
    }

    window.addEventListener("load", function () {
        navigator.serviceWorker.register(swUrl).catch(function () {
            // Fail silently — app must keep working without PWA.
        });
    });
})();
