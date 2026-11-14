(() => {
  'use strict';
  const form = document.getElementById('student-registration-form');
  if (!form) return;
  // Select2 replaces the native control. Preserve the explicit school/guardian
  // field labels on its interactive proxy, including newly created searches.
  const labelSearches = () => {
    form.querySelectorAll('select[aria-label]').forEach(select => {
      const proxy = select.parentElement.querySelector('.select2-selection');
      if (proxy) {
        proxy.setAttribute('aria-label', select.getAttribute('aria-label'));
        const rendered = proxy.querySelector('.select2-selection__rendered');
        if (rendered) rendered.setAttribute('aria-label', select.getAttribute('aria-label'));
      }
    });
  };
  labelSearches();
  new MutationObserver(labelSearches).observe(form, { childList: true, subtree: true });
})();
