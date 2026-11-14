(function () {
    'use strict';
    var root = document.querySelector('[data-attendance-workspace]');
    if (!root) return;
    var mode = root.dataset.attendanceWorkspace;
    var message = root.querySelector('[data-attendance-message]');
    function say(text) { if (message) message.textContent = text; }
    function text(key) { return window.trans ? window.trans(key) : key; }
    function year() { return document.querySelector('#filter_session_year_global')?.value || ''; }
    function iso(value) { return /^\d{2}-\d{2}-\d{4}$/.test(value || '') ? value.split('-').reverse().join('-') : value; }
    function get(url, params) {
        return fetch(url + '?' + new URLSearchParams(params), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) {
            if (!r.ok) throw new Error(text('Unable to load attendance. Try again.'));
            return r.json();
        }).then(function (data) { if (data && data.error) throw new Error(data.message); return data; });
    }
    window.attendanceWorkspaceParams = function (p) {
        p.session_year_id = year();
        var select = root.querySelector('[name="class_section_id"]');
        if (select) {
            p.class_section_id = select.value;
            p.term_id = select.selectedOptions[0]?.dataset.term || '';
        }
        return p;
    };
    if (mode === 'report' || mode === 'records') {
        var classSelect = root.querySelector('[name="class_section_id"]');
        var date = root.querySelector('[name="date"]');
        function bounds() {
            if (!date) return;
            var chosen = classSelect.selectedOptions[0];
            var selectedYear = document.querySelector('#filter_session_year_global')?.selectedOptions[0];
            date.min = iso(chosen?.dataset.start || selectedYear?.dataset.startDate || '');
            date.max = iso(chosen?.dataset.end || selectedYear?.dataset.endDate || '');
            if (date.value && ((date.min && date.value < date.min) || (date.max && date.value > date.max))) date.value = '';
        }
        function classes() {
            classSelect.disabled = true;
            get(root.dataset.classOptionsUrl, { session_year_id: year(), scope: root.dataset.classScope }).then(function (rows) {
                classSelect.replaceChildren(new Option(text('Choose a class'), ''));
                rows.forEach(function (row) {
                    var option = new Option(row.label || row.name || '', row.class_section_id);
                    option.dataset.term = row.term_id || '';
                    option.dataset.start = row.term_start_date || '';
                    option.dataset.end = row.term_end_date || '';
                    classSelect.appendChild(option);
                });
                bounds(); say('');
            }).catch(function (error) { say(error.message); }).finally(function () { classSelect.disabled = false; });
        }
        classSelect.addEventListener('change', bounds);
        classes();
    }
    root.querySelector('[data-attendance-export]')?.addEventListener('click', async function (event) {
        var button = event.currentTarget;
        if (!root.querySelector('[name="class_section_id"]')?.value) { say(text('Choose a class before exporting.')); return; }
        button.disabled = true;
        var scope = window.attendanceWorkspaceParams({offset: 0, limit: 200, sort: 'id', order: 'asc'});
        var rows = [];
        try {
            while (true) {
                var data = await get(button.dataset.attendanceExport, scope);
                if (!Array.isArray(data.rows)) throw new Error(data.message || text('Unable to export attendance.'));
                rows = rows.concat(data.rows);
                if (rows.length >= Number(data.total || 0) || data.rows.length === 0) break;
                if (rows.length >= 10000) throw new Error(text('Narrow the selected class before exporting more than 10000 learners.'));
                scope.offset = rows.length;
            }
            var fields = ['name','admission_no','roll_no','total_days','present_days','absent_days','percentage'];
            var headings = ['Learner','Admission number','Roll number','Working days','Present days','Absent days','Attendance percentage'];
            function cell(value) {
                var valueText = String(value ?? '');
                if (/^[=+@\-\t\r]/.test(valueText)) valueText = "'" + valueText;
                return '"' + valueText.replace(/"/g, '""') + '"';
            }
            var csv = [headings.map(cell).join(',')].concat(rows.map(function (row) { return fields.map(function (field) { return cell(row[field]); }).join(','); })).join('\r\n');
            var blob = new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8'});
            var url = URL.createObjectURL(blob); var link = document.createElement('a');
            link.href = url; link.download = 'attendance-report-' + scope.session_year_id + '.csv'; link.click(); URL.revokeObjectURL(url);
            say(text('Attendance report downloaded.'));
        } catch (error) { say(error.message); } finally { button.disabled = false; }
    });
    if (mode !== 'capture') return;
    var form = document.querySelector('#daily-attendance-form');
    var selectedClass = document.querySelector('#capture-class');
    var captureDate = document.querySelector('#capture-date');
    var datePayload = document.querySelector('#capture-date-payload');
    var holiday = document.querySelector('#capture-holiday');
    var body = document.querySelector('#capture-roster');
    var save = form.querySelector('[data-submit]');
    var request = 0;
    var loaded = false;
    function holidayMode() {
        body.querySelectorAll('input[type="radio"]').forEach(function (input) { input.disabled = holiday.checked; });
    }
    function refresh() {
        var mine = ++request;
        loaded = false; save.disabled = true; body.replaceChildren();
        datePayload.value = captureDate.value ? captureDate.value.split('-').reverse().join('-') : '';
        if (!selectedClass.value || !captureDate.value) { say(text('Choose a class and date to load the register.')); return; }
        say(text('Loading register…'));
        var query = { class_section_id: selectedClass.value, date: datePayload.value, limit: 200, offset: 0, sort: 'roll_number', order: 'asc' };
        var roster = [];
        function nextPage() {
            return get(root.dataset.rosterUrl, query).then(function (data) {
                if (mine !== request) return null;
                var rows = data.rows || [];
                roster = roster.concat(rows);
                if (roster.length < Number(data.total || 0) && rows.length) { query.offset = roster.length; return nextPage(); }
                return roster;
            });
        }
        Promise.all([nextPage(), get(root.dataset.holidayUrl, query)]).then(function (result) {
            if (mine !== request || !result[0]) return;
            result[0].forEach(function (row) {
                var tr = document.createElement('tr');
                ['name', 'admission_no', 'roll_no'].forEach(function (key) { var td = document.createElement('td'); td.textContent = row[key] || ''; tr.appendChild(td); });
                var cell = document.createElement('td');
                // This field is the server's existing fixed radio/approved-leave markup.
                cell.innerHTML = row.type || '';
                var learner = document.createElement('input'); learner.type = 'hidden'; learner.name = 'student_id[]'; learner.value = row.id; cell.appendChild(learner);
                tr.appendChild(cell); body.appendChild(tr);
            });
            holiday.checked = Number(result[1]) === 3;
            holidayMode(); loaded = result[0].length > 0; save.disabled = !loaded;
            say(loaded ? text('Review all learners before saving. Unmarked learners default to present.') : text('No learners are enrolled in this class for the current year.'));
        }).catch(function (error) { if (mine === request) say(error.message); });
    }
    selectedClass.addEventListener('change', function () {
        var option = selectedClass.selectedOptions[0];
        if (option?.dataset.start) captureDate.min = iso(option.dataset.start);
        if (option?.dataset.end) captureDate.max = [iso(option.dataset.end), new Date().toISOString().slice(0,10)].sort()[0];
        if (captureDate.value && (captureDate.value < captureDate.min || captureDate.value > captureDate.max)) captureDate.value = '';
        refresh();
    });
    captureDate.addEventListener('change', refresh);
    holiday.addEventListener('change', function () {
        if (!loaded) { holiday.checked = false; return; }
        if (holiday.checked && window.pageConfirm) {
            window.pageConfirm({title: text('Record holiday'), message: text('Record this class date as a holiday for every learner?')}).then(function (confirmed) { holiday.checked = confirmed; holidayMode(); });
        } else holidayMode();
    });
    form.addEventListener('submit', function (event) {
        if (!loaded || (year() && year() !== root.dataset.currentYear)) {
            event.preventDefault(); event.stopImmediatePropagation();
            say(text('Choose the current school year, a class and date before saving attendance.'));
        }
    }, true);
    form.addEventListener('page-form:saved', refresh);
    save.disabled = true;
}());
