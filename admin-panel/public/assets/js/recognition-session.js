(() => {
  'use strict';
  const root = document.querySelector('[data-recognition-root]');
  if (!root) return;
  let erased = false;
  const erase = () => { erased = true; root.replaceChildren(); };
  document.addEventListener('visibilitychange', () => { if(document.hidden) erase(); else if(erased) location.reload(); });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if(event.persisted || erased) location.reload(); });
})();
