(() => {
  'use strict';
  const root = document.querySelector('[data-wallet-payment-counter]');
  if (!root) return;
  const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
  const key = `vendor-wallet-payment:${root.dataset.school}:${root.dataset.actor}:${root.dataset.vendor}`;
  let pending = null, expiry = null, readerAbort = null, readerGeneration = 0, storageAvailable = true;
  try { pending = sessionStorage.getItem(key); } catch (_) { storageAvailable = false; }
  const outcome = root.querySelector('[data-wallet-payment-outcome]')?.dataset.walletPaymentOutcome;
  if (outcome && uuid.test(outcome) && outcome === pending) {
    try { sessionStorage.removeItem(key); pending = null; } catch (_) { }
  }
  const storageFailed = () => {
    const status = root.querySelector('[data-wallet-payment-recovery-status]');
    if (status) status.textContent = status.dataset.storageError;
    root.querySelectorAll('[data-wallet-payment-new] fieldset, [data-wallet-payment-confirm] button').forEach(el => {el.disabled = true;});
  };
  if (!storageAvailable) storageFailed();
  const recover = root.querySelector('[data-wallet-payment-recover]');
  if (pending && uuid.test(pending) && recover) {
    recover.hidden = false; recover.elements.operation_id.value = pending;
    root.querySelectorAll('[data-wallet-payment-new] fieldset, [data-wallet-payment-confirm] button').forEach(el => {el.disabled = true;});
  }
  root.querySelector('[data-wallet-payment-confirm]')?.addEventListener('submit', event => {
    const operation = event.currentTarget.elements.operation_id.value;
    if (!uuid.test(operation) || pending && pending !== operation) { event.preventDefault(); return; }
    try { sessionStorage.setItem(key, operation); pending = operation; }
    catch (_) { event.preventDefault(); storageFailed(); return; }
    event.currentTarget.querySelectorAll('button').forEach(button => {button.disabled = true;});
  });
  const review = root.querySelector('[data-wallet-payment-expires]');
  if (review) {
    const deadline = Date.parse(review.dataset.walletPaymentExpires);
    const erase = () => { root.replaceChildren(); location.replace(root.dataset.index); };
    expiry = setTimeout(erase, Number.isFinite(deadline) ? Math.max(0, deadline - Date.now()) : 0);
  }
  const nfc = root.querySelector('[data-wallet-payment-nfc]'), field = root.querySelector('[data-wallet-payment-tap]');
  const status = root.querySelector('[data-wallet-payment-nfc-status]');
  nfc?.addEventListener('click', async () => {
    field.value = ''; readerAbort?.abort();
    const generation = ++readerGeneration, controller = new AbortController();
    readerAbort = controller;
    const current = () => generation === readerGeneration && !controller.signal.aborted && !document.hidden && root.isConnected;
    if (!window.NDEFReader) { status.textContent = status.dataset.unavailable; return; }
    try {
      const reader = new NDEFReader();
      reader.addEventListener('reading', event => {
        if (!current()) return;
        for (const record of event.message.records) {
          if (record.recordType !== 'url') continue;
          try {
            const value = new TextDecoder('utf-8', {fatal: true}).decode(record.data);
            if (value.length > 1024) continue;
            field.value = value; status.textContent = status.dataset.ready;
            controller.abort(); break;
          } catch (_) { /* Ignore malformed records and keep waiting for a valid link. */ }
        }
      }, {signal: controller.signal});
      await reader.scan({signal: controller.signal});
    } catch (_) { if (current()) status.textContent = status.dataset.unavailable; }
  });
  const stop = () => { ++readerGeneration; readerAbort?.abort(); if (expiry) clearTimeout(expiry); };
  document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });
  window.addEventListener('pagehide', stop);
})();
