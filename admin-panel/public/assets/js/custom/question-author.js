(() => {
  'use strict';
  const form = document.querySelector('[data-question-author]');
  if (!form) return;
  const kind = form.querySelector('[name=question_kind]');
  const words = JSON.parse(form.querySelector('[data-question-editor-labels]').textContent);
  const sources = Object.fromEntries(['options', 'answers', 'steps', 'regions', 'labels', 'mapping'].map(name => [name, form.querySelector(`[name=${name}]`)]));
  const lines = text => text.split(/\r?\n/).map(line => line.trim()).filter(Boolean);
  const pair = text => { const i = text.indexOf(':'); return i < 0 ? [text, ''] : [text.slice(0, i).trim(), text.slice(i + 1).trim()]; };
  const pairs = text => lines(text).map(line => { const [code, text] = pair(line); return { code, text }; });
  const state = {
    options: pairs(sources.options.value), answers: lines(sources.answers.value).map(answer => ({ answer })),
    labels: pairs(sources.labels.value), mapping: Object.fromEntries(lines(sources.mapping.value).map(pair)),
    regions: pairs(sources.regions.value).map(({ code, text }) => { const [x = '', y = ''] = text.split(',').map(s => s.trim()); return { code, x, y }; }),
    steps: lines(sources.steps.value).map(line => {
      const [prompt = '', answers = '', choices = ''] = line.split('|').map(s => s.trim());
      return { prompt, answers: answers.split(',').map(s => s.trim()).filter(Boolean).join('\n'), options: choices.split(';').map(s => s.trim()).filter(Boolean).map(line => { const [code, text] = pair(line); return { code, text }; }) };
    })
  };
  let serial = 0;
  const el = (tag, text, className) => { const node = document.createElement(tag); if (text !== undefined) node.textContent = text; if (className) node.className = className; return node; };
  const button = (text, handler, parent) => { const b = el('button', text, 'm3-button m3-button--outlined'); b.type = 'button'; b.addEventListener('click', handler); parent.append(b); return b; };
  const sourceWrapper = source => source.closest('.page-field') || source.closest('.m3-text-field') || source.parentElement;
  function input(parent, label, value, change, options = {}) {
    const wrap = el('div', undefined, 'm3-text-field');
    const labelNode = el('label', label, 'm3-text-field__label');
    const control = el(options.multiline ? 'textarea' : 'input', undefined, 'm3-text-field__input');
    control.id = `question-editor-${++serial}`; labelNode.htmlFor = control.id;
    control.setAttribute('aria-label', label); control.placeholder = ' '; control.value = value ?? '';
    if (!options.multiline) control.type = options.number ? 'number' : 'text';
    control.maxLength = options.max ?? 1000;
    if (options.pattern) control.pattern = options.pattern;
    if (options.number) { control.min = '0'; control.max = '100'; control.step = '1'; }
    if (options.required !== false) control.required = true;
    control.addEventListener('input', () => { change(control.value); sync(); });
    wrap.append(control, labelNode); parent.append(wrap); return control;
  }
  const selectors = [];
  function selection(parent, label, getValue, getOptions, change) {
    const wrap = el('div', undefined, 'm3-text-field m3-text-field--select');
    const control = el('select', undefined, 'm3-text-field__input');
    control.id = `question-editor-${++serial}`; control.required = true;
    const caption = el('label', label, 'm3-text-field__label'); caption.htmlFor = control.id;
    control.addEventListener('change', () => { change(control.value); sync(); });
    wrap.append(control, caption); parent.append(wrap);
    selectors.push({ control, getValue, getOptions });
  }
  function code(parent, row) { return input(parent, words.code, row.code, value => row.code = value, { max: 20, pattern: '[a-z][a-z0-9_]{0,19}' }); }
  function host(source, title) {
    const region = el('section', undefined, 'workspace-section');
    region.append(el('h3', title));
    sourceWrapper(source).before(region); sourceWrapper(source).hidden = true;
    const rows = el('div', undefined, 'panel-record-list'); region.append(rows);
    return { region, rows };
  }
  const optionsHost = host(sources.options, words.options);
  const answersHost = host(sources.answers, words.answers);
  const stepsHost = host(sources.steps, words.steps);
  const labelsHost = host(sources.labels, words.labels);
  const regionsHost = host(sources.regions, words.regions);
  sourceWrapper(sources.mapping).hidden = true;
  function uniqueCode(rows, prefix) { let n = 1; while (rows.some(row => row.code === `${prefix}${n}`)) n++; return `${prefix}${n}`; }
  function rowCard(host, title) {
    const card = el('fieldset', undefined, 'm3-card m3-card--outlined workspace-section');
    card.append(el('legend', title));
    const fields = el('div', undefined, 'panel-form-grid'); card.append(fields); host.append(card);
    return { card, fields };
  }
  function renderPairs(target, rows, title, after) {
    target.replaceChildren();
    rows.forEach((row, index) => {
      const { card, fields } = rowCard(target, `${title} ${index + 1}`);
      code(fields, row); input(fields, words.text, row.text, value => row.text = value, { max: rows === state.labels ? 200 : 1000 });
      button(words.remove, () => { rows.splice(index, 1); renderPairs(target, rows, title, after); sync(); if (after) after(); }, card);
    });
  }
  renderPairs(optionsHost.rows, state.options, words.option);
  const addOption = button(words.addOption, () => { if (state.options.length >= 6) return; state.options.push({ code: uniqueCode(state.options, 'o'), text: '' }); renderPairs(optionsHost.rows, state.options, words.option); sync(); }, optionsHost.region);
  function renderAnswers() {
    answersHost.rows.replaceChildren();
    state.answers.forEach((row, index) => {
      const { card, fields } = rowCard(answersHost.rows, `${words.answer} ${index + 1}`);
      if (kind.value === 'choice') selection(fields, words.correctCode, () => row.answer, () => state.options, value => row.answer = value);
      else input(fields, words.answer, row.answer, value => row.answer = value, { max: kind.value === 'short_text' ? 200 : 400 });
      button(words.remove, () => { state.answers.splice(index, 1); renderAnswers(); sync(); }, card);
    });
  }
  renderAnswers();
  const addAnswer = button(words.addAnswer, () => { if (state.answers.length >= (kind.value === 'short_text' ? 20 : 10)) return; state.answers.push({ answer: '' }); renderAnswers(); sync(); }, answersHost.region);
  function renderSteps() {
    stepsHost.rows.replaceChildren();
    state.steps.forEach((step, index) => {
      const { card, fields } = rowCard(stepsHost.rows, `${words.step} ${index + 1}`);
      input(fields, words.stepPrompt, step.prompt, value => step.prompt = value, { max: 1000, pattern: '[^|]*' });
      input(fields, words.stepAnswers, step.answers, value => step.answers = value, { multiline: true, max: 400 });
      card.append(el('p', words.stepHelp));
      const options = el('div', undefined, 'panel-record-list'); card.append(options);
      const render = () => {
        options.replaceChildren();
        step.options.forEach((option, optionIndex) => {
          const { card: choice, fields: choiceFields } = rowCard(options, `${words.option} ${optionIndex + 1}`);
          code(choiceFields, option); input(choiceFields, words.text, option.text, value => option.text = value, { max: 200, pattern: '[^;|]*' });
          button(words.remove, () => { step.options.splice(optionIndex, 1); render(); sync(); }, choice);
        });
      };
      render();
      button(words.addOption, () => { if (step.options.length >= 6) return; step.options.push({ code: uniqueCode(step.options, 'o'), text: '' }); render(); sync(); }, card);
      button(words.removeStep, () => { state.steps.splice(index, 1); renderSteps(); sync(); }, card);
    });
  }
  renderSteps();
  const addStep = button(words.addStep, () => { if (state.steps.length >= 8) return; state.steps.push({ prompt: '', answers: '', options: [] }); renderSteps(); sync(); }, stepsHost.region);
  renderPairs(labelsHost.rows, state.labels, words.label);
  const addLabel = button(words.addLabel, () => { if (state.labels.length >= 16) return; state.labels.push({ code: uniqueCode(state.labels, 'l'), text: '' }); renderPairs(labelsHost.rows, state.labels, words.label); sync(); }, labelsHost.region);
  function renderRegions() {
    regionsHost.rows.replaceChildren();
    state.regions.forEach((region, index) => {
      const { card, fields } = rowCard(regionsHost.rows, `${words.region} ${index + 1}`);
      input(fields, words.code, region.code, value => { if (value !== region.code) { state.mapping[value] = state.mapping[region.code] ?? ''; delete state.mapping[region.code]; region.code = value; } }, { max: 3, pattern: '[a-z][a-z0-9]{0,2}' });
      input(fields, words.across, region.x, value => region.x = value, { number: true });
      input(fields, words.down, region.y, value => region.y = value, { number: true });
      selection(fields, words.correctLabel, () => state.mapping[region.code] ?? '', () => state.labels, value => state.mapping[region.code] = value);
      button(words.remove, () => { delete state.mapping[region.code]; state.regions.splice(index, 1); renderRegions(); sync(); }, card);
    });
  }
  renderRegions();
  const addRegion = button(words.addRegion, () => { if (state.regions.length >= 12) return; state.regions.push({ code: uniqueCode(state.regions, 'r'), x: '', y: '' }); renderRegions(); sync(); }, regionsHost.region);
  const diagramPreview = form.querySelector('[data-diagram-preview]');
  const diagramPicture = form.querySelector('[data-diagram-picture]');
  const diagramButton = form.querySelector('[data-diagram-add]');
  const diagramPoints = form.querySelector('[data-diagram-points]');
  let localImage;
  const showDiagram = () => { diagramPreview.hidden = !(diagramPicture.complete && diagramPicture.naturalWidth); };
  diagramPicture.addEventListener('load', showDiagram);
  diagramPicture.addEventListener('error', () => diagramPreview.hidden = true);
  form.querySelector('[name=diagram]').addEventListener('change', event => {
    if (localImage) URL.revokeObjectURL(localImage);
    const file = event.target.files[0];
    if (!file) { diagramPreview.hidden = true; return; }
    localImage = URL.createObjectURL(file); diagramPicture.src = localImage;
  });
  diagramButton.addEventListener('click', event => {
    if (state.regions.length >= 12) return;
    const rect = diagramButton.getBoundingClientRect();
    const x = event.detail ? Math.round((event.clientX - rect.left) / rect.width * 100) : 50;
    const y = event.detail ? Math.round((event.clientY - rect.top) / rect.height * 100) : 50;
    state.regions.push({ code: uniqueCode(state.regions, 'r'), x: Math.max(0, Math.min(100, x)), y: Math.max(0, Math.min(100, y)) });
    renderRegions(); sync();
  });
  showDiagram();
  function sync() {
    for (let index = selectors.length - 1; index >= 0; index--) {
      const item = selectors[index];
      if (!item.control.isConnected) { selectors.splice(index, 1); continue; }
      const current = item.getValue();
      item.control.replaceChildren(new Option(words.choose, ''));
      const options = item.getOptions();
      options.forEach(option => item.control.add(new Option(`${option.code} · ${option.text}`, option.code)));
      if (current && !options.some(option => option.code === current)) item.control.add(new Option(`${words.unavailable} (${current})`, current));
      item.control.value = current;
      item.control.setCustomValidity(current && !options.some(option => option.code === current) ? words.unavailable : '');
    }
    diagramPoints.replaceChildren();
    state.regions.forEach(region => {
      if (region.x === '' || region.y === '') return;
      const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      dot.setAttribute('cx', region.x); dot.setAttribute('cy', region.y); dot.setAttribute('r', '2');
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', region.x); text.setAttribute('y', Number(region.y) - 3); text.textContent = region.code;
      diagramPoints.append(dot, text);
    });
    diagramButton.disabled = state.regions.length >= 12;
    const writePairs = rows => rows.map(row => `${row.code}: ${row.text}`).join('\n');
    sources.options.value = writePairs(state.options);
    sources.answers.value = state.answers.map(row => row.answer).join('\n');
    sources.labels.value = writePairs(state.labels);
    sources.regions.value = state.regions.map(row => `${row.code}: ${row.x}, ${row.y}`).join('\n');
    sources.mapping.value = state.regions.map(row => `${row.code}: ${state.mapping[row.code] ?? ''}`).join('\n');
    sources.steps.value = state.steps.map(step => `${step.prompt} | ${lines(step.answers).join(', ')}${step.options.length ? ` | ${step.options.map(option => `${option.code}: ${option.text}`).join('; ')}` : ''}`).join('\n');
    addOption.disabled = state.options.length >= 6; addAnswer.disabled = state.answers.length >= (kind.value === 'short_text' ? 20 : 10);
    addStep.disabled = state.steps.length >= 8; addLabel.disabled = state.labels.length >= 16; addRegion.disabled = state.regions.length >= 12;
  }
  function visibility() {
    form.querySelectorAll('[data-question-kinds]').forEach(section => {
      const active = section.dataset.questionKinds.split(' ').includes(kind.value);
      section.hidden = !active;
      section.querySelectorAll('input,select,textarea,button').forEach(control => { if (!Object.values(sources).includes(control)) control.disabled = !active; });
    });
    optionsHost.region.hidden = kind.value !== 'choice';
    optionsHost.region.querySelectorAll('input,button').forEach(control => control.disabled = kind.value !== 'choice');
    form.querySelectorAll('[data-question-kind-help]').forEach(help => help.hidden = help.dataset.questionKindHelp !== kind.value);
    const audioLimit = form.querySelector('[name=max_seconds]');
    if (audioLimit) audioLimit.required = kind.value === 'audio';
    const diagram = form.querySelector('[name=diagram]');
    if (diagram) diagram.required = kind.value === 'diagram_label' && !form.querySelector('[name=diagram_id]')?.value;
    sync();
  }
  kind.addEventListener('change', () => { renderAnswers(); visibility(); });
  form.addEventListener('submit', sync);
  visibility();
})();
