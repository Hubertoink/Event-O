(function () {
    var frontend = window.EventOFrontend = window.EventOFrontend || {};

    if (!frontend._inits) {
        frontend._inits = [];
    }

    frontend.registerInit = function (init) {
        if (typeof init === 'function') {
            frontend._inits.push(init);
        }
    };

    frontend.boot = function () {
        if (frontend._booted) {
            return;
        }

        frontend._booted = true;

        frontend._inits.forEach(function (init) {
            init();
        });
    };
})();
