/* ==========================================================================
   Reset Password page
   - Per-field password visibility toggles (eye / eye-off Lucide glyphs).
   - Debounced live verification of the current ("old") password.
   - AJAX submit through the shared formAjaxRequest helper.
   Config (URLs + labels) is passed from Blade via data-* attributes.
   ========================================================================== */

(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var form = document.getElementById('reset-password-form');
        if (!form) {
            return;
        }

        /* ---------- Password visibility toggles ---------- */
        form.querySelectorAll('.password-toggle').forEach(function (toggle) {
            function toggleVisibility() {
                var field = toggle.closest('.password-field');
                var input = field ? field.querySelector('input') : null;
                if (!input) {
                    return;
                }
                var reveal = input.getAttribute('type') === 'password';
                input.setAttribute('type', reveal ? 'text' : 'password');
                field.classList.toggle('is-revealed', reveal);
                toggle.setAttribute('aria-pressed', String(reveal));
                toggle.setAttribute(
                    'aria-label',
                    reveal ? (toggle.dataset.hideLabel || '') : (toggle.dataset.showLabel || '')
                );
            }

            // Keep focus on the input when the eye is clicked. Without this the
            // input blurs, and the browser's password manager treats the blur of
            // a current-password field as a cue to pop its "save/update password"
            // prompt. preventDefault on mousedown stops the focus steal; the
            // click event (and Enter/Space on the native <button>) still fires.
            toggle.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            toggle.addEventListener('click', toggleVisibility);
        });

        /* ---------- Live old-password verification ---------- */
        var oldPassword = document.getElementById('old_password');
        var status = document.getElementById('old_password_status');
        var checkUrl = form.dataset.checkUrl;
        var oldPasswordValid = false;
        var debounceTimer = null;

        var iconValid = document.getElementById('pw-icon-valid');
        var iconInvalid = document.getElementById('pw-icon-invalid');

        function setStatus(state, message) {
            if (!status) {
                return;
            }
            status.className = 'password-status' + (state ? ' is-' + state : '');
            if (!message) {
                status.innerHTML = '';
                return;
            }
            var iconHolder = state === 'valid' ? iconValid : (state === 'invalid' ? iconInvalid : null);
            var iconHtml = iconHolder ? '<span class="password-status-icon">' + iconHolder.innerHTML + '</span>' : '';
            status.innerHTML = iconHtml + '<span>' + message + '</span>';
        }

        function verifyOldPassword() {
            if (!oldPassword || !checkUrl) {
                return;
            }
            var value = oldPassword.value;
            if (!value) {
                oldPasswordValid = false;
                setStatus('', '');
                return;
            }
            setStatus('', status.dataset.checkingLabel || '');
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: checkUrl,
                data: { old_password: value },
                success: function (result) {
                    if (result == 1) {
                        oldPasswordValid = true;
                        setStatus('valid', status.dataset.validLabel || '');
                    } else {
                        oldPasswordValid = false;
                        setStatus('invalid', status.dataset.invalidLabel || '');
                    }
                },
                error: function () {
                    oldPasswordValid = false;
                    setStatus('invalid', status.dataset.invalidLabel || '');
                }
            });
        }

        if (oldPassword) {
            // Verify when the user leaves the field...
            oldPassword.addEventListener('blur', verifyOldPassword);
            // ...and debounced while typing, never firing on an empty value.
            oldPassword.addEventListener('input', function () {
                oldPasswordValid = false;
                setStatus('', '');
                clearTimeout(debounceTimer);
                if (oldPassword.value) {
                    debounceTimer = setTimeout(verifyOldPassword, 600);
                }
            });
        }

        /* ---------- Submit ---------- */
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var newPassword = document.getElementById('new_password');
            var confirmPassword = document.getElementById('confirm_password');

            if (!oldPasswordValid) {
                verifyOldPassword();
                if (typeof showErrorToast === 'function') {
                    showErrorToast(status.dataset.invalidLabel || '');
                }
                if (oldPassword) {
                    oldPassword.focus();
                }
                return;
            }

            // The server applies the full rule (letters, numbers, not breached).
            var minLength = parseInt(form.dataset.minLength || '10', 10);
            if (newPassword.value.length < minLength) {
                if (typeof showErrorToast === 'function') {
                    showErrorToast(form.dataset.msgMinLength || '');
                }
                newPassword.focus();
                return;
            }

            if (newPassword.value !== confirmPassword.value) {
                if (typeof showErrorToast === 'function') {
                    showErrorToast(form.dataset.msgMismatch || '');
                }
                confirmPassword.focus();
                return;
            }

            var $form = $(form);
            var submitButton = $form.find(':submit');
            var data = new FormData(form);

            formAjaxRequest('POST', form.getAttribute('action'), data, $form, submitButton, function () {
                form.reset();
                oldPasswordValid = false;
                setStatus('', '');
            });
        });
    });
})();
