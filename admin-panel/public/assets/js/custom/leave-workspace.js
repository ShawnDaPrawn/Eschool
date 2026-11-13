/* Leave workspaces use the original school-scoped endpoints with native M3
 * forms/dialogs. Request reasons/files are never written to browser storage. */
(function () {
    'use strict';
    var root = document.querySelector('[data-leave-workspace]');
    if (!root) return;
    function label(key, fallback) { return window.trans ? window.trans(key) || fallback : fallback; }
    function esc(value) { var e = document.createElement('span'); e.textContent = String(value == null ? '' : value); return e.innerHTML; }
    function setText(selector, value) { var e = root.querySelector(selector); if (e) e.textContent = value == null || value === '' ? '—' : String(value); }
    function openDialog(dialog) { if (dialog && !dialog.open) { if (window.M3) M3.dialog.open(dialog); else dialog.showModal(); } }
    window.leaveWorkspaceParams = function (p) {
        return Object.assign({}, p, {session_year_id: document.querySelector('.js-year-switcher')?.value});
    };
    window.leaveWorkspaceStatus = function (_value, row) {
        var states = {'0': ['warning', label('pending', 'Pending')], '1': ['success', label('accepted', 'Approved')], '2': ['danger', label('rejected', 'Rejected')]};
        var state = states[String(row.status)] || ['neutral', String(row.status || '')];
        return '<span class="m3-chip m3-chip--status-' + state[0] + '">' + esc(state[1]) + '</span>';
    };
    window.leaveWorkspaceActions = function (_value, row) {
        var source = String(row.operate || '');
        var result = '<button type="button" class="m3-button m3-button--text" data-leave-record>' + esc(label('view', 'View')) + '</button>';
        if (!root.hasAttribute('data-leave-read-only') && source.includes('delete-form')) result += '<button type="button" class="m3-button m3-button--text m3-button--error" data-leave-delete>' + esc(label('delete', 'Delete')) + '</button>';
        return result;
    };
    function showRecord(row) {
        var configuration = root.hasAttribute('data-leave-configuration');
        if (configuration) {
            var form = document.getElementById('leave-configuration-edit');
            if (!form) return;
            form.reset();
            form.elements.namedItem('edit_id').value = row.id;
            form.elements.namedItem('total_leave').value = row.total_leave;
            form.elements.namedItem('session_year_id').value = row.session_year_id;
            var days = String(row.holiday_days || '').split(',');
            form.querySelectorAll('[name="holiday_days[]"]').forEach(function (field) { field.checked = days.includes(field.value); });
            openDialog(document.getElementById('leave-configuration-dialog'));
            return;
        }
        var dialog = root.querySelector('[data-leave-dialog]');
        if (!dialog) return;
        setText('[data-leave-person]', row.name || label('my', 'My leave'));
        setText('[data-leave-range]', String(row.from_date || '') + ' — ' + String(row.to_date || ''));
        setText('[data-leave-reason]', row.reason);
        setText('[data-leave-rejection]', row.reason_of_rejection);
        setText('[data-leave-state]', {'0': label('pending', 'Pending'), '1': label('accepted', 'Approved'), '2': label('rejected', 'Rejected')}[String(row.status)]);
        var daysList = root.querySelector('[data-leave-days]'); daysList.replaceChildren();
        (row.leave_detail || []).forEach(function (day) { var li = document.createElement('li'); li.textContent = day.date + ' · ' + day.type; daysList.appendChild(li); });
        var files = root.querySelector('[data-leave-files]'); files.replaceChildren();
        (row.file || []).forEach(function (file) {
            var address;
            try { address = new URL(file.file_url, location.href); } catch (_) { return; }
            if (!['https:', 'http:'].includes(address.protocol)) return;
            var li = document.createElement('li'), a = document.createElement('a');
            a.href = address.href; a.target = '_blank'; a.rel = 'noopener noreferrer'; a.textContent = file.file_name || label('attachments', 'Supporting document');
            li.appendChild(a); files.appendChild(li);
        });
        var decision = document.getElementById('leave-decision-form');
        if (decision) {
            decision.reset(); decision.elements.namedItem('id').value = row.id;
            decision.elements.namedItem('status').value = row.status;
            if (decision.elements.namedItem('reason')) decision.elements.namedItem('reason').value = row.reason_of_rejection || '';
            var finalStaff = (dialog.dataset.mode === 'staff' && ['1', '2'].includes(String(row.status))) || !String(row.operate || '').includes('edit-data');
            decision.querySelector('[data-leave-decision]').disabled = finalStaff;
            decision.querySelector('[data-leave-finalized]').hidden = !finalStaff;
            decision.querySelector('[data-form-summary]').hidden = true;
        }
        openDialog(dialog);
    }
    window.leaveWorkspaceEvents = {
        'click [data-leave-record]': function (event, _value, row) { event.preventDefault(); showRecord(row); },
        'click [data-leave-delete]': async function (event, _value, row) {
            event.preventDefault();
            if (!root.dataset.deleteBase || !/^\d+$/.test(String(row.id))) return;
            if (!await window.pageConfirm({title: label('delete', 'Delete'), message: root.hasAttribute('data-leave-configuration') ? 'Remove this monthly leave configuration? Existing leave requests are retained.' : 'Delete this leave request and its day details and attachments? This is not a rejection and cannot be undone.', danger: true})) return;
            var button = event.target.closest('button') || event.target; button.disabled = true;
            try {
                var response = await fetch(root.dataset.deleteBase + '/' + row.id, {method: 'DELETE', credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
                var data = await response.json();
                if (!response.ok || data.error) throw new Error(data.message || label('error_occurred', 'Unable to remove the record.'));
                M3.snackbar({message: data.message, type: 'success'}); PageTable.refresh('table_list');
            } catch (error) { M3.snackbar({message: error.message, type: 'danger'}); } finally { button.disabled = false; }
        }
    };
    document.addEventListener('page-form:saved', function (event) {
        if (['leave-decision-form', 'leave-configuration-edit'].includes(event.target.id)) {
            var dialog = event.target.closest('dialog'); if (dialog) M3.dialog.close(dialog);
        }
    });
    var dateBlock = root.querySelector('[data-leave-dates]');
    if (dateBlock) {
        var from = dateBlock.querySelector('[name="from_date"]'), to = dateBlock.querySelector('[name="to_date"]'), portions = dateBlock.querySelector('[data-leave-portions]');
        var holidays = (dateBlock.dataset.holidays || '').split(','), publicDays = (dateBlock.dataset.publicHolidays || '').split(',');
        var weekday = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        function renderPortions() {
            var retained = {}; portions.querySelectorAll('select').forEach(function (s) { retained[s.name] = s.value; });
            portions.replaceChildren();
            if (from.value) to.min = from.value;
            if (!from.value || !to.value || to.value < from.value) { portions.textContent = portions.dataset.empty; return; }
            var day = new Date(from.value + 'T12:00:00Z'), end = new Date(to.value + 'T12:00:00Z');
            var count = 0;
            while (day <= end && count < 370) {
                var iso = day.toISOString().slice(0, 10);
                if (!holidays.includes(weekday[day.getUTCDay()]) && !publicDays.includes(iso)) {
                    var wrapper = document.createElement('label'); wrapper.className = 'm3-text-field m3-text-field--select';
                    var select = document.createElement('select'); select.className = 'm3-text-field__input'; select.name = 'type[' + iso + '][]'; select.required = true;
                    [['', label('Please Select', 'Choose a portion')], ['Full', portions.dataset.full], ['First Half', portions.dataset.first], ['Second Half', portions.dataset.second]].forEach(function (entry) { var option = document.createElement('option'); option.value = entry[0]; option.textContent = entry[1]; select.appendChild(option); });
                    select.value = retained[select.name] || '';
                    var text = document.createElement('span'); text.className = 'm3-text-field__label'; text.textContent = iso + ' · ' + weekday[day.getUTCDay()];
                    wrapper.append(select, text); portions.appendChild(wrapper);
                }
                day.setUTCDate(day.getUTCDate() + 1); count++;
            }
            if (!portions.children.length) portions.textContent = portions.dataset.noDays;
        }
        from.addEventListener('change', renderPortions); to.addEventListener('change', renderPortions); renderPortions();
        dateBlock.closest('form').addEventListener('submit', function (event) {
            if (!portions.querySelector('select')) { event.preventDefault(); event.stopImmediatePropagation(); M3.snackbar({message: portions.dataset.noDays, type: 'danger'}); }
        }, true);
    }
})();
