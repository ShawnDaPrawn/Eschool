(() => {
  'use strict';
  const root = document.querySelector('[data-ticket-admission-counter]');
  if (!root) return;
  const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i;
  const key = `vendor-ticket-admission:${root.dataset.school}:${root.dataset.actor}:${root.dataset.vendor}`;
  const recover = root.querySelector('[data-ticket-recover]');
  let pending = null, available = true;
  const blockNew = () => root.querySelectorAll('[data-ticket-new] fieldset, [data-ticket-confirm] button').forEach(node => { node.disabled = true; });
  const storageFailed = () => {
    blockNew();
    const status = root.querySelector('[data-ticket-recovery-status]');
    status.textContent = status.dataset.storageError;
  };
  try { pending = sessionStorage.getItem(key); } catch (_) { available = false; storageFailed(); }
  if (pending && !uuid.test(pending)) { available = false; storageFailed(); }
  const outcome = root.querySelector('[data-ticket-outcome]')?.dataset.ticketOutcome;
  if (outcome && uuid.test(outcome) && outcome === pending) {
    try { sessionStorage.removeItem(key); pending = null; } catch (_) { available = false; storageFailed(); }
  }
  if (pending && uuid.test(pending)) {
    blockNew(); recover.hidden = false; recover.elements.operation_id.value = pending;
  }
  root.querySelector('[data-ticket-confirm]')?.addEventListener('submit', event => {
    const operation = event.currentTarget.elements.operation_id.value;
    if (!available || !uuid.test(operation) || pending && pending !== operation) { event.preventDefault(); return; }
    try { sessionStorage.setItem(key, operation); pending = operation; }
    catch (_) { event.preventDefault(); available = false; storageFailed(); return; }
    event.currentTarget.querySelectorAll('button').forEach(node => { node.disabled = true; });
  });
  const number = root.querySelector('[name="ticket_number"]'), qr = root.querySelector('[name="qr_token"]');
  number?.addEventListener('input', () => { if (number.value && qr) qr.value = ''; });
  qr?.addEventListener('input', () => { if (qr.value && number) number.value = ''; });
})();
