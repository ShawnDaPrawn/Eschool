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

// One retained server form, presented as focused steps. Without JavaScript the
// complete form remains usable; advancing never publishes or changes a record.
(() => {
  'use strict';
  const form = document.querySelector('[data-disc-wizard]');
  if (!form) return;
  const stages = [...form.querySelectorAll('[data-disc-stage]')];
  const tabs = form.querySelector('[data-disc-steps]');
  const controls = form.querySelector('[data-disc-step-actions]');
  if (!stages.length || !tabs || !controls) return;
  let current = 0;
  const preview = () => {
    const target = form.querySelector('[data-disc-preview]');
    target.replaceChildren();
    for (const stage of stages.filter(stage => stage.dataset.discStage !== 'review')) {
      const heading = document.createElement('h3');
      heading.textContent = stage.querySelector('legend').textContent;
      target.append(heading);
      if (stage.dataset.discStage === 'rules') {
        for (const input of stage.querySelectorAll('input, select')) {
          if (!input.value) continue;
          const item = document.createElement('p');
          const label = stage.querySelector(`label[for="${input.id}"]`);
          item.textContent = `${label?.textContent ?? ''}: ${input.tagName === 'SELECT' ? input.selectedOptions[0]?.textContent : input.value}`;
          target.append(item);
        }
      } else {
        const list = document.createElement('ul');
        for (const row of stage.querySelectorAll('[data-disc-row]')) {
          const parts = [...row.querySelectorAll('input, select')].flatMap(input => input.tagName === 'SELECT' ? [...input.selectedOptions].map(option => option.textContent) : [input.value]).filter(Boolean);
          if (!parts.length) continue;
          const item = document.createElement('li'); item.textContent = parts.join(' · '); list.append(item);
        }
        target.append(list);
      }
    }
  };
  const show = index => {
    current = Math.max(0, Math.min(index, stages.length - 1));
    stages.forEach((stage, i) => { stage.hidden = i !== current; });
    tabs.querySelectorAll('[data-disc-go]').forEach(button => {
      if (button.dataset.discGo === stages[current].dataset.discStage) button.setAttribute('aria-current', 'step');
      else button.removeAttribute('aria-current');
    });
    controls.querySelector('[data-disc-prev]').disabled = current === 0;
    controls.querySelector('[data-disc-next]').hidden = current === stages.length - 1;
    if (stages[current].dataset.discStage === 'review') preview();
  };
  const validThrough = index => {
    for (let i = 0; i <= index; i++) {
      const invalid = [...stages[i].querySelectorAll('input, select, textarea')].find(input => !input.checkValidity());
      if (invalid) { show(i); invalid.reportValidity(); return false; }
    }
    return true;
  };
  tabs.hidden = false; controls.hidden = false; show(0);
  form.addEventListener('click', event => {
    if (event.target.closest('[data-disc-prev]')) show(current - 1);
    if (event.target.closest('[data-disc-next]') && validThrough(current)) show(current + 1);
    const go = event.target.closest('[data-disc-go]');
    if (go) {
      const next = stages.findIndex(stage => stage.dataset.discStage === go.dataset.discGo);
      if (next <= current || validThrough(next - 1)) show(next);
    }
  });
  // Reveal the first invalid step before the browser attempts to focus a field.
  form.addEventListener('invalid', event => {
    const index = stages.findIndex(stage => stage.contains(event.target));
    if (index >= 0 && stages[index].hidden) show(index);
  }, true);
})();
