'use strict';
(() => {
  const staff = document.getElementById('note-staff');
  const revision = document.getElementById('assignment-revision');
  if (!staff || !revision) return;
  staff.addEventListener('change', () => {
    const selected = staff.selectedOptions[0];
    const current = selected ? selected.dataset.revision : '0';
    revision.value = /^\d+$/.test(current || '') ? current : '0';
  });
})();
