(() => {
  const root = document.querySelector('[data-encoding-private]');
  if (!root) return;
  const started = performance.now(), requests = new Set();
  let retired = false;
  const retire = () => {
    if (retired) return; retired = true;
    requests.forEach(request => request.abort()); requests.clear();
    root.querySelectorAll('input,textarea,select').forEach(field => { field.value = ''; });
    root.replaceChildren(); document.querySelector('[data-encoding-retired]').hidden = false;
  };
  const verify = async () => {
    if (retired) return;
    if (document.visibilityState !== 'visible' || performance.now() < started || performance.now() - started >= 30000) { retire(); return; }
    const controller = new AbortController(); requests.add(controller);
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
      const response = await fetch(root.dataset.leaseUrl, {credentials: 'same-origin', cache: 'no-store', referrerPolicy: 'same-origin', signal: controller.signal, headers: {Accept: 'application/json', 'X-Encoding-Lease': '1'}});
      if (!response.ok || (await response.json()).active !== true) retire();
    } catch (_) { retire(); }
    finally { clearTimeout(timeout); requests.delete(controller); }
  };
  const pulse = setInterval(() => { if (retired) clearInterval(pulse); else void verify(); }, 15000);
  setTimeout(retire, 30000);
  window.addEventListener('focus', () => { void verify(); });
  document.addEventListener('visibilitychange', () => { if (document.visibilityState !== 'visible') retire(); });
  window.addEventListener('pagehide', retire);
  window.addEventListener('pageshow', event => { if (event.persisted) retire(); });
  document.querySelector('[data-encoding-reload]').addEventListener('click', () => location.reload());
})();
