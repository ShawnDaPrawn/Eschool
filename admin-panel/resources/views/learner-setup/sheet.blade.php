@extends('learner-setup.shell')

{{-- Printed by the school and handed to the learner or guardian. The code is shown only here, once. --}}
@section('content')
    <h1 class="m3-headline-small">{{ __('learner_setup_sheet_title') }}</h1>
    <dl class="learner-setup__facts">
        <dt>{{ __('learner') }}</dt>
        <dd>{{ $learner->full_name }}</dd>
        <dt>{{ __('gr_number') }}</dt>
        <dd>{{ $learner->email }}</dd>
        <dt>{{ __('learner_setup_code') }}</dt>
        <dd class="learner-setup__code">{{ $code }}</dd>
        <dt>{{ __('learner_setup_valid_until') }}</dt>
        <dd>{{ $expiresAt->toFormattedDateString() }}</dd>
    </dl>
    <ol class="m3-body-medium">
        <li>{{ __('learner_setup_sheet_step_open', ['url' => $setupUrl]) }}</li>
        <li>{{ __('learner_setup_sheet_step_enter') }}</li>
        <li>{{ __('learner_setup_sheet_step_sign_in') }}</li>
    </ol>
    <p class="m3-body-small m3-text-muted">{{ __('learner_setup_sheet_once') }}</p>
    <button type="button" class="m3-button m3-button--filled learner-setup__print" id="print-sheet">{{ __('print') }}</button>
@endsection

@section('script')
    <script src="{{ asset('/assets/js/custom/learner-setup.js') }}?v={{ filemtime(public_path('assets/js/custom/learner-setup.js')) }}"></script>
@endsection
