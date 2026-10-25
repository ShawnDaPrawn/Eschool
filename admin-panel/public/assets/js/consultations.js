(() => {
  'use strict';
  const root = document.querySelector('[data-consultations-root]');
  if (!root) return;
  let erased = false;
  let generation = 0;
  let pending;
  const editor = root.querySelector('[data-consultation-editor]');
  const picker = root.querySelector('[data-audience-picker]');
  const status = root.querySelector('[data-editor-status]');
  const say = key => { if (status) status.textContent = root.dataset[key] || ''; };
  const erase = () => {
    erased = true;
    generation += 1;
    pending?.abort();
    root.querySelectorAll('input,textarea').forEach(field => { field.value = ''; field.removeAttribute('value'); if (field.tagName === 'TEXTAREA') field.textContent = ''; });
    root.querySelectorAll('select,form').forEach(element => element.replaceChildren());
    root.replaceChildren();
  };
  document.addEventListener('visibilitychange', () => { if (document.hidden) erase(); else if (erased) location.reload(); });
  window.addEventListener('pagehide', erase);
  window.addEventListener('pageshow', event => { if (event.persisted || erased) location.reload(); });
  if (!editor || editor.querySelector('fieldset')?.disabled) return;
  const questions = editor.querySelector('[data-questions]');
  const type = editor.querySelector('[data-consultation-type]');
  const questionTemplate = root.querySelector('[data-question-template]');
  const optionTemplate = root.querySelector('[data-option-template]');
  let nextQuestion = questions.querySelectorAll('[data-question]').length;
  const refresh = question => {
    const kind = question.querySelector('[data-question-kind]').value;
    const options = question.querySelector('[data-options]');
    options.hidden = kind === 'text';
    options.querySelectorAll('input,textarea').forEach(field => { field.disabled = kind === 'text'; });
    question.querySelector('[data-add-option]').hidden = kind === 'text';
    question.querySelector('[data-max-choices]').hidden = kind !== 'multiple';
    question.querySelector('[data-max-choices] input').disabled = kind !== 'multiple';
    options.querySelectorAll('[data-candidate-statement]').forEach(label => {
      label.hidden = type.value !== 'election';
      label.querySelector('textarea').disabled = type.value !== 'election' || kind === 'text';
    });
  };
  const addOption = question => {
    if (question.querySelectorAll('[data-option]').length >= 50) { say('choiceLimit'); return; }
    const next = Number(question.dataset.nextOption || question.querySelectorAll('[data-option]').length);
    question.dataset.nextOption = String(next + 1);
    const box = document.createElement('div');
    box.innerHTML = optionTemplate.innerHTML.replaceAll('__Q__', question.dataset.index).replaceAll('__O__', String(next));
    question.querySelector('[data-options]').append(box.firstElementChild);
    refresh(question);
  };
  questions.querySelectorAll('[data-question]').forEach(question => { question.dataset.nextOption = String(question.querySelectorAll('[data-option]').length); refresh(question); });
  type.addEventListener('change', () => { questions.querySelectorAll('[data-question]').forEach(refresh); });
  root.addEventListener('change', event => {
    if (event.target.matches('[data-question-kind]')) refresh(event.target.closest('[data-question]'));
  });
  root.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || erased) return;
    if (button.matches('[data-add-question]')) {
      if (questions.querySelectorAll('[data-question]').length >= 20) { say('questionLimit'); return; }
      const box = document.createElement('div');
      box.innerHTML = questionTemplate.innerHTML.replaceAll('__Q__', String(nextQuestion++));
      const question = box.firstElementChild;
      questions.append(question);
      addOption(question); addOption(question);
      question.querySelector('textarea').focus();
    } else if (button.matches('[data-remove-question]')) {
      if (questions.querySelectorAll('[data-question]').length <= 1) { say('questionRequired'); return; }
      button.closest('[data-question]').remove();
    } else if (button.matches('[data-add-option]')) {
      addOption(button.closest('[data-question]'));
    } else if (button.matches('[data-remove-option]')) {
      button.closest('[data-option]').remove();
    }
  });
  if (!picker) return;
  const selectedKind = editor.querySelector('[data-selected-kind]');
  const target = editor.querySelector('#audience-target');
  const targetLabel = editor.querySelector('[data-audience-target]');
  const staffLabel = editor.querySelector('[data-staff-audience]');
  const staff = staffLabel?.querySelector('input');
  const setStaff = kind => {
    if (!staffLabel || !staff) return;
    const permitted = ['school','activity_group','custom'].includes(kind);
    staffLabel.hidden = !permitted; staff.disabled = !permitted;
    if (!permitted) staff.checked = false;
  };
  const pickerStatus = picker.querySelector('[data-audience-status]');
  const save = editor.querySelector('[data-save-consultation]');
  const previous = picker.querySelector('[data-audience-previous]');
  const next = picker.querySelector('[data-audience-next]');
  let page = 1;
  let lastPage = 1;
  const load = async desiredPage => {
    const kind = picker.elements.kind.value;
    generation += 1;
    const local = generation;
    pending?.abort();
    pending = new AbortController();
    save.disabled = true;
    pickerStatus.textContent = '';
    if (kind === 'school') {
      selectedKind.value = kind; setStaff(kind);
      target.replaceChildren(); target.disabled = true; target.required = false; targetLabel.hidden = true;
      previous.hidden = true; next.hidden = true; save.disabled = false;
      return;
    }
    const url = new URL('/api/v2/consultations/audiences', location.origin);
    url.searchParams.set('session_year_id', root.dataset.year);
    url.searchParams.set('kind', kind);
    url.searchParams.set('page', String(desiredPage));
    if (picker.elements.search.value.trim()) url.searchParams.set('search', picker.elements.search.value.trim());
    if (kind === selectedKind.value && target.value) url.searchParams.set('selected', target.value);
    try {
      const response = await fetch(url, {credentials: 'same-origin', referrerPolicy: 'same-origin', cache: 'no-store', signal: pending.signal,
        headers: {'Accept': 'application/json', 'X-School-Id': root.dataset.school, 'X-Account-Id': root.dataset.account}});
      if (local !== generation || erased) return;
      if ([401,403,404,419].includes(response.status) || response.redirected) { erase(); return; }
      if (!response.ok || !response.headers.get('Content-Type')?.includes('application/json')) throw new Error('Unavailable audience');
      const packet = await response.json();
      if (local !== generation || erased) return;
      if (packet.error !== false || !Array.isArray(packet.data?.items)) throw new Error('Invalid audience');
      const data = packet.data;
      const rows = [...data.items];
      if (data.selected_id && !rows.some(row => row.id === data.selected_id)) rows.unshift({id: data.selected_id, name: data.selected_name});
      target.replaceChildren(new Option(root.dataset.chooseAudience, ''));
      rows.forEach(row => { target.add(new Option(String(row.name), String(row.id), false, row.id === data.selected_id)); });
      selectedKind.value = kind; setStaff(kind); target.disabled = false; target.required = true; targetLabel.hidden = false;
      page = data.page; lastPage = data.last_page;
      previous.hidden = page <= 1; next.hidden = page >= lastPage;
      if (rows.length === 0) pickerStatus.textContent = root.dataset.noAudience;
    } catch (error) {
      if (error.name !== 'AbortError' && local === generation && !erased) pickerStatus.textContent = root.dataset.audienceRetry;
    } finally {
      if (local === generation && !erased) save.disabled = false;
    }
  };
  target.required = selectedKind.value !== 'school';
  setStaff(selectedKind.value);
  picker.addEventListener('submit', event => { event.preventDefault(); load(1); });
  previous.addEventListener('click', () => load(Math.max(1,page-1)));
  next.addEventListener('click', () => load(Math.min(lastPage,page+1)));
})();
