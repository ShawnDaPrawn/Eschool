@extends('learner-setup.shell')

{{-- Printed by the school and handed to the learner or guardian. The code is shown only here, once. --}}
@section('content')
    <h1 class="h4 mb-3">{{ __('learner_setup_sheet_title') }}</h1>
    <dl class="mb-4">
        <dt>{{ __('learner') }}</dt>
        <dd>{{ $learner->full_name }}</dd>
        <dt>{{ __('gr_number') }}</dt>
        <dd>{{ $learner->email }}</dd>
        <dt>{{ __('learner_setup_code') }}</dt>
        <dd class="h3 font-weight-bold" style="letter-spacing: 0.15em;">{{ $code }}</dd>
        <dt>{{ __('learner_setup_valid_until') }}</dt>
        <dd>{{ $expiresAt->toFormattedDateString() }}</dd>
    </dl>
    <ol>
        <li>{{ __('learner_setup_sheet_step_open', ['url' => $setupUrl]) }}</li>
        <li>{{ __('learner_setup_sheet_step_enter') }}</li>
        <li>{{ __('learner_setup_sheet_step_sign_in') }}</li>
    </ol>
    <p class="text-muted small">{{ __('learner_setup_sheet_once') }}</p>
    <button type="button" class="btn btn-theme d-print-none" id="print-sheet">{{ __('print') }}</button>
    <script>
        document.getElementById('print-sheet').addEventListener('click', function () { window.print(); });
    </script>
@endsection
