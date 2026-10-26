/* Shared dashboard interactions; page-specific charts may call RHCharts.create. */
(function (window, document) {
    'use strict';
    const RHCharts = {
        palette: ['#155e75','#14b8a6','#7c3aed','#f97316','#2563eb','#e11d48','#eab308'],
        create(id, type, data, options) {
            const el = document.getElementById(id);
            if (!el || !window.Chart) return null;
            const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const styles = getComputedStyle(document.documentElement);
            const color = styles.getPropertyValue('--bs-body-color').trim() || (dark ? '#e8eef2' : '#183044');
            const muted = styles.getPropertyValue('--bs-secondary-color').trim() || '#6b7c88';
            const defaults = { responsive:true, maintainAspectRatio:false, interaction:{intersect:false,mode:'index'}, plugins:{legend:{labels:{color,usePointStyle:true,padding:16}},tooltip:{backgroundColor:dark?'#10212c':'#183044',padding:12,cornerRadius:10}}, scales:{x:{grid:{display:false},ticks:{color:muted}},y:{beginAtZero:true,grid:{color:dark?'rgba(255,255,255,.08)':'rgba(24,48,68,.08)'},ticks:{color:muted}}} };
            const merged = Object.assign({}, defaults, options || {});
            try { return new window.Chart(el, {type, data, options:merged}); } catch (error) { console.error('RHCharts:', error); return null; }
        }
    };
    window.RHCharts = RHCharts;
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-auto-submit]').forEach(function (field) { field.addEventListener('change', function () { if (field.form) field.form.submit(); }); });
        document.querySelectorAll('[data-tooltip]').forEach(function (el) { el.setAttribute('title', el.getAttribute('data-tooltip')); });
    });
})(window, document);
