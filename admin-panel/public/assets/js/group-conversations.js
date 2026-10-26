(() => {
  'use strict';
  const root = document.querySelector('[data-group-conversations]');
  if (!root) return;
  let retired = false;
  let pending = false;
  let validUntil = performance.now() + 30000;
  let request;
  const retire = () => {
    if (retired) return;
    retired = true;
    request?.abort();
    const content = root.querySelector('[data-private-chat-content]');
    content?.querySelectorAll('input,textarea,select').forEach(field => { field.value = ''; });
    content?.replaceChildren();
    root.querySelector('[data-chat-retired]').hidden = false;
  };
  const check = async () => {
    if (retired || pending || !root.dataset.leaseUrl) return;
    if (document.hidden || performance.now() >= validUntil) { retire(); return; }
    pending = true;
    request = new AbortController();
    try {
      const response = await fetch(root.dataset.leaseUrl, {credentials: 'same-origin', cache: 'no-store', redirect: 'error', referrerPolicy: 'same-origin', headers: {Accept: 'application/json'}, signal: request.signal});
      if (!response.ok || (await response.json()).available !== true || document.hidden || performance.now() >= validUntil) { retire(); return; }
      validUntil = performance.now() + 30000;
    } catch (_) { retire(); }
    finally { pending = false; }
  };
  document.addEventListener('visibilitychange', () => { if (document.hidden) retire(); });
  window.addEventListener('pagehide', retire);
  window.addEventListener('pageshow', event => { if (event.persisted) retire(); });
  window.addEventListener('focus', check);
  setInterval(() => { if (performance.now() >= validUntil) retire(); else check(); }, 15000);
})();
