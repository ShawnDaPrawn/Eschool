/* Applying a calendar uses the exact school/year template preview currently shown. */
(function () {
    'use strict';
    const form = document.querySelector('[data-calendar-template-review]');
    if (!form) return;
    const select = form.querySelector('[name="template_id"]');
    const apply = form.querySelector('[data-apply-reviewed-calendar]');
    if (!select || !apply) return;
    const update = () => { apply.disabled = !form.dataset.calendarTemplateReview || select.value !== form.dataset.calendarTemplateReview; };
    select.addEventListener('change', update);
    update();
}());
