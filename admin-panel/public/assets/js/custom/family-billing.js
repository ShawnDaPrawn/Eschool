(function () {
    'use strict';
    const form = document.querySelector('[data-family-account-form]');
    if (!form) return;
    const holders = document.getElementById('billing-holders');
    const template = document.getElementById('billing-holder-template');
    form.addEventListener('input', function () {
        holders.querySelectorAll('input').forEach(function (input) { input.setCustomValidity(''); });
    });
    form.addEventListener('click', function (event) {
        const add = event.target.closest('[data-add-holder]');
        const remove = event.target.closest('[data-remove-holder]');
        if (add && holders.children.length < 20) {
            const index = Number(holders.dataset.nextIndex);
            holders.dataset.nextIndex = String(index + 1);
            holders.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__index__', String(index)).replaceAll('__number__', String(index + 1)));
            holders.lastElementChild.querySelector('select').focus();
        }
        if (remove && holders.children.length > 1) remove.closest('[data-billing-holder]').remove();
    });
    form.addEventListener('submit', function (event) {
        let total = 0;
        let invalid = null;
        holders.querySelectorAll('[data-billing-holder]').forEach(function (row) {
            const person = row.querySelector('select');
            const share = row.querySelector('input');
            if (!person.value && !share.value) return;
            const match = /^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/.test(share.value);
            const parts = share.value.split('.');
            const bp = match ? Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0')) : 0;
            if (!person.value || !match || bp <= 0) invalid = share;
            total += bp;
        });
        if (total !== 10000) invalid = invalid || holders.querySelector('input');
        if (invalid) {
            event.preventDefault();
            invalid.setCustomValidity(form.dataset.holderError);
            invalid.reportValidity();
        }
    });
    const search = form.querySelector('[data-billing-learner-search]');
    const learners = document.getElementById('billing-learners');
    search.addEventListener('input', function () {
        const query = search.value.toLocaleLowerCase();
        Array.from(learners.options).forEach(function (option) { option.hidden = !option.selected && !option.text.toLocaleLowerCase().includes(query); });
    });
}());
