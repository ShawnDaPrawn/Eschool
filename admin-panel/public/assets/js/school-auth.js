'use strict';
document.querySelectorAll('[data-auth-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    button.addEventListener('click', () => {
        const shown = input.type === 'password';
        input.type = shown ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(shown));
        button.textContent = shown ? button.dataset.hideLabel : button.dataset.showLabel;
    });
});
document.querySelectorAll('[data-demo-account]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('frmLogin');
        form.elements.email.value = button.dataset.demoEmail;
        form.elements.password.value = button.dataset.demoPassword;
        form.requestSubmit();
    });
});
