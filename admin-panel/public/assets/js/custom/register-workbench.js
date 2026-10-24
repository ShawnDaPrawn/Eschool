/* Attendance stays unmarked until explicitly captured. No network or persistence here. */
(() => {
    'use strict';
    function initialize() {
        const form = document.querySelector('[data-register-form]');
        if (!form || form.dataset.registerInitialized) return;
        form.dataset.registerInitialized = 'true';
        const rows = Array.from(form.querySelectorAll('[data-register-row]'));
        rows.forEach(row => row.querySelector('[data-register-state]').addEventListener('change', () => { delete row.dataset.scanned; }));
        form.querySelector('[data-mark-present]').addEventListener('click', () => {
            rows.forEach(row => { const state = row.querySelector('[data-register-state]'); if (state.value === 'unmarked') state.value = 'present'; });
        });
        form.querySelector('[data-register-qr]').addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            event.stopPropagation();
            const code = event.target.value.trim();
            const row = rows.find(item => item.dataset.qrCode === code);
            const status = form.querySelector('[data-qr-status]');
            if (row) { row.querySelector('[data-register-state]').value = 'present'; row.dataset.scanned = code; status.textContent = status.dataset.found; }
            else status.textContent = status.dataset.unknown;
            event.target.value = '';
        });
        form.addEventListener('submit', () => {
            form.querySelector('[data-register-payload]').value = JSON.stringify(rows.map(row => ({
                learner_id: Number(row.dataset.learnerId), state: row.querySelector('[data-register-state]').value,
                expected_revision: Number(row.dataset.revision), source: row.dataset.scanned ? 'qr' : 'tap',
                ...(row.dataset.scanned ? {qr_token: row.dataset.scanned} : {})
            })));
            // Keep the bounded JSON request below PHP's max_input_vars even for 500 learners.
            rows.forEach(row => row.querySelectorAll('[name]').forEach(field => { field.disabled = true; }));
        });
        form.querySelector('[data-register-qr]').disabled = false;
        form.querySelector('[data-mark-present]').disabled = false;
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
