(() => {
    'use strict';
    const form = document.querySelector('[data-platform-password]');
    if (!form) return;
    let active = true;
    let pending;
    const retire = () => {
        active = false; pending?.abort(); form.reset();
        form.querySelector('button[type=submit]').disabled = true;
        document.querySelector('[data-password-retired]').hidden = false;
    };
    document.addEventListener('visibilitychange', () => { if (document.hidden) retire(); });
    window.addEventListener('pagehide', retire);
    window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!active || pending || !form.reportValidity()) return;
        const button = form.querySelector('button[type=submit]');
        const error = form.querySelector('[data-password-error]');
        button.disabled = true; error.hidden = true;
        pending = new AbortController();
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin', signal: pending.signal,
                headers: { Accept: 'application/json' }, body: new FormData(form),
            });
            const result = await response.json();
            if (!active) return;
            if (!response.ok || result.error !== false) { error.hidden = false; return; }
            form.reset();
            window.location.replace(form.dataset.successUrl);
        } catch (_) {
            if (active) error.hidden = false;
        } finally {
            pending = undefined;
            if (active) button.disabled = false;
        }
    });
})();
