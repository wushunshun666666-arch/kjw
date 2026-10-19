(function () {
    'use strict';

    function initBootstrapComponents(root) {
        if (typeof bootstrap === 'undefined') {
            return;
        }

        var scope = root || document;
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
        scope.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (element) {
            bootstrap.Popover.getOrCreateInstance(element);
        });
    }

    window.initBootstrapComponents = initBootstrapComponents;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initBootstrapComponents(document);
        });
    } else {
        initBootstrapComponents(document);
    }
}());
