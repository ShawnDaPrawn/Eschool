<dialog class="m3-dialog" id="leave-record-dialog" aria-labelledby="leave-record-title" data-leave-dialog data-mode="{{ $leaveMode ?? 'own' }}">
    <h2 class="m3-dialog__headline" id="leave-record-title">{{ __('Leave request') }}</h2>
    <div class="m3-dialog__content">
        <dl class="panel-record-summary">
            <dt>{{ __('Person') }}</dt><dd data-leave-person></dd>
            <dt>{{ __('Dates') }}</dt><dd data-leave-range></dd>
            <dt>{{ __('Reason') }}</dt><dd data-leave-reason></dd>
            <dt>{{ __('Status') }}</dt><dd data-leave-state></dd>
            <dt>{{ __('Reason for rejection') }}</dt><dd data-leave-rejection></dd>
        </dl>
        <h3>{{ __('Leave days') }}</h3><ul data-leave-days></ul>
        <h3>{{ __('Supporting documents') }}</h3><ul data-leave-files></ul>
        @if(in_array($leaveMode ?? 'own', ['staff', 'learner'], true))
            <form data-page-form id="leave-decision-form" method="POST" action="{{ route(($leaveMode ?? '') === 'learner' ? 'student-leave-status.update' : 'leave-status.update') }}" data-refresh-table="table_list">
                @csrf<input type="hidden" name="id">
                <div class="m3-banner m3-banner--danger page-form-summary" role="alert" data-form-summary hidden></div>
                <fieldset data-leave-decision>
                    <legend>{{ __('Decision') }}</legend>
                    <x-page.field name="status" type="select" :label="__('Decision')" :options="['0' => __('Pending'), '1' => __('Approve'), '2' => __('Reject')]" required />
                    @if(($leaveMode ?? '') === 'learner')<x-page.field name="reason" type="textarea" :label="__('Reason for rejection')" />@endif
                    <p data-leave-finalized hidden>{{ __('The saved request is read-only. No decision changes are available.') }}</p>
                    <div class="page-form-actions"><button type="submit" class="m3-button m3-button--filled" data-submit>{{ __('Save decision') }}</button></div>
                </fieldset>
            </form>
        @endif
    </div>
    <div class="m3-dialog__actions"><button type="button" class="m3-button m3-button--text" data-m3-dialog-close>{{ __('Close') }}</button></div>
</dialog>
