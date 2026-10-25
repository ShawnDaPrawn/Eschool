(() => {
  'use strict';
  const workbench = document.querySelector('.discipline-workbench');
  if (!workbench) return;
  let dirty = workbench.hasAttribute('data-unsaved-draft'), submitting = false;
  const refresh = () => {
    for (const [source, attribute] of [['sanctions', 'data-disc-sanction-choices'], ['levels', 'data-disc-level-choices']]) {
      const choices = [...workbench.querySelectorAll(`[data-disc-row="${source}"]`)].flatMap(row => {
        const code = row.querySelector('input[name$="[code]"]')?.value.trim();
        const name = row.querySelector('input[name$="[name]"]')?.value.trim();
        return code && name ? [[code, name]] : [];
      });
      for (const select of workbench.querySelectorAll(`[${attribute}]`)) {
        const selected = [...select.selectedOptions].map(option => option.value);
        select.replaceChildren(...choices.map(([code, name]) => {
          const option = document.createElement('option');
          option.value = code; option.textContent = name;
          option.selected = selected.includes(code);
          return option;
        }));
      }
    }
  };
  workbench.addEventListener('input', event => { if (event.target.closest('form[method="post"]')) { dirty = true; refresh(); } });
  workbench.addEventListener('change', event => { if (event.target.closest('form[method="post"]')) dirty = true; });
  workbench.addEventListener('click', event => {
    const add = event.target.closest('[data-disc-add]');
    if (add) {
      const kind = add.dataset.discAdd, body = workbench.querySelector(`[data-disc-body="${kind}"]`);
      const template = workbench.querySelector(`[data-disc-template="${kind}"]`);
      const next = Number(body.dataset.next);
      if (body.children.length >= Number(body.dataset.limit)) return;
      body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(next)));
      body.dataset.next = String(next + 1); dirty = true; refresh();
    }
    const remove = event.target.closest('[data-disc-remove]');
    if (remove) { remove.closest('tr').remove(); dirty = true; refresh(); }
  });
  workbench.addEventListener('submit', event => {
    if (event.target.method.toLowerCase() !== 'post') return;
    submitting = true;
    for (const button of event.target.querySelectorAll('button[type="submit"]')) button.disabled = true;
  });
  window.addEventListener('beforeunload', event => {
    if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
  });
})();
