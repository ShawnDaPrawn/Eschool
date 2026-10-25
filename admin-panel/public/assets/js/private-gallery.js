(() => {
  'use strict';
  const root = document.querySelector('[data-private-gallery]');
  if (!root) return;
  const dialog = root.querySelector('[data-gallery-viewer]');
  const full = root.querySelector('[data-gallery-full]');
  root.addEventListener('click', event => {
    const button = event.target.closest('[data-gallery-open]');
    if (!button || !dialog || !full) return;
    full.src = button.dataset.galleryOpen;
    dialog.showModal();
  });
  dialog?.addEventListener('close', () => full?.removeAttribute('src'));
  const erase = () => {
    dialog?.close();
    for (const node of root.querySelectorAll('img')) node.removeAttribute('src');
    root.replaceChildren();
  };
  let hidden = false;
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { hidden = true; erase(); }
    else if (hidden) location.reload();
  });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
  root.querySelector('[data-gallery-error]')?.focus();
})();
