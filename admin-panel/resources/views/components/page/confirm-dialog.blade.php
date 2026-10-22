{{--
    The panel's one confirm dialog (a Material 3 dialog, a native <dialog>
    opened as a modal), included by layouts/master. Ask with data attributes on
    a button or link:

        <button type="submit" data-confirm="{{ __('make_current_confirm') }}"
            data-confirm-title="{{ __('make_it_current') }}" data-confirm-label="{{ __('make_it_current') }}">

    or from page code: pageConfirm({title, message, confirmLabel, danger}).then(ok => ...).
    Focus moves into the dialog and back to the button; Escape cancels.
--}}
<dialog class="m3-dialog page-confirm" id="page-confirm" role="alertdialog" aria-modal="true"
    aria-labelledby="page-confirm-title" aria-describedby="page-confirm-message">
    <form method="dialog" class="m3-dialog__form" data-year-safe>
        <h2 class="m3-dialog__headline" id="page-confirm-title">{{ __('page_confirm_title') }}</h2>
        <div class="m3-dialog__content">
            <p id="page-confirm-message" class="page-confirm__message"></p>
        </div>
        <div class="m3-dialog__actions">
            <button type="submit" class="m3-button m3-button--text" value="cancel" data-confirm-cancel>{{ __('cancel') }}</button>
            <button type="submit" class="m3-button m3-button--filled" value="confirm" data-confirm-ok>{{ __('page_confirm_ok') }}</button>
        </div>
    </form>
</dialog>
