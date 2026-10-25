'use strict';
(() => {
  const staff = document.getElementById('risk-professional');
  const revision = document.getElementById('risk-grant-revision');
  if (staff && revision) staff.addEventListener('change', () => {
    const value = staff.selectedOptions[0]?.dataset.revision || '0';
    revision.value = /^\d+$/.test(value) ? value : '0';
  });
})();
