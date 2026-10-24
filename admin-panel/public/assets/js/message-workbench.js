(() => {
  'use strict';
  const draft = document.querySelector('[data-message-draft]');
  if (!draft) return;
  let dirty = false;
  const changed = () => {
    dirty = true;
    document.querySelectorAll('[data-message-saved-action]').forEach(action => {
      if (action instanceof HTMLButtonElement) action.disabled = true;
      action.setAttribute('aria-disabled', 'true');
      action.addEventListener('click', event => event.preventDefault(), {once: true});
    });
    const status = document.querySelector('[data-message-dirty-status]');
    if (status) status.hidden = false;
  };
  if (draft.hasAttribute('data-restored-draft')) changed();
  draft.addEventListener('input', changed, {once: true});
  draft.addEventListener('change', changed, {once: true});
  draft.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', event => {
    if (!dirty) return;
    event.preventDefault(); event.returnValue = '';
  });
})();
