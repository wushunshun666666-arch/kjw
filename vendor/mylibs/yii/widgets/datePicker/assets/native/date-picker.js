(function (window, document) {
    'use strict';

    function normalizeDate(value) {
        if (!value) {
            return '';
        }
        if (value === '%y-%M-%d') {
            var today = new Date();
            var month = String(today.getMonth() + 1).padStart(2, '0');
            var day = String(today.getDate()).padStart(2, '0');
            return today.getFullYear() + '-' + month + '-' + day;
        }
        return String(value).substring(0, 10);
    }

    function syncRange(container) {
        var start = container.querySelector('[data-date-range-part="start"]');
        var end = container.querySelector('[data-date-range-part="end"]');
        var target = document.getElementById(container.getAttribute('data-target'));
        if (!start || !end || !target) {
            return;
        }

        var minDate = normalizeDate(container.getAttribute('data-min-date'));
        var maxDate = normalizeDate(container.getAttribute('data-max-date'));
        start.min = minDate;
        start.max = end.value || maxDate;
        end.min = start.value || minDate;
        end.max = maxDate;
        target.value = start.value + '/' + end.value;
        target.dispatchEvent(new Event('change', {bubbles: true}));
    }

    document.addEventListener('click', function (event) {
        var input = event.target.closest('input[data-native-date-picker="1"]');
        if (!input || typeof input.showPicker !== 'function') {
            return;
        }
        try {
            input.showPicker();
        } catch (error) {
            // The browser still provides its normal date-input interaction.
        }
    });

    document.addEventListener('change', function (event) {
        var input = event.target.closest('[data-date-range-part]');
        if (!input) {
            return;
        }
        var container = input.closest('[data-date-range-picker="1"]');
        if (container) {
            syncRange(container);
        }
    });
})(window, document);
