/* =========================================================================
   System Update — dropzone interactions
   Progressive enhancement over the native <input type="file">. The existing
   #system-update submit handler in custom.js still does the AJAX upload via
   FormData; this file only manages the drag/drop + selected-file UI and keeps
   the native input's FileList in sync so the upload is unchanged.
   ========================================================================= */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var dropzone = document.querySelector('.sysupd-dropzone');
        var input = document.getElementById('sysupd-file');
        if (!dropzone || !input) {
            return;
        }

        var nameEl = dropzone.querySelector('[data-file-name]');
        var removeBtn = dropzone.querySelector('[data-file-remove]');

        function render() {
            if (input.files && input.files.length) {
                if (nameEl) {
                    nameEl.textContent = input.files[0].name;
                }
                dropzone.classList.add('has-file');
            } else {
                dropzone.classList.remove('has-file');
                if (nameEl) {
                    nameEl.textContent = '';
                }
            }
        }

        input.addEventListener('change', render);

        // The dropzone is a plain <div> (not a <label>) so the remove <button>
        // isn't nested in a label; open the native picker on click instead.
        dropzone.addEventListener('click', function (e) {
            if (e.target.closest('[data-file-remove]')) {
                return;
            }
            input.click();
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                // Keep the click off the dropzone so it doesn't re-open the picker.
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                render();
            });
        }

        function activate(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-dragover');
        }

        function deactivate(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        }

        ['dragenter', 'dragover'].forEach(function (evt) {
            dropzone.addEventListener(evt, activate);
        });
        ['dragleave', 'dragend'].forEach(function (evt) {
            dropzone.addEventListener(evt, deactivate);
        });

        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                // Assigning FileList to a file input is supported in modern
                // browsers; the existing FormData submit then picks it up.
                input.files = e.dataTransfer.files;
                render();
            }
        });

        // Reflect any pre-populated selection (e.g. back/forward cache).
        render();

        // Announce the busy state to assistive tech. formAjaxRequest toggles the
        // submit button's `disabled` attribute around the request; mirror that onto
        // aria-busy + a polite live region (its visible label can't be swapped).
        var form = document.getElementById('system-update');
        var submitBtn = form ? form.querySelector('[type="submit"]') : null;
        var statusEl = form ? form.querySelector('[data-status]') : null;
        if (submitBtn && window.MutationObserver) {
            var loadingText = submitBtn.getAttribute('data-loading-text') || '';
            new MutationObserver(function () {
                var busy = submitBtn.disabled;
                submitBtn.setAttribute('aria-busy', busy ? 'true' : 'false');
                if (statusEl) {
                    statusEl.textContent = busy ? loadingText : '';
                }
            }).observe(submitBtn, { attributes: true, attributeFilter: ['disabled'] });
        }
    });
})();
