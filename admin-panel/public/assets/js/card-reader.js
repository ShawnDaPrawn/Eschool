(() => {
  'use strict';
  const root = document.querySelector('[data-card-reader]');
  if (!root) return;
  const messages = JSON.parse(root.dataset.messages || '{}');
  const text = (value) => messages[value] || value;
  const requests = new Set();
  const select = (key) => root.querySelector(`[data-reader-${key}]`);
  const credential = root.querySelector('#reader-credential');
  const proof = root.querySelector('#reader-proof');
  const status = select('status');
  const photo = select('photo');
  const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;
  let token = '', school = '', generation = 0, busy = false, pending = null, collectionPending = null;
  let stream = null, nfc = null, objectUrl = null, clearTimer = null, displayGeneration = 0;
  const current = (epoch) => epoch === generation && token !== '' && document.visibilityState === 'visible';
  function clearPixels() {
    displayGeneration++;
    clearTimeout(clearTimer); clearTimer = null;
    select('gate').hidden = true; select('gate-options').replaceChildren(); select('gate-message').textContent = '';
    photo.removeAttribute('src'); select('result').hidden = true;
    select('kind').textContent = '';
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
  }
  function stopCapture() {
    if (stream) stream.getTracks().forEach((track) => track.stop());
    stream = null; select('video').srcObject = null; select('video').hidden = true;
    if (nfc) nfc.abort(); nfc = null;
  }
  function clear() {
    generation++; busy = false; token = ''; school = ''; pending = null; collectionPending = null; select('retry').hidden = true; select('collect-retry').hidden = true;
    requests.forEach((request) => request.abort()); requests.clear();
    stopCapture(); clearPixels(); credential.value = ''; proof.value = '';
    select('ready').hidden = true; status.textContent = '';
  }
  async function send(path, body, epoch, jsonResponse = false) {
    if (!current(epoch)) throw new Error(text('Reader access changed.'));
    const request = new AbortController(); requests.add(request);
    const timeout = setTimeout(() => request.abort(), 10000);
    try {
      const response = await fetch(`${root.dataset.apiBase}${path}`, {
        signal: request.signal,
        method: body ? 'POST' : 'GET', credentials: 'omit', cache: 'no-store',
        referrerPolicy: 'no-referrer',
        headers: { Accept: body || jsonResponse ? 'application/json' : 'image/jpeg', Authorization: `Bearer ${token}`, 'X-School-Id': school, ...(body ? {'Content-Type': 'application/json'} : {}) },
        ...(body ? {body: JSON.stringify(body)} : {}),
      });
      if (!current(epoch)) throw new Error(text('Reader access changed.'));
      if (!response.ok) {
        const fail = (message) => { const error = new Error(message); error.status = response.status; error.definitive = response.status < 500 && response.status !== 429; return error; };
        if ([401, 403].includes(response.status)) throw fail(text('Reader or card access was refused. Recheck the registered reader and current card.'));
        if (response.status === 409) throw fail(text('This proof was already used or the card is frozen. Ask for a fresh code or secure tap.'));
        throw fail(text('The card could not be verified. Reconnect and try again.'));
      }
      if (body || jsonResponse) return await response.json();
      if (!(response.headers.get('Content-Type') || '').startsWith('image/jpeg')) throw new Error(text('The private portrait is unavailable.'));
      return await response.blob();
    } catch (error) {
      if (error.name === 'AbortError' || error instanceof TypeError) throw new Error(text('Connection interrupted. Retry the same verification.'));
      throw error;
    } finally { clearTimeout(timeout); requests.delete(request); }
  }
  async function collect(epoch) {
    if (busy || !collectionPending || !current(epoch)) return;
    busy = true; select('collect-retry').hidden = true;
    select('gate-options').replaceChildren();
    try {
      const envelope = await send(collectionPending.path, collectionPending.body, epoch, true);
      if (!current(epoch)) return;
      if (envelope.error !== false || envelope.data?.id !== collectionPending.id || envelope.data?.status !== 'collected') throw new Error('Invalid collection receipt');
      collectionPending = null; clearPixels(); status.textContent = text('Collection recorded.');
    } catch (error) {
      if (!current(epoch)) return;
      clearPixels();
      if (error.definitive) { collectionPending = null; status.textContent = text('Collection was refused. Scan again and check current approval.'); }
      else { select('collect-retry').hidden = false; status.textContent = text('Collection outcome is uncertain. Retry the same record before another scan.'); }
    } finally { if (epoch === generation) busy = false; }
  }
  async function gate(result, epoch, display) {
    if (result.type !== 'learner') return;
    try {
      const envelope = await send(`/scans/${result.scan_id}/gate-passes`, null, epoch, true);
      if (!current(epoch) || display !== displayGeneration || select('result').hidden) return;
      const data = envelope.data;
      if (envelope.error !== false || data?.scan_id !== result.scan_id || data.collector_check_required !== true || !Array.isArray(data.passes) || data.passes.length > 1) throw new Error('Invalid collection clearance');
      if (data.available === false) return;
      if (data.available !== true) throw new Error('Invalid collection availability');
      select('gate').hidden = false;
      select('gate-message').textContent = data.passes.length ? '' : text('No current staff-approved collection pass.');
      for (const pass of data.passes) {
        if (!uuid.test(pass.id || '') || !Number.isInteger(pass.revision) || pass.revision < 1 || typeof pass.collector !== 'string' || pass.collector.length < 1 || pass.collector.length > 191 || !Number.isFinite(Date.parse(pass.ends_at)) || !Number.isFinite(Date.parse(pass.starts_at)) || Date.parse(pass.starts_at) > Date.now() || Date.parse(pass.ends_at) <= Date.now()) throw new Error('Invalid collection clearance');
        const card = document.createElement('form'), name = document.createElement('p'), label = document.createElement('label'), check = document.createElement('input'), button = document.createElement('button');
        name.textContent = `${text('Named collector')}: ${pass.collector}`;
        check.type = 'checkbox'; check.required = true; check.name = 'collector_confirmed';
        label.append(check, document.createTextNode(text('I checked the named collector in person.')));
        button.type = 'submit'; button.className = 'm3-button m3-button--filled'; button.textContent = text('Record collection'); button.disabled = true;
        check.addEventListener('change', () => { button.disabled = !check.checked; });
        card.append(name, label, button);
        card.addEventListener('submit', (event) => {
          event.preventDefault(); if (!check.checked || !current(epoch) || display !== displayGeneration || busy || collectionPending || select('result').hidden) return;
          collectionPending = {id: pass.id, path: `/gate-passes/${pass.id}/collect`, body: {operation_id: crypto.randomUUID(), revision: pass.revision, scan_id: result.scan_id, collector_confirmed: true}};
          collect(epoch);
        });
        select('gate-options').append(card);
      }
    } catch (_) {
      if (current(epoch) && display === displayGeneration && !select('result').hidden) { select('gate-options').replaceChildren(); select('gate').hidden = false; select('gate-message').textContent = text('Early collection could not be checked. Reconnect and scan again.'); }
    }
  }
  function decode(value) {
    if (value.startsWith('NEQ1.') && value.length <= 1024) return ['/scan/qr', {credential: value}];
    if (value.startsWith('NELOC1.')) throw new Error(text('A printed locator is not a secure proof. Use the app code or tap the personalized secure card.'));
    let params;
    try { params = new URLSearchParams(new URL(value).hash.slice(1)); }
    catch (_) { throw new Error(text('Use a secure app code or personalized card tap.')); }
    if (!uuid.test(params.get('c') || '') || !/^[a-f0-9]{32}$/i.test(params.get('e') || '') || !/^[a-f0-9]{16}$/i.test(params.get('m') || '') || params.get('s') !== school) throw new Error(text('This personalized tap is not for the selected school reader.'));
    return ['/scan/sun', {card_id: params.get('c'), picc: params.get('e'), mac: params.get('m')}];
  }
  async function scan(value, retry = false) {
    if (busy || !token) return;
    if (collectionPending) { status.textContent = text('Retry or clear the previous collection before scanning another card.'); return; }
    if (pending && !retry) { status.textContent = text('Retry or clear the previous verification before scanning another card.'); return; }
    const epoch = generation; const started = performance.now(); busy = true; clearPixels(); proof.value = '';
    status.textContent = text('Verifying card…');
    try {
      if (!pending) { const [path, payload] = decode(value.trim()); pending = {path, body: {operation_id: crypto.randomUUID(), ...payload}}; }
      const envelope = await send(pending.path, pending.body, epoch);
      if (!current(epoch)) return;
      const result = envelope.data;
      if (envelope.error !== false || !uuid.test(result?.scan_id || '') || result.outcome !== 'accepted' || !['learner', 'staff', 'guardian'].includes(result.type)) throw new Error(text('The card response could not be verified.'));
      const bytes = await send(`/scans/${result.scan_id}/portrait`, null, epoch);
      if (!current(epoch)) return;
      if (bytes.size < 4 || bytes.size > 4194304) throw new Error(text('The private portrait is unavailable.'));
      const remaining = 15000 - (performance.now() - started);
      if (remaining <= 0) throw new Error(text('Verification took too long. Retry the same verification.'));
      objectUrl = URL.createObjectURL(bytes); photo.src = objectUrl;
      select('kind').textContent = ({learner: text('Learner'), staff: text('Staff'), guardian: text('Guardian')})[result.type];
      select('result').hidden = false; status.textContent = text('Current card verified.');
      pending = null; select('retry').hidden = true;
      stopCapture(); clearTimer = setTimeout(() => { clearPixels(); if (!collectionPending) status.textContent = text('Scan a school card.'); }, remaining);
      gate(result, epoch, displayGeneration);
    } catch (error) {
      if (current(epoch)) { clearPixels(); if (error.definitive) pending = null; select('retry').hidden = !pending; status.textContent = error.message || text('The card could not be verified.'); }
    } finally { if (epoch === generation) { busy = false; stopCapture(); } }
  }
  select('pair').addEventListener('submit', (event) => {
    event.preventDefault(); const candidate = credential.value.trim(); clear();
    const parts = /^NED1\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})$/.exec(candidate);
    if (!parts || !uuid.test(parts[2])) { status.textContent = text('Use the private credential from a registered school reader.'); return; }
    token = candidate; school = parts[1]; select('ready').hidden = false;
    status.textContent = text('Scan a school card.');
  });
  credential.addEventListener('input', () => { if (token) { const candidate = credential.value; clear(); credential.value = candidate; } });
  select('collect-retry').addEventListener('click', () => collect(generation));
  select('retry').addEventListener('click', () => { if (pending) scan('', true); });
  select('manual').addEventListener('submit', (event) => { event.preventDefault(); scan(proof.value); });
  select('stop').addEventListener('click', clear);
  select('camera').addEventListener('click', async () => {
    const epoch = generation;
    if (!current(epoch)) return;
    if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) { status.textContent = text('This browser cannot scan a camera code. Use a supported school device or enter the secure app code.'); return; }
    try {
      stopCapture(); const captured = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
      if (!current(epoch)) { captured.getTracks().forEach((track) => track.stop()); return; }
      stream = captured; const video = select('video'); video.srcObject = captured; video.hidden = false; await video.play();
      const detector = new BarcodeDetector({formats: ['qr_code']});
      const read = async () => {
        if (!current(epoch) || !stream) return;
        try { if (!busy) { const found = await detector.detect(video); if (current(epoch) && found[0]?.rawValue) await scan(found[0].rawValue); } }
        catch (_) { /* A changing frame is retried; no pixels leave this device. */ }
        if (current(epoch) && stream) setTimeout(read, 150);
      };
      read();
    } catch (_) { if (current(epoch)) status.textContent = text('Camera access is unavailable. Enter the secure app code or use a supported school device.'); }
  });
  select('nfc').addEventListener('click', async () => {
    const epoch = generation;
    if (!current(epoch)) return;
    if (!window.NDEFReader) { status.textContent = text('Secure tap is unavailable in this browser. Use a supported school reader.'); return; }
    try {
      stopCapture(); nfc = new AbortController(); const reader = new NDEFReader();
      await reader.scan({signal: nfc.signal});
      reader.addEventListener('reading', (event) => {
        if (!current(epoch)) return;
        for (const record of event.message.records) {
          if (!['url', 'absolute-url'].includes(record.recordType)) continue;
          const bytes = new Uint8Array(record.data.buffer, record.data.byteOffset, record.data.byteLength);
          const value = bytes[0] === 4 ? `https://${new TextDecoder().decode(bytes.slice(1))}` : bytes[0] === 0 ? new TextDecoder().decode(bytes.slice(1)) : new TextDecoder().decode(bytes);
          scan(value);
        }
      });
      status.textContent = text('Tap the personalized secure card.');
    } catch (_) { if (current(epoch)) status.textContent = text('Secure tap could not start. Use the app code or a supported school reader.'); }
  });
  document.addEventListener('visibilitychange', () => { if (document.visibilityState !== 'visible') clear(); });
  window.addEventListener('pagehide', clear);
})();
