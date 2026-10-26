(() => {
  'use strict';
  const root = document.querySelector('[data-foundation-device]');
  if (!root) return;
  const messages = JSON.parse(root.dataset.messages || '{}');
  const t = (key) => messages[key] || key;
  const q = (name) => root.querySelector(`[data-foundation-${name}]`);
  const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;
  const decimal = /^-?(?:\d{1,12}(?:\.\d{1,6})?|\.\d{1,6})$/;
  const controllers = new Set();
  let generation = 0, device = '', authorization = '', learner = '', school = 0;
  let context = null, set = null, pending = null, busy = false, stream = null, setupPair = null;
  let deadline = 0, leaseTimer = null, cameraTimer = null, reader = null;
  const positive = (v) => Number.isSafeInteger(v) && v > 0;
  const plain = (v) => v && typeof v === 'object' && !Array.isArray(v);
  const bounded = (v, max) => typeof v === 'string' && v.length > 0 && v.length <= max * 2 && Array.from(v).length <= max;
  const credential = (value, prefix) => {
    const match = new RegExp(`^${prefix}\\.([1-9][0-9]*)\\.([a-f0-9-]{36})\\.([a-f0-9]{64})$`).exec(value);
    if (!match || !uuid.test(match[2]) || value.length >= 160 || !positive(Number(match[1]))) return null;
    return {school: Number(match[1]), id: match[2]};
  };
  const current = (epoch) => generation === epoch && device !== '' && document.visibilityState === 'visible' && Date.now() < deadline;
  const status = (message) => { q('status').textContent = t(message); };
  const node = (tag, text, className = '') => {
    const element = document.createElement(tag);
    if (text !== undefined) element.textContent = text;
    if (className) element.className = className;
    return element;
  };
  function stopCamera() {
    clearTimeout(cameraTimer); cameraTimer = null;
    if (stream) stream.getTracks().forEach((track) => track.stop());
    stream = null;
    q('video').pause(); q('video').srcObject = null; q('video').hidden = true;
  }
  function updateControls() {
    root.querySelectorAll('[data-foundation-write]').forEach((control) => { control.disabled = busy || pending !== null; });
    q('retry').hidden = pending === null || busy;
    q('refresh').hidden = set === null || busy;
    q('retry').disabled = busy;
  }
  function clear(message = 'Ask your teacher to pair this tablet.') {
    generation++; controllers.forEach((controller) => controller.abort()); controllers.clear();
    stopCamera(); clearTimeout(leaseTimer); leaseTimer = null;
    device = ''; authorization = ''; learner = ''; school = 0; context = null; set = null;
    pending = null; busy = false; setupPair = null; reader = null; deadline = 0;
    root.querySelectorAll('input,textarea').forEach((control) => { control.value = ''; });
    q('setup-code').textContent = ''; q('setup-result').hidden = true;
    q('name').textContent = ''; q('questions').replaceChildren();
    q('ready').hidden = true; q('practice').hidden = true; q('pairing').hidden = false;
    q('retry').hidden = true; q('refresh').hidden = true;
    status(message); updateControls();
  }
  function armExpiry(value) {
    deadline = Math.min(deadline, value);
    clearTimeout(leaseTimer);
    leaseTimer = setTimeout(() => clear('This classroom session ended. Ask your teacher to pair again.'), Math.max(0, deadline - Date.now()));
  }
  function safeUrl(path) {
    if (!/^\/(?:api\/v2\/foundation-device\/select|api\/v2\/foundation-learner\/(?:context|leave|practice(?:\/[1-9][0-9]*(?:\/entries\/[1-9][0-9]*\/review)?)?))$/.test(path)) throw new Error('Invalid classroom path');
    const url = new URL(path, window.location.origin);
    if (url.origin !== window.location.origin || url.search || url.hash) throw new Error('Invalid classroom origin');
    return url;
  }
  async function responseData(response) {
    if (!(response.headers.get('Content-Type') || '').toLowerCase().startsWith('application/json')) throw new Error('Unverified response');
    // Ten maximum-sized multilingual approved definitions, including JSON escapes.
    const maximum = 2097152;
    if (Number(response.headers.get('Content-Length')) > maximum) throw new Error('Response too large');
    let text;
    if (response.body && response.body.getReader) {
      const body = response.body.getReader(), chunks = []; let length = 0;
      try {
        for (;;) {
          const result = await body.read();
          if (result.done) break;
          length += result.value.byteLength;
          if (length > maximum) { await body.cancel(); throw new Error('Response too large'); }
          chunks.push(result.value);
        }
      } finally { body.releaseLock(); }
      const bytes = new Uint8Array(length); let offset = 0;
      chunks.forEach((chunk) => { bytes.set(chunk, offset); offset += chunk.byteLength; });
      text = new TextDecoder('utf-8', {fatal: true}).decode(bytes);
    } else {
      text = await response.text(); if (text.length > maximum) throw new Error('Response too large');
    }
    const envelope = JSON.parse(text);
    if (!plain(envelope) || envelope.error !== false || envelope.code !== 200 || !plain(envelope.data)) throw new Error('Unverified response');
    return envelope.data;
  }
  async function send(path, serialized, epoch, learnerRequest = true) {
    if (!current(epoch) || (learnerRequest && learner === '')) throw new Error('Retired classroom');
    const controller = new AbortController(); controllers.add(controller);
    const timer = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(safeUrl(path), {
        method: serialized === null ? 'GET' : 'POST', credentials: 'omit', redirect: 'error', cache: 'no-store',
        mode: 'same-origin', referrerPolicy: 'no-referrer', signal: controller.signal,
        headers: {Accept: 'application/json', Authorization: `Bearer ${learnerRequest ? `${learner}~` : ''}${device}`,
          ...(serialized === null ? {} : {'Content-Type': 'application/json'})},
        ...(serialized === null ? {} : {body: serialized}),
      });
      if (!current(epoch)) throw new Error('Retired classroom');
      if (!response.ok) { const error = new Error('Classroom request refused'); error.status = response.status; throw error; }
      const data = await responseData(response);
      if (!current(epoch)) throw new Error('Retired classroom');
      return data;
    } finally { clearTimeout(timer); controllers.delete(controller); }
  }
  function verifiedContext(data, selection = false) {
    const expires = Date.parse(data.expires_at);
    if (!plain(data) || data.school_id !== school || !positive(data.learner_id) || !positive(data.teaching_group_id)
      || !bounded(data.name, 191) || data.restricted !== true || !Array.isArray(data.capabilities)
      || data.capabilities.length !== 1 || data.capabilities[0] !== 'daily_practice'
      || !Number.isFinite(expires) || expires <= Date.now() || expires > Date.now() + 16 * 60 * 1000) throw new Error('Unverified learner context');
    if (!selection && (!context || data.learner_id !== context.learner_id || data.teaching_group_id !== context.teaching_group_id
      || data.expires_at !== context.expires_at)) throw new Error('Replaced learner context');
    if (selection) {
      const token = typeof data.classroom_session === 'string' ? credential(data.classroom_session, 'NEF1') : null;
      if (!token || token.school !== school) throw new Error('Unverified learner credential');
    }
    return data;
  }
  function verifiedReview(data, entry) {
    if (!plain(data) || !positive(data.id) || data.entry_id !== entry || typeof data.correct !== 'boolean'
      || !/^\d{4}-\d{2}-\d{2}$/.test(data.reviewed_on) || !plain(data.schedule)
      || !Number.isSafeInteger(data.schedule.interval_days) || data.schedule.interval_days < 1 || data.schedule.interval_days > 36500) throw new Error('Unverified saved review');
    return data;
  }
  function verifiedSet(data) {
    if (!context || !plain(data) || !positive(data.id) || data.school_id !== school || data.learner_id !== context.learner_id
      || data.group_id !== context.teaching_group_id || !positive(data.reader_id) || (reader !== null && reader !== data.reader_id)
      || (set !== null && set.id !== data.id) || !/^\d{4}-\d{2}-\d{2}$/.test(data.practice_on)
      || !Number.isFinite(Date.parse(data.server_time)) || !Number.isFinite(Date.parse(data.offline_until))
      || Date.parse(data.offline_until) <= Date.now() || typeof data.completed !== 'boolean'
      || !Array.isArray(data.items) || data.items.length < 1 || data.items.length > 10) throw new Error('Unverified practice set');
    const seen = new Set();
    data.items.forEach((item) => {
      const definition = item.definition;
      if (!positive(item.id) || seen.has(item.id) || !positive(item.item_id) || !positive(item.topic_id) || !positive(item.source_version_id)
        || !plain(definition) || !['choice', 'number'].includes(definition.kind) || !bounded(definition.prompt, 4000)
        || !bounded(definition.explanation, 4000) || !Array.isArray(definition.options) || !Array.isArray(definition.answers)
        || definition.answers.length < 1 || definition.answers.length > 10 || definition.answers.some((answer) => !bounded(answer, 100))) throw new Error('Unverified practice question');
      seen.add(item.id);
      if (definition.kind === 'choice') {
        const ids = new Set();
        if (definition.options.length < 2 || definition.options.length > 6) throw new Error('Unverified practice choices');
        definition.options.forEach((option) => {
          if (!plain(option) || !/^[a-z][a-z0-9_]{0,19}$/.test(option.id) || ids.has(option.id) || !bounded(option.text, 1000)) throw new Error('Unverified practice choices');
          ids.add(option.id);
        });
        if (definition.answers.some((answer) => !ids.has(answer))) throw new Error('Unverified approved choice');
      } else if (definition.options.length !== 0 || definition.answers.some((answer) => !decimal.test(answer))) throw new Error('Unverified practice number');
      if (item.review !== null) verifiedReview(item.review, item.id);
    });
    reader = data.reader_id;
    return data;
  }
  function newWrite(action, path, body, learnerRequest = true, entry = null) {
    if (pending || busy || !current(generation)) return;
    pending = Object.freeze({action, path, body: JSON.stringify(body), learnerRequest, entry, epoch: generation});
    executePending();
  }
  async function executePending() {
    if (busy || !pending || !current(pending.epoch)) return;
    const operation = pending; busy = true; updateControls(); status('Please wait.');
    try {
      const data = await send(operation.path, operation.body, operation.epoch, operation.learnerRequest);
      if (pending !== operation || !current(operation.epoch)) return;
      if (operation.action === 'select') {
        verifiedContext(data, true);
        learner = data.classroom_session; context = data; set = null; reader = null;
        armExpiry(Date.parse(data.expires_at));
        q('name').textContent = data.name; q('ready').hidden = true; q('practice').hidden = false;
        q('questions').replaceChildren(); q('questions').append(node('p', t('Ready for your classroom practice.')));
        q('start').hidden = false; q('locator').value = ''; status('Your classroom practice is ready.');
      } else if (operation.action === 'issue') {
        set = verifiedSet(data); armExpiry(Date.parse(data.offline_until)); q('start').hidden = true; renderPractice();
      } else if (operation.action === 'review') {
        verifiedReview(data, operation.entry);
        const item = set?.items.find((candidate) => candidate.id === operation.entry);
        if (!item) throw new Error('Replaced practice question');
        item.review = data; renderFeedback(item);
      }
      pending = null;
    } catch (error) {
      if (!current(operation.epoch) || pending !== operation) return;
      if ([401, 403, 404].includes(error.status)) { clear('Classroom access changed. Ask your teacher to pair again.'); return; }
      if ([400, 413, 419, 422].includes(error.status)) {
        pending = null; status('Check your classroom entry or answer, then ask your teacher if needed.');
      } else {
        // Malformed 2xx, network/timeout, 408, 425, 429 and 5xx all retain exact JSON/UUID.
        status('We could not confirm the result. Retry the same request.');
      }
    } finally {
      if (generation === operation.epoch) { busy = false; updateControls(); }
    }
  }
  function renderPractice() {
    const box = q('questions'); box.replaceChildren();
    const item = set.items.find((candidate) => candidate.review === null);
    if (!item) { box.append(node('h2', t('Great practice today!')), node('p', t('Your saved practice is ready for your next lesson.'))); return; }
    const heading = node('h2', item.definition.prompt); heading.tabIndex = -1; box.append(heading);
    const form = document.createElement('form'); form.autocomplete = 'off';
    // A browser that loses JavaScript must never put an answer in a GET URL.
    form.method = 'POST'; form.action = safeUrl('/api/v2/foundation-learner/practice').href;
    const answers = node('fieldset'); answers.append(node('legend', t('Choose your answer')));
    if (item.definition.kind === 'choice') {
      item.definition.options.forEach((option) => {
        const label = node('label', undefined, 'foundation-choice'), input = document.createElement('input');
        input.type = 'radio'; input.name = 'answer'; input.value = option.id; input.required = true;
        input.setAttribute('data-foundation-write', ''); label.append(input, node('span', option.text)); answers.append(label);
      });
    } else {
      const label = node('label', t('Your number')), input = document.createElement('input');
      input.name = 'answer'; input.type = 'text'; input.inputMode = 'decimal'; input.maxLength = 100; input.required = true;
      input.autocomplete = 'off'; input.setAttribute('data-foundation-write', ''); label.append(input); answers.append(label);
    }
    form.append(answers);
    const feeling = node('fieldset'); feeling.append(node('legend', t('How did it feel?')));
    [[2, 'With help'], [3, 'By myself'], [4, 'Easy for me']].forEach(([value, copy]) => {
      const label = node('label', undefined, 'foundation-choice'), input = document.createElement('input');
      input.type = 'radio'; input.name = 'rating'; input.value = String(value); input.checked = value === 3; input.required = true;
      input.setAttribute('data-foundation-write', ''); label.append(input, node('span', t(copy))); feeling.append(label);
    });
    const submit = node('button', t('Save my answer'), 'm3-button m3-button--filled'); submit.type = 'submit'; submit.setAttribute('data-foundation-write', '');
    form.append(feeling, submit);
    form.addEventListener('submit', (event) => {
      event.preventDefault(); if (pending || busy || !context || !set) return;
      const data = new FormData(form), answer = String(data.get('answer') || '').trim(), rating = Number(data.get('rating'));
      if (!bounded(answer, 100) || ![2, 3, 4].includes(rating) || (item.definition.kind === 'number' && !decimal.test(answer))
        || (item.definition.kind === 'choice' && !item.definition.options.some((option) => option.id === answer))) {
        status('Choose an answer or enter a number using a dot.'); return;
      }
      newWrite('review', `/api/v2/foundation-learner/practice/${set.id}/entries/${item.id}/review`, {operation_id: crypto.randomUUID(), answer, rating}, true, item.id);
    });
    box.append(form); updateControls(); status('Try one question. Ask your teacher whenever you need help.'); heading.focus();
  }
  function renderFeedback(item) {
    const box = q('questions'); box.replaceChildren();
    box.append(node('h2', t(item.review.correct ? 'Well done!' : 'Let us learn together.')), node('p', item.definition.explanation));
    const next = node('button', t('Next question'), 'm3-button m3-button--filled'); next.type = 'button';
    next.addEventListener('click', () => refreshSet()); box.append(next);
  }
  async function refreshSet() {
    if (busy || !set || !current(generation)) return;
    const epoch = generation, id = set.id; busy = true; updateControls();
    try {
      verifiedContext(await send('/api/v2/foundation-learner/context', null, epoch));
      const data = verifiedSet(await send(`/api/v2/foundation-learner/practice/${id}`, null, epoch));
      if (!current(epoch) || set?.id !== id) return;
      set = data;
      if (pending?.action === 'review' && set.items.find((item) => item.id === pending.entry)?.review) pending = null;
      if (pending) { q('questions').replaceChildren(); status('Retry the same request before starting another question.'); }
      else renderPractice();
    } catch (error) {
      if (!current(epoch)) return;
      if ([401, 403, 404].includes(error.status)) clear('Classroom access changed. Ask your teacher to pair again.');
      else { q('questions').replaceChildren(); status('Saved practice could not be checked. Try reading it again.'); }
    } finally { if (generation === epoch) { busy = false; updateControls(); } }
  }
  q('pair-form').addEventListener('submit', (event) => {
    event.preventDefault(); if (busy || pending) return;
    const nextDevice = q('tablet-code').value.trim(), nextAuthorization = q('authorization').value.trim();
    const d = credential(nextDevice, 'NED1'), a = credential(nextAuthorization, 'NECL1');
    if (!d || !a || d.school !== a.school || !crypto.randomUUID) { status('Use the tablet access code and your teacher classroom authorization.'); return; }
    clear(); device = nextDevice; authorization = nextAuthorization; school = d.school;
    deadline = Date.now() + 15 * 60 * 1000; armExpiry(deadline);
    q('pairing').hidden = true; q('ready').hidden = false; status('Scan your classroom card, or enter its printed classroom code.');
  });
  q('select-form').addEventListener('submit', (event) => {
    event.preventDefault(); if (busy || pending || !current(generation)) return;
    const locator = q('locator').value.trim();
    const match = /^NELOC1\.([1-9][0-9]*)\.([a-f0-9-]{36})$/.exec(locator);
    if (!match || Number(match[1]) !== school || !uuid.test(match[2])) { status('Use a printed card from this classroom school.'); return; }
    stopCamera(); newWrite('select', '/api/v2/foundation-device/select', {operation_id: crypto.randomUUID(), classroom_authorization: authorization, locator}, false);
  });
  q('start').addEventListener('click', () => { if (context) newWrite('issue', '/api/v2/foundation-learner/practice', {operation_id: crypto.randomUUID()}); });
  q('retry').addEventListener('click', executePending);
  q('refresh').addEventListener('click', refreshSet);
  q('exit').addEventListener('click', () => {
    const oldDevice = device, oldLearner = learner;
    clear('This tablet is clear. Ask your teacher before the next learner.');
    if (oldDevice && oldLearner) {
      const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 5000);
      controllers.add(controller);
      // Clear locally first; this best-effort revocation never restores retired state.
      fetch(safeUrl('/api/v2/foundation-learner/leave'), {method: 'POST', mode: 'same-origin', credentials: 'omit', redirect: 'error', cache: 'no-store', referrerPolicy: 'no-referrer', signal: controller.signal,
        headers: {Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${oldLearner}~${oldDevice}`}, body: '{}'}).catch(() => {}).finally(() => { clearTimeout(timer); controllers.delete(controller); });
    }
  });
  q('setup').addEventListener('click', async () => {
    if (busy || pending) return;
    clear(); const epoch = generation; busy = true; updateControls();
    try {
      if (!crypto.subtle || !window.isSecureContext) throw new Error('Secure setup unavailable');
      const pair = await crypto.subtle.generateKey({name: 'X25519'}, true, ['deriveBits']);
      const bytes = new Uint8Array(await crypto.subtle.exportKey('raw', pair.publicKey));
      if (generation !== epoch || document.hidden) return;
      if (bytes.byteLength !== 32) throw new Error('Invalid public setup key');
      setupPair = pair;
      q('setup-code').textContent = `NESET1.${btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')}`;
      q('setup-result').hidden = false; status('Give only this setup code to your school administrator.');
    } catch (_) { if (generation === epoch) status('Tablet setup is unavailable in this browser. Ask your school administrator.'); }
    finally { if (generation === epoch) { busy = false; updateControls(); } }
  });
  q('camera').addEventListener('click', async () => {
    if (busy || pending || !current(generation)) return;
    const epoch = generation; stopCamera();
    if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) { status('Camera scanning is unavailable. Enter the printed classroom code.'); return; }
    try {
      const captured = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
      if (!current(epoch)) { captured.getTracks().forEach((track) => track.stop()); return; }
      stream = captured; const video = q('video'); video.srcObject = stream; video.hidden = false; await video.play();
      if (!current(epoch)) { stopCamera(); return; }
      const detector = new BarcodeDetector({formats: ['qr_code']});
      const scan = async () => {
        if (!current(epoch) || !stream || pending) { stopCamera(); return; }
        try {
          const results = await detector.detect(video);
          if (!current(epoch) || !stream) return;
          const value = results.find((result) => /^NELOC1\./.test(result.rawValue || ''))?.rawValue;
          if (value) { q('locator').value = value; stopCamera(); q('select-form').requestSubmit(); return; }
          cameraTimer = setTimeout(scan, 300);
        } catch (_) { stopCamera(); if (current(epoch)) status('Camera scanning stopped. Enter the printed classroom code.'); }
      };
      scan();
    } catch (_) { stopCamera(); if (current(epoch)) status('Camera access is unavailable. Enter the printed classroom code.'); }
  });
  q('stop-camera').addEventListener('click', stopCamera);
  document.addEventListener('visibilitychange', () => { if (document.hidden) clear(); });
  window.addEventListener('pagehide', () => clear());
  window.addEventListener('pageshow', (event) => { if (event.persisted) clear(); });
  clear();
})();
