(function () {
    'use strict';
    function append(container, template, token) {
        const index = Number(container.dataset.nextIndex || 0);
        container.dataset.nextIndex = String(index + 1);
        container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll(token, String(index)));
        container.lastElementChild.querySelector('input,select,textarea')?.focus();
    }
    document.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (!button) return;
        const rules = button.closest('[data-fee-rules]');
        const options = button.closest('[data-payment-options]');
        const option = button.closest('[data-payment-option]');
        if (button.matches('[data-add-rule]') && rules) {
            const rows = rules.querySelector('[data-rule-rows]');
            if (rows.children.length < 20) append(rows, rules.querySelector('[data-rule-template]'), '__row__');
        }
        if (button.matches('[data-remove-rule]')) button.closest('[data-fee-rule]').remove();
        if (button.matches('[data-add-option]') && options) {
            const rows = options.querySelector('[data-option-rows]');
            if (rows.children.length < 6) append(rows, options.querySelector('[data-option-template]'), '__option__');
        }
        if (button.matches('[data-remove-option]') && option) option.remove();
        if (button.matches('[data-add-installment]') && option) {
            const rows = option.querySelector('[data-installment-rows]');
            if (rows.children.length < 24) append(rows, option.querySelector('[data-installment-template]'), '__row__');
        }
        if (button.matches('[data-remove-installment]')) button.closest('[data-installment]').remove();
    });
}());
