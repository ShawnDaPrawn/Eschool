'use strict';
(() => {
    const input = document.getElementById('application-link-token');
    const form = document.getElementById('application-link-form');
    document.querySelectorAll('.m3-file-field').forEach((field) => {
        const file = field.querySelector('input[type=file]');
        const browse = field.querySelector('.file-upload-browse');
        const label = field.querySelector('.file-upload-info');
        if (!file || !browse || !label) return;
        browse.addEventListener('click', () => file.click());
        file.addEventListener('change', () => { label.value = file.files?.[0]?.name || ''; });
    });
    if (!input || !form) return;
    const token = window.location.hash.slice(1);
    // Clear before any action; the secret never enters a query string or storage.
    if (window.location.hash) window.history.replaceState(null, '', window.location.pathname);
    if (/^[a-f0-9]{64}$/.test(token)) {
        input.value = token;
        form.hidden = false;
    }
})();
