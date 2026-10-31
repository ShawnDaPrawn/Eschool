// Promote by grade (/promotions/by-grade): typing a homeroom chooses "place",
// and only the learners whose choice differs from the default are sent, so a
// whole school fits in one form. Without this script every choice is sent.
(function () {
    'use strict';
    const form = document.querySelector('[data-grade-promotion]');
    if (!form) {
        return;
    }
    form.addEventListener('input', function (event) {
        const room = event.target;
        if (!(room instanceof HTMLInputElement) || room.list === null || room.value.trim() === '') {
            return;
        }
        const outcome = form.elements.namedItem(room.name.replace('[homeroom]', '[outcome]'));
        if (outcome instanceof HTMLSelectElement) {
            outcome.value = 'place';
        }
    });
    form.addEventListener('submit', function () {
        form.querySelectorAll('[data-default]').forEach(function (control) {
            if (control.value.trim() === control.dataset.default) {
                control.disabled = true;
            }
        });
        // Re-enable after the browser has collected the form, in case the
        // page stays open (a download or a cancelled navigation).
        window.setTimeout(function () {
            form.querySelectorAll('[data-default]').forEach(function (control) {
                control.disabled = false;
            });
        }, 0);
    });
})();
