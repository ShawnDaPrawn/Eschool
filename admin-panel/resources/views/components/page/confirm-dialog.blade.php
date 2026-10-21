{{--
    The panel's one confirm dialog, included by layouts/master. Ask with
    data attributes on a button or link:

        <button type="submit" data-confirm="{{ __('make_current_confirm') }}"
            data-confirm-title="{{ __('make_it_current') }}" data-confirm-label="{{ __('make_it_current') }}">

    or from page code: pageConfirm({title, message, confirmLabel, danger}).then(ok => ...).
    Focus moves into the dialog and back to the button; Escape cancels.
--}}
<div class="modal fade page-confirm" id="page-confirm" tabindex="-1" role="dialog" aria-modal="true"
    aria-labelledby="page-confirm-title" aria-describedby="page-confirm-message" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="page-confirm-title">{{ __('page_confirm_title') }}</h2>
            </div>
            <div class="modal-body">
                <p id="page-confirm-message" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-confirm-cancel>{{ __('cancel') }}</button>
                <button type="button" class="btn btn-theme" data-confirm-ok>{{ __('page_confirm_ok') }}</button>
            </div>
        </div>
    </div>
</div>
