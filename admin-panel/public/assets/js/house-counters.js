(() => {
  'use strict';
  const root = document.querySelector('[data-counter-url]');
  if (!root) return;
  let revision = Number(root.dataset.revision), generation = 0, active = true;
  const status = document.getElementById('counter-status');
  const erase = () => { active = false; generation++; root.replaceChildren(); if (status) status.textContent = 'Refresh this page to check your current access.'; };
  const refresh = async () => {
    if (!active || document.hidden) return;
    const epoch = ++generation;
    try {
      const url = new URL(root.dataset.counterUrl, location.origin);
      url.searchParams.set('session_year_id', root.dataset.year);
      const response = await fetch(url, {credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-School-Id':root.dataset.school,'X-Account-Id':root.dataset.account,'X-Requested-With':'XMLHttpRequest'}});
      if ([401,403,404,423].includes(response.status)) { erase(); return; }
      if (!response.ok) return;
      const data = (await response.json()).data;
      if (epoch !== generation || !active || document.hidden || data.session_year_id !== Number(root.dataset.year) || data.revision < revision) return;
      revision = data.revision;
      for (const house of data.items) { const node=root.querySelector(`[data-house="${house.id}"]`); if(node) node.textContent=String(house.points); }
    } catch (_) { /* The retained counter remains marked as last fetched. */ }
  };
  document.addEventListener('visibilitychange', () => { if(document.hidden) {generation++;} else refresh(); });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if(event.persisted) location.reload(); });
  window.setInterval(refresh,5000);
  document.addEventListener('navigateed:house-points-changed', refresh);
})();
