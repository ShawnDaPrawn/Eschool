(() => {
  'use strict';
  const pairing = document.querySelector('[data-payment-pairing]');
  if (!pairing) return;
  const erase = () => pairing.replaceChildren();
  document.addEventListener('visibilitychange', () => { if (document.hidden) erase(); });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
})();
