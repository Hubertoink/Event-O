(function () {
    function boot() {
        if (window.EventOFrontend && typeof window.EventOFrontend.boot === 'function') {
            window.EventOFrontend.boot();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
