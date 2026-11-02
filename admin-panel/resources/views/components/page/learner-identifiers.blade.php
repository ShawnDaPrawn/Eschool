{{-- A learner's numbers, headline first (ADR 0007): the LURITS number, or the
     NavigateEd number labelled temporary when no LURITS number is recorded,
     then the school's own admission number. $identifier is null for a viewer
     who may not see it (LearnerIdentifiers). --}}
@props(['identifier' => null, 'admissionNo' => null])
@if ($identifier !== null || (string) $admissionNo !== '')
    <dl {{ $attributes->merge(['class' => 'learner-identifiers']) }} data-learner-identifiers>
        @if ($identifier !== null)
            <div data-identifier-type="{{ $identifier['type'] }}"><dt>{{ $identifier['type'] === 'lurits' ? __('LURITS number') : __('NavigateEd number (temporary)') }}</dt><dd>{{ $identifier['value'] }}</dd></div>
        @endif
        @if ((string) $admissionNo !== '')
            <div><dt>{{ __('Admission number') }}</dt><dd>{{ $admissionNo }}</dd></div>
        @endif
    </dl>
@endif
