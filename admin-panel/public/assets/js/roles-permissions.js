(() => {
  'use strict';
  document.querySelectorAll('[data-permission-picker]').forEach(root => {
    const groups = [...root.querySelectorAll('[data-permission-group]')];
    const all = root.querySelector('[data-permission-select-all]');
    const sync = () => {
      groups.forEach(group => {
        const items = [...group.querySelectorAll('[data-permission-child]')];
        const count = items.filter(item => item.checked).length;
        const parent = group.querySelector('[data-permission-group-toggle]');
        parent.checked = count === items.length; parent.indeterminate = count > 0 && count < items.length;
        group.querySelector('[data-permission-group-count]').textContent = String(count);
      });
      const items = [...root.querySelectorAll('[data-permission-child]')];
      const count = items.filter(item => item.checked).length;
      all.checked = count === items.length; all.indeterminate = count > 0 && count < items.length;
      root.querySelector('[data-permission-selected-count]').textContent = String(count);
    };
    root.addEventListener('change', event => {
      if (event.target === all) root.querySelectorAll('[data-permission-child]').forEach(item => { item.checked = all.checked; });
      else if (event.target.matches('[data-permission-group-toggle]')) event.target.closest('[data-permission-group]').querySelectorAll('[data-permission-child]').forEach(item => { item.checked = event.target.checked; });
      sync();
    });
    root.querySelector('[data-permission-toggle-all]').addEventListener('click', event => {
      const button = event.currentTarget; const open = button.getAttribute('aria-expanded') !== 'true';
      groups.forEach(group => { group.open = open; });
      button.setAttribute('aria-expanded', String(open));
      button.querySelector('[data-permission-toggle-label]').textContent = open ? button.dataset.labelCollapse : button.dataset.labelExpand;
    });
    sync();
  });
})();
