'use strict';
const eventWorkspaceLabel = (message) => typeof window.trans === 'function' ? window.trans(message) : message;
window.eventListParams = (params) => ({ ...params, session_year_id: document.getElementById('filter_session_year_global')?.value });
document.addEventListener('click', async (event) => {
    const action = event.target.closest('[data-event-delete]');
    if (!action) return;
    event.preventDefault();
    if (action.getAttribute('aria-disabled') === 'true') return;
    const accepted = await window.pageConfirm({ title: action.textContent.trim(), message: action.dataset.confirmMessage, danger: true });
    if (!accepted) return;
    action.setAttribute('aria-disabled', 'true');
    try {
        const response = await fetch(action.href, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
        const payload = await response.json();
        if (!response.ok || payload.error) throw new Error(payload.message || action.dataset.errorMessage);
        await window.PageTable.refresh('school-events');
    } catch (error) {
        const status = document.getElementById('event-action-status');
        status.hidden = false;
        status.textContent = error.message;
        status.focus();
        action.removeAttribute('aria-disabled');
    }
});
document.querySelectorAll('[data-event-editor]').forEach((container) => {
    const form = container.querySelector('form');
    const type = form.querySelector('[data-event-type]');
    const single = form.querySelector('[data-event-single]');
    const multiple = form.querySelector('[data-event-multiple]');
    const rows = form.querySelector('[data-programme-rows]');
    let sequence = rows?.children.length || 0;
    const pendingControls = new Map();
    const programmeSaving = () => form.getAttribute('aria-busy') === 'true' || form.hasAttribute('data-page-form-committed');
    const syncProgrammeControls = () => {
        if (programmeSaving()) {
            form.querySelectorAll('[data-programme-add], [data-programme-remove]').forEach((button) => {
                if (!pendingControls.has(button)) pendingControls.set(button, button.disabled);
                button.disabled = true;
            });
        } else {
            pendingControls.forEach((disabled, button) => { button.disabled = disabled; });
            pendingControls.clear();
        }
    };
    new MutationObserver(syncProgrammeControls).observe(form, { attributes: true, attributeFilter: ['aria-busy'] });
    syncProgrammeControls();
    const update = () => {
        if (type && single && multiple) {
            single.hidden = single.disabled = type.value !== 'single';
            multiple.hidden = multiple.disabled = type.value !== 'multiple';
        }
        const start = form.querySelector('[data-range-start]');
        const end = form.querySelector('[data-range-end]');
        const range = form.querySelector('[data-range-value]');
        if (start && end && range) {
            range.value = start.value && end.value ? `${start.value} - ${end.value}` : '';
            end.min = start.value;
            rows?.querySelectorAll('input[type=date]').forEach((input) => { input.min = start.value; input.max = end.value; });
        }
        form.querySelectorAll('[data-event-time-pair]').forEach((pair) => {
            const first = pair.querySelector('[data-time-start]');
            const last = pair.querySelector('[data-time-end]');
            const result = pair.querySelector('[data-time-value]');
            const seconds = (value) => value.length === 5 ? `${value}:00` : value;
            result.value = first.value && last.value ? `${seconds(first.value)} - ${seconds(last.value)}` : '';
            if (!pair.hasAttribute('data-optional-time')) pair.dataset.optionalTime = String(!first.required);
            if (pair.dataset.optionalTime === 'true') first.required = last.required = Boolean(first.value || last.value);
        });
    };
    form.addEventListener('change', update);
    form.addEventListener('input', update);
    form.addEventListener('submit', (event) => {
        update();
        if (!form.reportValidity()) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    form.querySelector('[data-programme-add]')?.addEventListener('click', () => {
        if (programmeSaving()) return;
        const template = form.querySelector('[data-programme-template]');
        const copy = document.createElement('template');
        copy.innerHTML = template.innerHTML.replaceAll('__ROW__', String(++sequence));
        const row = copy.content.firstElementChild;
        rows.append(row);
        update();
        row.querySelector('input')?.focus();
    });
    form.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-programme-remove]');
        if (!button) return;
        if (programmeSaving()) return;
        const row = button.closest('[data-programme-row]');
        const status = row.querySelector('[data-programme-status]');
        if (rows.children.length === 1) {
            status.hidden = false;
            status.textContent = eventWorkspaceLabel('Keep at least one programme item.');
            return;
        }
        if (button.dataset.removeUrl) {
            const accepted = await window.pageConfirm({ title: button.textContent.trim(), message: eventWorkspaceLabel('This deletes the saved programme item immediately.'), danger: true });
            if (!accepted) return;
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.removeUrl, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                const payload = await response.json();
                if (!response.ok || payload.error) throw new Error(payload.message || eventWorkspaceLabel('The programme item could not be deleted.'));
            } catch (error) {
                status.hidden = false; status.textContent = error.message; button.disabled = false; return;
            }
        }
        row.remove();
        form.querySelector('[data-programme-add]')?.focus();
    });
    update();
});
