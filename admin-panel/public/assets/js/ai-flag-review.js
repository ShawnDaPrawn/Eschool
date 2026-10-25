(() => {
  'use strict';
  const root = document.querySelector('[data-ai-private-review]');
  if (!root) return;
  let hidden = false;
  let expiryTimer;
  const erase = () => {
    clearTimeout(expiryTimer);
    root.querySelectorAll('img').forEach(image => image.removeAttribute('src'));
    root.querySelectorAll('input,textarea,select').forEach(field => { field.value = ''; });
    root.replaceChildren();
  };
  const expiry = Date.parse(root.dataset.expiresAt || '');
  const scheduleExpiry = () => {
    if (!Number.isFinite(expiry)) return;
    const remaining = expiry - Date.now();
    if (remaining <= 0) erase();
    else expiryTimer = setTimeout(scheduleExpiry, Math.min(remaining, 2147483647));
  };
  scheduleExpiry();
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { hidden = true; erase(); }
    else if (hidden) location.reload();
  });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
})();
