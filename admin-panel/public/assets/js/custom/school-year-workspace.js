/* School-year cleanup stays separate from global activation and requires a concrete final review. */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-school-year-cleanup]');
        if (!root) return;
        const text = (key) => typeof window.trans === 'function' ? window.trans(key) : key;
        const choice = root.querySelector('[data-year-cleanup-choice]');
        choice?.addEventListener('change', function () {
            root.querySelector('[data-year-cleanup-categories]').hidden = choice.value !== 'categories';
            root.querySelector('[data-year-cleanup-whole]').hidden = choice.value !== 'year';
        });
        root.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                if (form.dataset.cleanupConfirmed === 'true') return;
                event.preventDefault(); event.stopImmediatePropagation();
                const selected = Array.from(form.querySelectorAll('input[name="clear_data[]"]:checked')).map((input) => input.closest('label').textContent.trim());
                if (form.id === 'school-year-clear' && selected.length === 0) {
                    window.M3?.snackbar({message: text('Select at least one data category.'), type: 'error'});
                    return;
                }
                const message = form.id === 'school-year-clear'
                    ? root.dataset.yearName + ': ' + selected.join(', ') + '. ' + text('These selected records will be removed. The school current year stays unchanged.')
                    : root.dataset.yearName + ': ' + text('Remove this school year and its original related records. The school current year stays unchanged.');
                const ok = await window.pageConfirm({title: text('Confirm school-year cleanup'), message, confirmLabel: text('Confirm removal'), danger: true});
                if (!ok) return;
                form.dataset.cleanupConfirmed = 'true';
                form.requestSubmit();
                delete form.dataset.cleanupConfirmed;
            }, true);
            form.addEventListener('page-form:saved', function (event) {
                root.querySelector('[data-year-cleanup-review]').hidden = true;
                const result = root.querySelector('[data-year-cleanup-result]');
                result.textContent = (event.detail?.message || text('School-year cleanup confirmed by the server.')) + ' ' + text('The school current year is unchanged.');
                const link = document.createElement('a');
                link.href = form.querySelector('.page-form-actions a').href;
                link.className = 'm3-button m3-button--outlined';
                link.textContent = text('Return to school years');
                result.appendChild(link); result.hidden = false; result.focus();
            });
        });
    });
}());
