/* Versioned curriculum editor. The server revalidates every definition. */
(() => {
  'use strict';
  const root = document.querySelector('[data-curriculum-editor]');
  if (!root) return;
  const options = JSON.parse(root.querySelector('[data-curriculum-state]').textContent);
  const form = root.querySelector('[data-definition-form]');
  const host = root.querySelector('[data-definition-fields]');
  const state = options.definition || { subject_id: options.subjects[0]?.id, grade_level_id: options.grades[0]?.id,
    session_year_id: options.years[0]?.id, title: '', annual_components: null,
    rounding: 'half_up', absent_policy: 'incomplete', topics: [], assessments: [] };
  if (Array.isArray(state.annual_components)) state.annual_components = {};
  state.assessments.forEach(a => { if (Array.isArray(a.components)) a.components = {}; });
  let serial = 0;
  const node = (tag, text, cls) => { const el = document.createElement(tag); if (text !== undefined) el.textContent = text; if (cls) el.className = cls; return el; };
  const button = (parent, label, action) => { const el = node('button', label, 'm3-button m3-button--outlined'); el.type = 'button'; el.addEventListener('click', action); parent.append(el); return el; };
  const field = (parent, label, value, change, cfg = {}) => {
    const wrap = node('label', undefined, 'm3-text-field'); const el = node(cfg.options ? 'select' : 'input', undefined, 'm3-text-field__input');
    el.id = `curriculum-field-${++serial}`; wrap.htmlFor = el.id; el.setAttribute('aria-label', label);
    if (cfg.options) cfg.options.forEach(item => { const o = node('option', item.name); o.value = item.id; el.append(o); });
    else { el.type = cfg.type || 'text'; el.maxLength = cfg.maxLength || 191; }
    el.value = value ?? ''; if (cfg.type === 'number') { el.min = cfg.min ?? 0; el.max = cfg.max ?? 10000; el.step = 1; }
    el.addEventListener('change', () => change(el.value)); wrap.append(el, node('span', label, 'm3-text-field__label')); parent.append(wrap); return el;
  };
  const number = (p, label, obj, key, max) => field(p, label, obj[key], v => { obj[key] = v === '' ? null : Number(v); }, { type: 'number', min: 1, max });
  const weight = (p, label, obj, key) => field(p, label, (obj[key] || 0) / 100, v => {
    const parts = v.split('.'); obj[key] = /^\d{1,3}(\.\d{1,2})?$/.test(v) ? Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0')) : -1;
  }, { maxLength: 6 });
  const section = (p, title) => { const el = node('fieldset', undefined, 'm3-card m3-card--outlined page-section'); el.append(node('legend', title)); p.append(el); return el; };
  const remove = (p, title, fn) => button(p, title, () => { fn(); render(); });
  function components(parent, object, key, title) {
    const box = section(parent, title);
    Object.keys(object[key] || {}).forEach(name => { const row = node('div', undefined, 'page-form-actions'); weight(row, `${name.toUpperCase()} (%)`, object[key], name); remove(row, `Remove ${name}`, () => { delete object[key][name]; }); box.append(row); });
    const entry = field(box, 'New component name (sba, exam, oral)', '', () => {}, { maxLength: 24 });
    button(box, 'Add component', () => {
      if (!/^[a-z][a-z0-9_]{0,23}$/.test(entry.value)) { entry.setCustomValidity('Use a lowercase name beginning with a letter.'); entry.reportValidity(); return; }
      entry.setCustomValidity(''); object[key] ||= {}; if (!Object.hasOwn(object[key], entry.value)) object[key][entry.value] = 0; render();
    });
  }
  function topics(parent, term) {
    const box = section(parent, 'Topics');
    state.topics.filter(t => t.term_id === term.term_id).forEach(topic => {
      const row = section(box, topic.name || 'New topic'); field(row, 'Topic name', topic.name, v => { topic.name = v; });
      number(row, 'From week', topic, 'week_from', 53); number(row, 'To week', topic, 'week_to', 53);
      remove(row, 'Remove topic', () => { state.topics = state.topics.filter(t => t !== topic); state.assessments.forEach(a => a.tasks.forEach(t => { t.topic_keys = t.topic_keys.filter(k => k !== topic.key); t.questions.forEach(q => { q.topic_keys = q.topic_keys.filter(k => k !== topic.key); }); })); });
    });
    button(box, 'Add topic', () => { state.topics.push({ key: crypto.randomUUID(), term_id: term.term_id, name: '', sort_order: state.topics.length, week_from: null, week_to: null }); render(); });
  }
  function tags(parent, target, termId) {
    const box = section(parent, 'Linked topics');
    state.topics.filter(t => t.term_id === termId).forEach(topic => { const label = node('label'); const el = node('input'); el.type = 'checkbox'; el.checked = target.topic_keys.includes(topic.key); el.addEventListener('change', () => { target.topic_keys = target.topic_keys.filter(k => k !== topic.key); if (el.checked) target.topic_keys.push(topic.key); }); label.append(el, document.createTextNode(topic.name || 'Unnamed topic')); box.append(label); });
  }
  function render() {
    host.replaceChildren();
    field(host, 'Curriculum title', state.title, v => { state.title = v; });
    field(host, 'Subject', state.subject_id, v => { state.subject_id = Number(v); }, { options: options.subjects }).disabled = !!state.id;
    field(host, 'Grade', state.grade_level_id, v => { state.grade_level_id = Number(v); render(); }, { options: options.grades }).disabled = !!state.id;
    field(host, 'School year', state.session_year_id, v => { state.session_year_id = Number(v); render(); }, { options: options.years }).disabled = !!state.id;
    const grade = options.grades.find(g => Number(g.id) === Number(state.grade_level_id));
    const refs = options.standards.filter(s => s.grade_from <= grade?.sort_order && s.grade_to >= grade?.sort_order).map(s => ({ id: s.id, name: `${s.code} · ${s.name} · ${s.version}` }));
    field(host, 'Curriculum reference', state.standard_id === undefined ? 'auto' : (state.standard_id ?? ''), v => { if (v === 'auto') delete state.standard_id; else state.standard_id = v ? Number(v) : null; }, { options: [{ id: 'auto', name: 'Use school subject reference when it covers this grade' }, { id: '', name: 'No reference' }, ...refs] });
    field(host, 'Rounding', state.rounding, v => { state.rounding = v; }, { options: [{ id: 'half_up', name: 'Round halves up' }, { id: 'half_even', name: 'Round halves to even' }, { id: 'floor', name: 'Round down' }] });
    field(host, 'Absent task policy', state.absent_policy, v => { state.absent_policy = v; }, { options: [{ id: 'incomplete', name: 'Keep result incomplete' }, { id: 'zero', name: 'Count absent as zero' }] });
    components(host, state, 'annual_components', 'Annual component weights (optional until the annual plan is ready)');
    state.assessments.forEach((a, ai) => {
      const box = section(host, `Term assessment ${ai + 1}`);
      field(box, 'Term', a.term_id, v => { const old = a.term_id; a.term_id = Number(v); state.topics.filter(t => t.term_id === old).forEach(t => { t.term_id = a.term_id; }); render(); }, { options: options.terms.filter(t => Number(t.session_year_id) === Number(state.session_year_id)) });
      components(box, a, 'components', 'Term component weights'); topics(box, a);
      a.tasks.forEach((t, ti) => {
        const task = section(box, `Task ${ti + 1}`);
        field(task, 'Task name', t.name, v => { t.name = v; }); field(task, 'Task type', t.type, v => { t.type = v; }, { maxLength: 40 });
        field(task, 'Term component', t.component, v => { t.component = v; }, { options: Object.keys(a.components).map(k => ({ id: k, name: k.toUpperCase() })) });
        weight(task, 'Weight within term component (%)', t, 'weight_bp'); field(task, 'Annual component (optional)', t.annual_component, v => { t.annual_component = v || null; }, { maxLength: 24 });
        weight(task, 'Weight within annual component (%)', t, 'annual_weight_bp'); field(task, 'Task maximum mark', t.max_mark, v => { t.max_mark = v; }, { maxLength: 10 });
        field(task, 'Due date (optional)', t.due_on, v => { t.due_on = v || null; }, { type: 'date' }); tags(task, t, a.term_id);
        t.questions.forEach((q, qi) => { const question = section(task, `Question ${qi + 1}`); field(question, 'Question number', q.number, v => { q.number = v; }, { maxLength: 24 }); field(question, 'Question maximum mark', q.max_mark, v => { q.max_mark = v; }, { maxLength: 10 }); tags(question, q, a.term_id); remove(question, 'Remove question', () => { t.questions.splice(qi, 1); }); });
        button(task, 'Add question', () => { t.questions.push({ number: String(t.questions.length + 1), max_mark: '1.00', topic_keys: [] }); render(); }); remove(task, 'Remove task', () => { a.tasks.splice(ti, 1); });
      });
      button(box, 'Add task', () => { a.tasks.push({ key: crypto.randomUUID(), name: '', type: 'test', component: Object.keys(a.components)[0] || 'sba', annual_component: null, weight_bp: 10000, annual_weight_bp: 0, max_mark: '100.00', due_on: null, topic_keys: [], questions: [] }); render(); });
      remove(box, 'Remove term assessment', () => { state.assessments.splice(ai, 1); state.topics = state.topics.filter(t => t.term_id !== a.term_id); });
    });
  }
  root.querySelector('[data-add-term]').addEventListener('click', () => { const term = options.terms.find(t => Number(t.session_year_id) === Number(state.session_year_id) && !state.assessments.some(a => a.term_id === t.id)); if (!term) { root.querySelector('[data-add-term]').textContent = 'Create school terms before adding another assessment'; return; } state.assessments.push({ term_id: term.id, components: { sba: 10000 }, tasks: [] }); render(); });
  form.addEventListener('submit', () => { root.querySelector('[data-definition-input]').value = JSON.stringify(state); });
  render();
})();
