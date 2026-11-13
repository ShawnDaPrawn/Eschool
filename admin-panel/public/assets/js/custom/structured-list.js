(() => {
  'use strict';
  let uniqueId = 0;
  document.querySelectorAll('[data-structured-list]').forEach(host => {
    if (host.dataset.ready) return;
    const form = host.closest('form');
    const source = form?.querySelector(`[name="${host.dataset.source}"]`);
    if (!source) return;
    host.dataset.ready = 'true';
    const separator = host.dataset.separator === 'comma' ? ',' : '\n';
    const values = source.value.split(separator).map(value => value.trim()).filter(Boolean);
    const maximum = Number(host.dataset.maximum);
    const required = source.required;
    source.required = false;
    (source.closest('[data-page-field]') || source.closest('.m3-text-field') || source).hidden = true;
    const oldLabel = source.id && form.querySelector(`label[for="${source.id}"]`);
    if (oldLabel && !oldLabel.contains(source)) oldLabel.hidden = true;
    const title = document.createElement('h3'); title.textContent = host.dataset.title; host.append(title);
    const rows = document.createElement('div'); rows.className = 'panel-record-list'; host.append(rows);
    const add = document.createElement('button'); add.type = 'button'; add.className = 'm3-button m3-button--outlined'; add.textContent = host.dataset.addLabel; host.append(add);
    const sync = () => { source.value = values.join(separator); add.disabled = values.length >= maximum; };
    function render(focusLast = false) {
      rows.replaceChildren();
      values.forEach((value, index) => {
        const row = document.createElement('div'); row.className = 'panel-actions';
        const label = document.createElement('label'); label.className = 'm3-text-field';
        const input = document.createElement('input'); input.className = 'm3-text-field__input'; input.type = 'text'; input.placeholder = ' '; input.value = value; input.required = true; input.maxLength = Number(host.dataset.rowMaxlength);
        input.id = `structured-list-${++uniqueId}`; label.htmlFor = input.id;
        if (host.dataset.pattern) input.pattern = host.dataset.pattern;
        const caption = document.createElement('span'); caption.className = 'm3-text-field__label'; caption.textContent = `${host.dataset.rowLabel} ${index + 1}`;
        input.addEventListener('input', () => { values[index] = input.value; sync(); }); label.append(input, caption);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'm3-button m3-button--text'; remove.textContent = host.dataset.removeLabel;
        remove.disabled = required && values.length === 1;
        remove.addEventListener('click', () => { values.splice(index, 1); render(); sync(); }); row.append(label, remove); rows.append(row);
        if (focusLast && index === values.length - 1) input.focus();
      });
    }
    if (required && values.length === 0) values.push('');
    render(); sync();
    add.addEventListener('click', () => { if (values.length < maximum) { values.push(''); render(true); sync(); } });
    form.addEventListener('submit', sync);
  });
})();
