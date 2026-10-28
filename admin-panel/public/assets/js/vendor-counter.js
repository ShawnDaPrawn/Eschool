(() => {
  'use strict';
  const root = document.querySelector('[data-vendor-private]');
  if (!root) return;
  const button = root.querySelector('[data-vendor-camera]'), video = root.querySelector('[data-vendor-video]');
  const stopButton = root.querySelector('[data-vendor-stop]'), field = root.querySelector('[data-vendor-qr]');
  const status = root.querySelector('[data-vendor-camera-status]');
  let stream = null, timer = null, generation = 0;
  const stop = () => {
    generation++;
    if (timer !== null) clearTimeout(timer);
    stream?.getTracks().forEach(track => track.stop());
    stream = null;
    if (video) { video.srcObject = null; video.hidden = true; }
    if (stopButton) stopButton.hidden = true;
    if (button) button.disabled = false;
  };
  stopButton?.addEventListener('click', stop);
  button?.addEventListener('click', async () => {
    if (!video || !field || !status) return;
    stop(); const epoch = generation;
    if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) {
      status.textContent = status.dataset.unavailable;
      return;
    }
    try {
      button.disabled = true;
      const detector = new BarcodeDetector({formats: ['qr_code']});
      const camera = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
      if (epoch !== generation) { camera.getTracks().forEach(track => track.stop()); return; }
      stream = camera; video.srcObject = camera; video.hidden = false; stopButton.hidden = false;
      await video.play();
      const scan = async () => {
        if (epoch !== generation) return;
        const codes = await detector.detect(video);
        if (epoch !== generation) return;
        const pattern = field.dataset.vendorQrPurpose === 'ticket' ? /^navigateed:ticket:[a-f0-9]{64}$/ : /^navigateed:order:[a-f0-9]{64}$/;
        const code = codes.find(item => pattern.test(item.rawValue ?? ''));
        if (code) {
          field.value = code.rawValue;
          const number = root.querySelector(field.dataset.vendorQrPurpose === 'ticket' ? '[name="ticket_number"]' : '[name="order_number"]'); if (number) number.value = '';
          stop(); status.textContent = status.dataset.ready; field.focus();
          return;
        }
        timer = setTimeout(() => scan().catch(() => {if (epoch !== generation) return;stop(); status.textContent = status.dataset.stopped;}), 300);
      };
      await scan();
    } catch (_) {
      if (epoch !== generation) return;
      stop(); status.textContent = status.dataset.unavailable;
    }
  });
  if (button) button.disabled = false;
  const erase = () => { stop(); root.replaceChildren(); };
  let hidden = false;
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { hidden = true; erase(); }
    else if (hidden) location.reload();
  });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
})();
