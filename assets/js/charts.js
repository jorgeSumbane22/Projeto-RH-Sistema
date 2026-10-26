/* Shared chart helpers. Loaded globally so every dashboard can use the same visual language. */
(function (window) {
    'use strict';
    window.RHChartData = {
        numbers(values) { return Array.isArray(values) ? values.map(function (value) { return Number(value) || 0; }) : []; },
        money(value) { return 'MT ' + Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        monthLabel(value) { const parts = String(value).split('-'); return parts.length === 2 ? parts[1] + '/' + parts[0].slice(2) : value; }
    };
})(window);
