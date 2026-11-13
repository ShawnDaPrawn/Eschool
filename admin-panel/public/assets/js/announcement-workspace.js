(() => {
    'use strict';
    window.noticeListParams = (params) => ({ ...params, session_year_id: document.getElementById('filter_session_year_global')?.value });
    const root = document.querySelector('[data-notice-workspace]');
    if (!root) return;
    const form = document.getElementById('school-notice-editor');
    if (form) {
        const scope = form.querySelector('#notice-scope');
        const section = form.querySelector('#notice-section');
        const target = form.querySelector('#notice-target');
        const term = form.querySelector('#notice-term');
        const status = form.querySelector('[data-notice-load-status]');
        const retry = form.querySelector('[data-notice-retry]');
        let loadSequence = 0;
        const options = (select, rows, selected, selectedTerm = null) => {
            select.replaceChildren(new Option(root.dataset.chooseLabel, ''));
            for (const row of rows) {
                const isSelected = String(row.id) === String(selected) && (selectedTerm === null || String(row.term_id || '') === String(selectedTerm));
                const option = new Option(row.name, String(row.id), false, isSelected);
                if (row.class_id) option.dataset.class = row.class_id;
                if (row.term_id) option.dataset.term = row.term_id;
                select.add(option);
            }
        };
        const read = async (base, params) => {
            const url = new URL(base, window.location.href);
            Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value ?? ''));
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(root.dataset.loadError);
            return response.json();
        };
        const refresh = async (initial = false) => {
            const sequence = ++loadSequence;
            const kind = scope.value;
            form.querySelector('[data-notice-section]').hidden = kind !== 'class_section';
            form.querySelector('[data-notice-target]').hidden = kind === 'noticeboard';
            section.disabled = kind !== 'class_section';
            section.required = kind === 'class_section';
            target.disabled = kind === 'noticeboard';
            target.required = kind !== 'noticeboard';
            status.hidden = retry.hidden = true;
            if (kind === 'noticeboard') { term.value = ''; return; }
            target.disabled = true;
            try {
                if (kind === 'class_section') {
                    if (initial || section.options.length === 0) {
                        const sections = await read(root.dataset.sectionsUrl, { session_year_id: root.dataset.year });
                        if (sequence !== loadSequence) return;
                        options(section, sections, initial ? section.dataset.selected : section.value, initial ? section.dataset.selectedTerm : null);
                    }
                    const selected = section.selectedOptions[0];
                    term.value = selected?.dataset.term || '';
                    if (!section.value) { options(target, [], ''); target.disabled = false; return; }
                    const subjects = await read(root.dataset.assignUrl, { data: kind, class_id: selected.dataset.class, class_section_id: section.value, term_id: term.value, session_year_id: root.dataset.year });
                    if (sequence !== loadSequence) return;
                    options(target, subjects.map(row => ({ id: row.subject.id, name: row.subject.name })), initial ? target.dataset.selected : '');
                } else {
                    term.value = '';
                    const classes = await read(root.dataset.assignUrl, { data: kind, session_year_id: root.dataset.year });
                    if (sequence !== loadSequence) return;
                    options(target, classes, initial ? target.dataset.selected : '');
                }
                target.disabled = false;
            } catch (error) {
                if (sequence !== loadSequence) return;
                status.textContent = root.dataset.loadError;
                status.hidden = retry.hidden = false;
                target.disabled = true;
            }
        };
        scope.addEventListener('change', () => refresh());
        section.addEventListener('change', () => refresh());
        retry.addEventListener('click', () => refresh(true));
        form.addEventListener('submit', (event) => {
            if (scope.value !== 'noticeboard' && (target.disabled || !target.value)) {
                event.preventDefault(); event.stopImmediatePropagation();
                status.textContent = target.disabled ? root.dataset.loadError : root.dataset.chooseLabel;
                status.hidden = false;
                target.disabled ? retry.focus() : target.focus();
            }
        }, true);
        refresh(true);
    }
    root.addEventListener('click', async (event) => {
        const action = event.target.closest('[data-notice-delete]');
        if (!action) return;
        event.preventDefault();
        if (action.getAttribute('aria-disabled') === 'true') return;
        if (!await window.pageConfirm({ title: action.textContent.trim(), message: root.dataset.deleteMessage, danger: true })) return;
        action.setAttribute('aria-disabled', 'true');
        try {
            const response = await fetch(action.href, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
            const payload = await response.json();
            if (!response.ok || payload.error) throw new Error(payload.message || root.dataset.deleteError);
            await window.PageTable.refresh('school-notices');
        } catch (error) {
            const status = document.getElementById('notice-action-status');
            status.hidden = false; status.textContent = error.message; status.focus(); action.removeAttribute('aria-disabled');
        }
    });
})();
