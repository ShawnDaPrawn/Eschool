(() => {
  'use strict';
  const draft = document.querySelector('[data-message-draft]');
  if (!draft) return;
  // The deferred script can arrive after a person has already edited the form.
  // Compare against the server-rendered values before enabling saved actions.
  const edited = () => Array.from(draft.elements).some(field => {
    if (field instanceof HTMLInputElement) {
      return ['checkbox', 'radio'].includes(field.type)
        ? field.checked !== field.defaultChecked
        : field.value !== field.defaultValue;
    }
    if (field instanceof HTMLTextAreaElement) return field.value !== field.defaultValue;
    if (field instanceof HTMLSelectElement) {
      const options = Array.from(field.options);
      const explicit = options.some(option => option.defaultSelected);
      return options.some((option, index) => option.selected !==
        (option.defaultSelected || (!explicit && !field.multiple && index === 0)));
    }
    return false;
  });
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
  if (draft.hasAttribute('data-restored-draft') || edited()) changed();
  draft.addEventListener('input', changed, {once: true});
  draft.addEventListener('change', changed, {once: true});
  draft.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', event => {
    if (!dirty) return;
    event.preventDefault(); event.returnValue = '';
  });
})();
