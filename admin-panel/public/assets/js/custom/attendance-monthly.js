(function () {
    'use strict';
    var root = document.querySelector('[data-attendance-monthly]');
    if (!root) return;
    var table = root.querySelector('table');
    var month = root.querySelector('[name="month"]');
    var group = root.querySelector('[name="class_section_id"]');
    var message = root.querySelector('[data-attendance-message]');
    var version = 0;
    function year() { return document.querySelector('#filter_session_year_global')?.value || ''; }
    function get(url, query) { return fetch(url + '?' + new URLSearchParams(query), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (response) { if (!response.ok) throw new Error(); return response.json(); }); }
    function load() {
        var mine = ++version;
        table.tHead.replaceChildren(); table.tBodies[0].replaceChildren();
        if (!group.value || !month.value) { message.textContent = 'Choose a class and month.'; return; }
        table.setAttribute('aria-busy', 'true'); message.textContent = 'Loading attendance…';
        get(root.dataset.attendanceUrl, { class_section_id: group.value, month: month.value, session_year_id: year() }).then(function (data) {
            if (mine !== version) return;
            var parts = month.value.split('-'); var days = new Date(Number(parts[0]), Number(parts[1]), 0).getDate();
            var head = document.createElement('tr'); var learner = document.createElement('th'); learner.scope = 'col'; learner.textContent = 'Learner'; head.appendChild(learner);
            for (var day = 1; day <= days; day++) { var th = document.createElement('th'); th.scope = 'col'; th.textContent = day; head.appendChild(th); }
            table.tHead.appendChild(head);
            var names = { P: 'Present', A: 'Absent', HD: 'Half day', H: 'Holiday', L: 'Leave' };
            var colors = { P: 'dot-present', A: 'dot-absent', HD: 'dot-half', H: 'dot-holiday', L: 'dot-leave' };
            (data.students || []).forEach(function (student) {
                var row = document.createElement('tr'); var name = document.createElement('th'); name.scope = 'row'; name.textContent = student.full_name; row.appendChild(name);
                for (var day = 1; day <= days; day++) {
                    var code = data.attendance?.[student.id]?.[day] || '—';
                    var cell = document.createElement('td'); cell.title = (names[code] || 'No record') + ' · ' + day;
                    var dot = document.createElement('span'); dot.className = 'attendance-dot ' + (colors[code] || 'dot-none'); dot.setAttribute('aria-hidden', 'true'); cell.appendChild(dot);
                    cell.appendChild(document.createTextNode(' ' + code)); cell.setAttribute('aria-label', cell.title); row.appendChild(cell);
                }
                table.tBodies[0].appendChild(row);
            });
            message.textContent = (data.students || []).length ? '' : 'No learners match this class and month.';
        }).catch(function () { if (mine === version) message.textContent = 'Attendance could not be loaded. Use Refresh to try again.'; }).finally(function () { if (mine === version) table.setAttribute('aria-busy', 'false'); });
    }
    group.addEventListener('change', load); month.addEventListener('change', load); root.querySelector('[data-attendance-refresh]').addEventListener('click', load);
    get(root.dataset.monthsUrl, { session_year_id: year() }).then(function (data) {
        var current = month.value; month.replaceChildren();
        (data.months || []).forEach(function (row) { month.appendChild(new Option(row.text, row.value)); });
        if (Array.from(month.options).some(function (option) { return option.value === current; })) month.value = current;
        load();
    }).catch(function () { message.textContent = 'School year months could not be loaded. Refresh the page to try again.'; });
}());
