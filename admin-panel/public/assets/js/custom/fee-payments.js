(function () {
    'use strict';
    const form = document.querySelector('[data-fee-payment-form]');
    if (!form) return;
    const eligible = JSON.parse(form.dataset.eligible);
    const holder = form.querySelector('[name="holder_user_id"]');
    const pin = form.querySelector('[name="pinned_learner_id"]');
    function update() {
        const ids = eligible[holder.value] || [];
        Array.from(pin.options).forEach(function (option) {
            option.hidden = option.value !== '' && !ids.includes(Number(option.value));
            option.disabled = option.hidden;
        });
        if (pin.selectedOptions.length && pin.selectedOptions[0].disabled) pin.value = '';
    }
    holder.addEventListener('change', update);
    update();
}());
