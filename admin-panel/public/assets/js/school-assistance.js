(() => {
    'use strict';
    const root = document.querySelector('[data-assistance-private]');
    if (!root) return;
    let retired = false;
    const erase = () => {
        retired = true;
        root.querySelectorAll('input, textarea').forEach(field => { field.value = ''; });
        root.replaceChildren();
    };
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) erase();
        else if (retired) window.location.reload();
    });
    window.addEventListener('pagehide', erase);
    window.addEventListener('pageshow', event => {
        if (event.persisted || retired) window.location.reload();
    });
    const approval = document.getElementById('assistance-doc-reviewed');
    approval?.form?.addEventListener('input', event => {
        if (event.target !== approval && event.target.type !== 'hidden') approval.checked = false;
    });
    // A foreground page never retains a private message/review indefinitely.
    const expiry = Date.parse(root.dataset.assistanceExpires);
    const lease = Number.isFinite(expiry) ? Math.max(0, Math.min(15 * 60 * 1000, expiry - Date.now())) : 15 * 60 * 1000;
    window.setTimeout(() => { erase(); window.location.reload(); }, lease);
})();
