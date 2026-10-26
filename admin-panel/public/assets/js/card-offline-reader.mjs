import {createOfflineReaderKey, OfflineCardSnapshot} from './card-offline-crypto.mjs';
import {OfflineReaderStore, decryptPendingRecord} from './card-offline-store.mjs';

const root = document.querySelector('[data-offline-reader]');
if (root) {
  const messages = JSON.parse(root.dataset.messages || '{}'), text = value => messages[value] || value;
  const element = name => root.querySelector(`[data-offline-${name}]`);
  const tokenField = element('credential'), proofField = element('proof'), picture = element('portrait');
  const keysField = element('keys'), status = element('status'), cacheStatus = element('cache-status');
  const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;
  const requests = new Set(), pages = new Map();
  let store, reader, credential = '', epoch = 0, busy = false, next = null, url, display, camera, captureTimer;
  const current = generation => generation === epoch && credential !== '' && document.visibilityState === 'visible';
  const message = value => { status.textContent = text(value); };
  function clearPicture() {
    clearTimeout(display); display = null;
    picture.removeAttribute('src'); element('result').hidden = true;
    if (url) URL.revokeObjectURL(url);
    url = null;
  }
  function stopCamera() {
    clearTimeout(captureTimer);
    camera?.getTracks().forEach(track => track.stop()); camera = null;
    element('video').srcObject = null; element('video').hidden = true;
  }
  function count() {
    let cards = 0;
    for (const page of [...pages.values()]) { try { cards += page.count; } catch (_) {} }
    cacheStatus.textContent = `${text('Current cached cards')}: ${cards}`;
    element('next').hidden = next === null || pages.size >= 8;
  }
  function clear() {
    epoch++; busy = false;
    credential = ''; reader = null; next = null;
    requests.forEach(request => request.abort()); requests.clear();
    for (const page of pages.values()) page.dispose();
    pages.clear(); stopCamera(); clearPicture();
    tokenField.value = ''; proofField.value = '';
    element('ready').hidden = true; status.textContent = ''; element('pending').textContent = ''; count();
  }
  async function send(path, input, generation) {
    if (!current(generation)) throw new Error('Reader access changed.');
    const controller = new AbortController(); requests.add(controller);
    const timeout = setTimeout(() => controller.abort(), 10000);
    try {
      const response = await fetch(`${root.dataset.apiBase}${path}`, {method: input ? 'POST' : 'GET', credentials: 'omit', redirect: 'error', cache: 'no-store',
        referrerPolicy: 'no-referrer', signal: controller.signal,
        headers: {Accept: 'application/json', Authorization: `Bearer ${credential}`, 'X-School-Id': String(reader.schoolId), ...(input ? {'Content-Type': 'application/json'} : {})},
        ...(input ? {body: JSON.stringify(input)} : {})});
      if (!current(generation)) throw new Error('Reader access changed.');
      if (!response.ok) {
        const error = new Error('Current reader or card access could not be verified.');
        error.status = response.status;
        throw error;
      }
      const envelope = await response.json();
      if (!current(generation) || envelope.error !== false || !envelope.data) throw new Error('Reader access changed.');
      return envelope.data;
    } finally { clearTimeout(timeout); requests.delete(controller); }
  }
  async function loadPage(generation, after = null) {
    if (!current(generation)) return;
    const fetchedAt = performance.now(), requestId = crypto.randomUUID();
    const packet = await send(`/offline/snapshot?request_id=${requestId}${after ? `&after_card_id=${encodeURIComponent(after)}` : ''}`, null, generation);
    const receivedAt = performance.now();
    if (!current(generation) || (packet.next_after !== null && !uuid.test(packet.next_after ?? ''))) throw new Error('Reader access changed.');
    // Trust is established only by this fresh authenticated network response.
    // A saved envelope is never reopened with a new clock or self-announced key.
    const page = await OfflineCardSnapshot.open(packet, reader.privateKey, {schoolId: reader.schoolId, deviceId: reader.deviceId,
      fetchedAt, receivedAt, expectedRequestId: requestId, trustedSigningKeys: new Map([[packet.signing_key_id, packet.signing_public_key]])});
    if (!current(generation)) { page.dispose(); return; }
    if (after === null) { for (const old of pages.values()) old.dispose(); pages.clear(); }
    const id = packet.envelope.context.snapshot_id;
    page.addEventListener('retired', () => {
      pages.delete(id); clearPicture(); count();
      if (current(generation) && pages.size === 0) message('Offline cache expired. Reconnect to refresh current status.');
    });
    pages.set(id, page); next = packet.next_after; count();
    await store.pruneNonces(reader, packet.envelope.context.issued_at);
  }
  async function synchronize(generation) {
    if (!current(generation)) return;
    const scope = reader;
    const rows = await store.pending(scope);
    if (!current(generation)) return;
    for (const row of rows) {
      if (!current(generation)) return;
      let input;
      try { input = await decryptPendingRecord(scope.privateKey, scope, row.id, row.packet); }
      catch (_) { await store.removePending(scope, row.id); message('A saved scan could not be verified. Use online verification.'); return; }
      try {
        const result = await send('/offline/reconcile', input, generation);
        if (!current(generation)) return;
        if (result.collection_authorized !== false || result.sign_in_authorized !== false || !['offline_accepted', 'status_changed', 'access_changed', 'duplicate'].includes(result.outcome)) throw new Error('Invalid identity-only history receipt.');
        await store.removePending(scope, row.id);
        if (!current(generation)) return;
        if (result.outcome !== 'offline_accepted') { clearPicture(); message('Current card status changed. Use online verification.'); }
      } catch (error) {
        if (!current(generation)) return;
        if ([401, 403, 404, 422].includes(error.status)) {
          await store.removePending(scope, row.id);
          clearPicture();
          if ([401, 403].includes(error.status)) { clear(); message('Reader access changed. Reconnect with a current registered reader.'); return; }
        }
        // Ambiguous transport/429/5xx keeps the exact encrypted UUID record.
        message('Saved scan outcome is unconfirmed. Retry synchronization when connected.');
        return;
      } finally {
        if (input) { input.manifest = ''; input.credential = ''; }
      }
    }
    const remaining = await store.pending(scope);
    if (current(generation)) element('pending').textContent = `${text('Saved scans awaiting synchronization')}: ${remaining.length}`;
  }
  async function scan(value) {
    if (busy || !credential || document.visibilityState !== 'visible') return;
    busy = true; stopCamera(); clearPicture(); proofField.value = '';
    const generation = epoch, started = performance.now();
    let result;
    try {
      if (!value.startsWith('NEQ1.') || value.length > 1024) throw new Error('Offline verification requires a current signed app code. Secure taps and printed locators need online verification.');
      for (const page of pages.values()) {
        try { result = await page.scan(value); break; } catch (_) {}
      }
      if (!result || !current(generation)) throw new Error('The card is not in a current offline cache or the proof was already used. Reconnect to verify online.');
      const input = {operation_id: crypto.randomUUID(), manifest: result.manifest, credential: value, scanned_at: result.scannedAt};
      await store.claimAndQueue(reader, reader, input, result.nonceHash, result.proofExpiresAt);
      if (!current(generation) || performance.now() - started >= 15000) return;
      const blob = new Blob([result.portrait], {type: 'image/jpeg'});
      url = URL.createObjectURL(blob); picture.src = url;
      await Promise.race([picture.decode(), new Promise((_, reject) => setTimeout(() => reject(new Error('Portrait display timed out.')), 5000))]);
      if (!current(generation) || performance.now() - started >= 15000) { clearPicture(); return; }
      element('result').hidden = false;
      display = setTimeout(clearPicture, Math.max(0, 15000 - (performance.now() - started)));
      message('Identity matched the current cached status. Early collection and sign-in are not authorized.');
      const pending = await store.pending(reader);
      if (!current(generation)) { clearPicture(); return; }
      element('pending').textContent = `${text('Saved scans awaiting synchronization')}: ${pending.length}`;
      if (navigator.onLine) await synchronize(generation);
    } catch (error) {
      clearPicture();
      if (current(generation)) message(error.message || 'Offline verification was refused. Reconnect to verify online.');
    } finally { result?.portrait?.fill(0); if (generation === epoch) busy = false; }
  }
  element('create-key').addEventListener('click', async () => {
    if (busy) return; clear(); busy = true;
    const generation = epoch;
    try {
      const key = await store.createKey(await createOfflineReaderKey());
      if (generation !== epoch || document.visibilityState !== 'visible') return;
      await showKeys(key.id);
      element('public-key').value = key.publicKey;
      message('Register this public key as a gate reader in School card readers. The private key remains on this device.');
    } catch (_) { message('Protected device keys are unavailable. Use the online reader.'); }
    finally { busy = false; }
  });
  async function showKeys(selected = keysField.value) {
    keysField.replaceChildren();
    const available = await store.keys();
    for (const key of available) {
      const option = document.createElement('option'); option.value = key.id;
      option.textContent = `${text('Reader key')} ${key.id.slice(0, 8)}`;
      option.selected = selected === key.id; keysField.append(option);
    }
    element('public-key').value = available.find(key => key.id === keysField.value)?.publicKey ?? '';
  }
  keysField.addEventListener('change', () => { clear(); void showKeys(keysField.value); });
  element('pair').addEventListener('submit', async event => {
    event.preventDefault(); if (busy) return;
    const value = tokenField.value.trim(), selected = keysField.value;
    clear(); busy = true;
    try {
      const parts = /^NED1\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})$/.exec(value);
      if (!parts || !Number.isSafeInteger(Number(parts[1])) || !uuid.test(parts[2])) throw new Error('Use the private credential for this registered gate reader.');
      const key = await store.key(selected);
      if (!key || (key.schoolId !== null && (key.schoolId !== Number(parts[1]) || key.deviceId !== parts[2]))) throw new Error('Select the device key registered for this reader.');
      reader = {...key, keyId: key.id, schoolId: Number(parts[1]), deviceId: parts[2]}; credential = value;
      const generation = epoch;
      await loadPage(generation);
      if (!current(generation)) return;
      const bound = await store.bind(selected, reader.schoolId, reader.deviceId);
      if (!current(generation)) return;
      reader = {...bound, keyId: selected};
      element('ready').hidden = false; message('Offline identity cache ready for up to sixty seconds.');
      await synchronize(generation);
    } catch (error) { clear(); message(error.message || 'Reader setup failed. Reconnect to verify online.'); }
    finally { busy = false; }
  });
  element('refresh').addEventListener('click', async () => {
    if (busy || !credential) return; busy = true;
    try { await loadPage(epoch); message('Offline identity cache refreshed.'); }
    catch (_) { clear(); message('Reader access changed. Reconnect with a current registered reader.'); }
    finally { busy = false; }
  });
  element('next').addEventListener('click', async () => {
    if (busy || !credential || !next || pages.size >= 8) return; busy = true;
    try { await loadPage(epoch, next); }
    catch (_) { message('The next cache page could not be prepared. Reconnect to verify uncached cards online.'); }
    finally { busy = false; }
  });
  element('sync').addEventListener('click', async () => { if (!busy && credential) { busy = true; try { await synchronize(epoch); } finally { busy = false; } } });
  element('manual').addEventListener('submit', event => { event.preventDefault(); void scan(proofField.value.trim()); });
  element('stop').addEventListener('click', () => { clear(); message('Reader cleared. Reconnect before scanning again.'); });
  element('camera').addEventListener('click', async () => {
    if (busy || !credential) return;
    if (!globalThis.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) { message('Camera scanning is unavailable here. Enter a current signed app code.'); return; }
    const generation = epoch;
    try {
      camera = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
      if (!current(generation)) { stopCamera(); return; }
      element('video').srcObject = camera; element('video').hidden = false; await element('video').play();
      const detector = new BarcodeDetector({formats: ['qr_code']});
      const capture = async () => {
        if (!current(generation) || !camera) return;
        const found = await detector.detect(element('video'));
        if (found.length) { stopCamera(); await scan(found[0].rawValue); }
        else captureTimer = setTimeout(() => { void capture().catch(() => { stopCamera(); }); }, 150);
      };
      await capture();
    } catch (_) { stopCamera(); message('Camera access is unavailable. Enter a current signed app code.'); }
  });
  document.addEventListener('visibilitychange', () => { if (document.visibilityState !== 'visible') clear(); });
  window.addEventListener('pagehide', clear);
  window.addEventListener('pageshow', event => { if (event.persisted) clear(); });
  void OfflineReaderStore.open().then(async value => { store = value; await showKeys(); element('setup').disabled = false; })
    .catch(() => { message('Protected device storage is unavailable. Use the online reader.'); });
}
