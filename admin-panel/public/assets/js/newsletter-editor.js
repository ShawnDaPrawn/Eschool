(() => {
  'use strict';
  const editor = document.querySelector('[data-newsletter-editor]');
  if (!editor) return;
  const form = editor.closest('form');
  const markdown = form.querySelector('[name="markdown"]');
  const rows = editor.querySelector('[data-newsletter-sections]');
  const template = editor.querySelector('[data-newsletter-template]');
  const renumber = () => Array.from(rows.children).forEach((row, index) => {
    row.querySelector('[data-section-legend]').textContent = `${editor.dataset.sectionLabel} ${index + 1}`;
    row.querySelector('[data-section-up]').disabled = index === 0;
    row.querySelector('[data-section-down]').disabled = index === rows.children.length - 1;
  });
  const sync = () => {
    markdown.value = Array.from(rows.children).map(row => {
      const heading = row.querySelector('[data-section-heading]').value.trim();
      const body = row.querySelector('[data-section-body]').value;
      return (heading ? `## ${heading}\n\n` : '') + body;
    }).join('\n\n');
    markdown.dispatchEvent(new Event('input', {bubbles: true}));
    renumber();
  };
  const add = (heading = '', body = '') => {
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelector('[data-section-heading]').value = heading;
    row.querySelector('[data-section-body]').value = body;
    rows.append(row); renumber(); return row;
  };
  const rebuild = () => {
    rows.replaceChildren();
    const parts = markdown.value.split(/^(?=## )/m);
    parts.forEach(part => {
      const match = part.match(/^## ([^\n]*)\n(?:\n)?([\s\S]*)$/);
      if (match) add(match[1], match[2].replace(/\n\n$/, ''));
      else if (part || parts.length === 1) add('', part.replace(/\n\n$/, ''));
    });
    if (!rows.children.length) add();
  };
  rebuild();
  rows.addEventListener('input', sync);
  editor.querySelector('[data-newsletter-add]').addEventListener('click', () => { const row = add(); sync(); row.querySelector('input').focus(); });
  editor.querySelector('[data-newsletter-rebuild]').addEventListener('click', rebuild);
  rows.addEventListener('click', async event => {
    const button = event.target.closest('button');
    if (!button) return;
    const row = button.closest('[data-newsletter-section]');
    if (button.hasAttribute('data-section-up') && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
    if (button.hasAttribute('data-section-down') && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
    if (button.hasAttribute('data-section-remove')) {
      // Do not use the generic data-confirm handler as well as this async one.
      if (!await window.pageConfirm({message:button.dataset.removeConfirm})) return;
      row.remove(); if (!rows.children.length) add();
    }
    sync();
  });
})();
