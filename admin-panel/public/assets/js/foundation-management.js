(() => {
  'use strict';
  const surface = document.querySelector('[data-foundation-management]');
  if (!surface) return;
  const returnUrl = surface.dataset.returnUrl;
  let retired = false;
  const retire = () => {
    if (retired) return;
    retired = true;
    surface.querySelectorAll('input,textarea,select').forEach((control) => {
      control.value = '';
      control.disabled = true;
    });
    surface.querySelectorAll('[data-foundation-secret]').forEach((node) => { node.textContent = ''; });
    surface.replaceChildren();
  };
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) retire();
    else if (retired && returnUrl) window.location.replace(returnUrl);
  });
  window.addEventListener('pagehide', retire);
  window.addEventListener('pageshow', (event) => {
    if (event.persisted && returnUrl) { retire(); window.location.replace(returnUrl); }
  });
  const expiry = Date.parse(surface.dataset.expiresAt || '');
  const delay = Math.max(0, Math.min(15 * 60 * 1000, Number.isFinite(expiry) ? expiry - Date.now() : 15 * 60 * 1000));
  window.setTimeout(() => { retire(); if (returnUrl) window.location.replace(returnUrl); }, delay);
})();
