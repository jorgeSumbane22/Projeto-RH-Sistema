/* Shared form validation and loading feedback. */
(function (document) {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
                const submit = form.querySelector('button[type="submit"]');
                if (submit && !submit.dataset.keepText) { submit.disabled = true; submit.dataset.originalText = submit.innerHTML; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>A processar...'; }
            });
        });
    });
})(document);
