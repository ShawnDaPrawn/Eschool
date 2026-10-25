@extends('layouts.master')
@section('title', __('Support plans'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/support-plans.css') }}">@endsection
@section('content')
@php
    $draftValue = fn(string $key, string $field, $default = '') => $draftKey === $key ? ($draft[$field] ?? $default) : $default;
    $modeLabels = ['professional' => __('My support cases'), 'principal' => __('Support assignments'), 'teacher' => __('My approved instructions')];
@endphp
<div class="content-wrapper m3 page-content support-workbench">
    <x-page.header :title="__('Support plans')" :subtitle="__('Restricted SIAS plans and explicitly approved classroom instructions.')" />
    @include('academics.groups-status')
    <p>{{ __('Private support records are limited to assigned professionals. Teacher instructions contain only approved accommodations.') }}</p>
    @if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach
</div>@endif

    @if(!$canWrite)<p role="status">{{ __('Changes are unavailable. Check the current school year and module access.') }}</p>@endif

    <nav aria-label="{{ __('Support workbenches') }}">@foreach($access['audiences'] as $mode)<a class="m3-button m3-button--outlined" @if($mode === $audience) aria-current="page" @endif
 href="{{ route('support-plans.index', ['audience' => $mode]) }}">{{ $modeLabels[$mode] }}</a>@endforeach
</nav>
    @if($audience !== 'teacher')
    <form method="GET" class="support-fields" action="{{ route('support-plans.index') }}" aria-label="{{ __('Find named support choices') }}">
        <input type="hidden" name="audience" value="{{ $audience }}">
        <x-page.field id="support-search" name="search" :label="__('Find learner or staff member')" :value="request('search', '')" :restore-old-input="false" maxlength="100" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button>
    </form>
    @endif

    @if($audience === 'teacher')
        <section class="page-section" aria-labelledby="support-instructions"><h2 id="support-instructions">{{ __('My approved instructions') }}</h2>
        @forelse($listing['items'] as $instruction)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $instruction['learner_name'] }}</h3><p>{{ __('Expires on') }} {{ $instruction['expires_on'] }}</p><p class="support-private-text">{{ $instruction['accommodations'] }}</p></div></article>

@empty
<p>{{ __('No current approved instructions.') }}</p>@endforelse
</section>
    @elseif($audience === 'principal')
        <p>{{ __('Appointments grant only the named professional a dated support caseload. Assignment management does not open plan contents.') }}</p>
        @if($canWrite)
        <section class="page-section" aria-labelledby="support-appoint"><h2 id="support-appoint">{{ __('Appoint a support professional') }}</h2>
        <form method="POST" action="{{ route('support-plans.assign') }}" class="support-fields" aria-label="{{ __('Appoint a support professional') }}">
            @include('pastoral.support-hidden', ['formKey' => 'assign:new'])
            <x-page.field id="support-assign-learner" name="learner_id" type="select" :label="__('Learner')" :options="array_column($configuration['learners'], 'name', 'id')" :value="$draftValue('assign:new','learner_id')" :restore-old-input="false" required />
            <x-page.field id="support-assign-staff" name="user_id" type="select" :label="__('Support professional')" :options="array_column($configuration['staff'], 'name', 'id')" :value="$draftValue('assign:new','user_id')" :restore-old-input="false" required />
            <x-page.field id="support-assign-start" name="starts_on" type="date" :label="__('Starts on')" :value="$draftValue('assign:new','starts_on',$today)" :restore-old-input="false" required />
            <x-page.field id="support-assign-end" name="ends_on" type="date" :label="__('Ends on')" :value="$draftValue('assign:new','ends_on',$today)" :restore-old-input="false" required />
            <x-page.field id="support-assign-reason" name="reason" type="textarea" :label="__('Private appointment rationale')" :value="$draftValue('assign:new','reason')" :restore-old-input="false" maxlength="2000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Appoint professional') }}</button>
        </form></section>
        @endif

        <section class="page-section" aria-labelledby="support-appointments"><h2 id="support-appointments">{{ __('Support assignments') }}</h2>
        @forelse($listing['items'] as $appointment)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $appointment['learner_name'] }}</h3><p>{{ $appointment['professional_name'] }} · {{ $appointment['starts_on'] }} – {{ $appointment['ends_on'] }} · {{ $appointment['active'] ? __('Active') : __('Withdrawn') }}</p>
            @if($canWrite && $appointment['active'])
            @php($formKey = 'revoke-assignment:'.$appointment['id'])
            <form method="POST" action="{{ route('support-plans.revoke-assignment', ['assignment' => $appointment['id']]) }}" class="support-fields" aria-label="{{ __('Withdraw support appointment') }} · {{ $appointment['learner_name'] }}">
                @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $appointment['revision']])
                <x-page.field :id="'support-revoke-appointment-'.$appointment['id']" name="reason" type="textarea" :label="__('Reason for this change')" :value="$draftValue($formKey,'reason')" :restore-old-input="false" maxlength="2000" required />
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Withdraw appointment') }}</button>
            </form>@endif

        </div></article>
@empty
<p>{{ __('No support appointments.') }}</p>@endforelse
</section>
    @else
        <section class="page-section" aria-labelledby="support-cases"><h2 id="support-cases">{{ __('My support cases') }}</h2>
        @forelse($listing['items'] as $case)<p><a href="{{ route('support-plans.index', ['audience' => 'professional', 'plan' => $case['id']]) }}">{{ $case['learner_name'] }}</a> · {{ __($case['state']) }} @if($case['review_due_on']) · {{ __('Review due') }} {{ $case['review_due_on'] }} @endif
</p>
@empty
<p>{{ __('No support plans in your current caseload.') }}</p>@endforelse

        <a class="m3-button m3-button--outlined" href="{{ route('support-plans.index', ['audience' => 'professional']) }}#support-plan-form">{{ __('Start a support plan') }}</a></section>
        @if($record)
        <section class="page-section" aria-labelledby="support-current"><h2 id="support-current">{{ $record['learner_name'] }} · {{ __('Current saved plan') }}</h2><p>{{ __('Revision') }} {{ $record['revision'] }} · {{ __($record['state']) }} · {{ __('SIAS stage') }}: {{ $record['sias_stage'] }}</p>
            <h3>{{ __('Support needs') }}</h3><p class="support-private-text">{{ $record['needs'] }}</p><h3>{{ __('Accommodations') }}</h3><p class="support-private-text">{{ $record['accommodations'] }}</p>
        </section>
        @endif

        @if($canWrite && (!$record || $record['state'] === 'active'))
        @php($action = $record ? 'save' : 'create')
        @php($formKey = $action.':'.($record['id'] ?? 'new'))
        <section class="page-section" aria-labelledby="support-plan-form"><h2 id="support-plan-form">{{ $record ? __('Edit support plan') : __('Start a support plan') }}</h2>
        <form method="POST" action="{{ route('support-plans.'.$action, $record ? ['plan' => $record['id']] : []) }}" class="support-fields" aria-label="{{ $record ? __('Edit support plan') : __('Start a support plan') }}">
            @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $record['revision'] ?? null])
            @if($record)<input type="hidden" name="learner_id" value="{{ $record['learner_id'] }}"><p>{{ $record['learner_name'] }}</p>
            @else<x-page.field id="support-plan-learner" name="learner_id" type="select" :label="__('Learner')" :options="array_column($configuration['learners'],'name','id')" :value="$draftValue($formKey,'learner_id')" :restore-old-input="false" required />@endif

            <x-page.field id="support-plan-start" name="started_on" type="date" :label="__('Plan start date')" :value="$draftValue($formKey,'started_on',$record['started_on'] ?? $today)" :restore-old-input="false" required />
            <x-page.field id="support-plan-stage" name="sias_stage" :label="__('SIAS stage')" :value="$draftValue($formKey,'sias_stage',$record['sias_stage'] ?? '')" :restore-old-input="false" maxlength="100" required />
            <x-page.field id="support-plan-needs" name="needs" type="textarea" :label="__('Support needs')" :value="$draftValue($formKey,'needs',$record['needs'] ?? '')" :restore-old-input="false" maxlength="20000" required />
            <x-page.field id="support-plan-accommodations" name="accommodations" type="textarea" :label="__('Accommodations')" :value="$draftValue($formKey,'accommodations',$record['accommodations'] ?? '')" :restore-old-input="false" maxlength="20000" required />
            <x-page.field id="support-plan-due" name="review_due_on" type="date" :label="__('Review due')" :value="$draftValue($formKey,'review_due_on',$record['review_due_on'] ?? '')" :restore-old-input="false" />
            <p>{{ __('Use the school’s recorded SIAS stage. Changing accommodations withdraws previously approved teacher instructions.') }}</p>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save support plan') }}</button>
        </form></section>
        @endif

        @if($canWrite && $record && $record['state'] === 'active')
        @php($formKey = 'review:'.$record['id'])
        <section class="page-section" aria-labelledby="support-review"><h2 id="support-review">{{ __('Add a support review') }}</h2>
        <form method="POST" action="{{ route('support-plans.review', ['plan' => $record['id']]) }}" class="support-fields" aria-label="{{ __('Add a support review') }}">
            @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $record['revision']])
            <x-page.field id="support-review-date" name="reviewed_on" type="date" :label="__('Reviewed on')" :value="$draftValue($formKey,'reviewed_on',$today)" :restore-old-input="false" required />
            <x-page.field id="support-review-next" name="next_review_on" type="date" :label="__('Next review date')" :value="$draftValue($formKey,'next_review_on')" :restore-old-input="false" />
            <x-page.field id="support-review-outcome" name="outcome" type="textarea" :label="__('Review outcome')" :value="$draftValue($formKey,'outcome')" :restore-old-input="false" maxlength="10000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Add review') }}</button>
        </form></section>
        @php($formKey = 'disclose:'.$record['id'])
        <section class="page-section" aria-labelledby="support-disclose"><h2 id="support-disclose">{{ __('Approve teacher instructions') }}</h2>
        <form method="POST" action="{{ route('support-plans.disclose', ['plan' => $record['id']]) }}" class="support-fields" aria-label="{{ __('Approve teacher instructions') }}">
            @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $record['revision']])
            <x-page.field id="support-disclose-teacher" name="user_id" type="select" :label="__('Teacher')" :options="array_column($configuration['staff'],'name','id')" :value="$draftValue($formKey,'user_id')" :restore-old-input="false" required />
            <x-page.field id="support-disclose-expiry" name="expires_on" type="date" :label="__('Expires on')" :value="$draftValue($formKey,'expires_on',$today)" :restore-old-input="false" required />
            <x-page.field id="support-disclose-text" name="accommodations" type="textarea" :label="__('Approved classroom instructions')" :value="$draftValue($formKey,'accommodations')" :restore-old-input="false" maxlength="10000" required />
            <x-page.field id="support-disclose-reason" name="reason" type="textarea" :label="__('Reason for this disclosure')" :value="$draftValue($formKey,'reason')" :restore-old-input="false" maxlength="2000" required />
            <label for="support-disclose-confirm"><input id="support-disclose-confirm" type="checkbox" name="confirmation" value="1" required> {{ __('I approve only these instructions for this named teacher until the expiry date.') }}</label>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Approve instructions') }}</button>
        </form></section>
        @php($formKey = 'close:'.$record['id'])
        <section class="page-section" aria-labelledby="support-close"><h2 id="support-close">{{ __('Close support plan') }}</h2>
        <form method="POST" action="{{ route('support-plans.close', ['plan' => $record['id']]) }}" class="support-fields" aria-label="{{ __('Close support plan') }}">
            @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $record['revision']])
            <x-page.field id="support-close-reason" name="reason" type="textarea" :label="__('Closure rationale')" :value="$draftValue($formKey,'reason')" :restore-old-input="false" maxlength="2000" required />
            <p>{{ __('Closing withdraws teacher instructions and retains the support history.') }}</p>
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Close plan') }}</button>
        </form></section>
        @endif

        @if($record)
        <section class="page-section" aria-labelledby="support-history"><h2 id="support-history">{{ __('Support history') }}</h2>
            <nav aria-label="{{ __('Choose support history') }}">@foreach(['reviews' => __('Reviews'), 'versions' => __('Plan versions'), 'disclosures' => __('Teacher disclosures')] as $kind => $label)<a class="m3-button m3-button--outlined" @if($kind === $historyKind) aria-current="page" @endif
 href="{{ route('support-plans.index', ['audience' => 'professional','plan' => $record['id'],'history' => $kind]) }}#support-history">{{ $label }}</a>@endforeach
</nav>
            @forelse($history['items'] as $entry)<article class="m3-card m3-card--outlined"><div class="m3-card__content">
                @if($historyKind === 'reviews')<h3>{{ $entry['reviewed_on'] }} · {{ $entry['author_name'] }}</h3><p>{{ __($entry['kind']) }}</p><p class="support-private-text">{{ $entry['outcome'] }}</p>@if($entry['next_review_on'])<p>{{ __('Next review date') }} {{ $entry['next_review_on'] }}</p>@endif

                @elseif($historyKind === 'versions')<h3>{{ __('Revision') }} {{ $entry['revision'] }} · {{ $entry['recorded_at'] }}</h3><p>{{ __('SIAS stage') }} {{ $entry['snapshot']['contents']['sias_stage'] }}</p><h4>{{ __('Support needs') }}</h4><p class="support-private-text">{{ $entry['snapshot']['contents']['needs'] }}</p><h4>{{ __('Accommodations') }}</h4><p class="support-private-text">{{ $entry['snapshot']['contents']['accommodations'] }}</p>
                @else<h3>{{ $entry['teacher_name'] }} · {{ $entry['expires_on'] }} · {{ $entry['active'] ? __('Active') : __('Withdrawn') }}</h3><p class="support-private-text">{{ $entry['accommodations'] }}</p>
                    @if($canWrite && $entry['active'])
                    @php($formKey = 'revoke-disclosure:'.$entry['id'])
                    <form method="POST" action="{{ route('support-plans.revoke-disclosure', ['disclosure' => $entry['id']]) }}" class="support-fields" aria-label="{{ __('Withdraw teacher instructions') }} · {{ $entry['teacher_name'] }}">
                        @include('pastoral.support-hidden', ['formKey' => $formKey, 'revision' => $entry['revision']])
                        <x-page.field :id="'support-revoke-disclosure-'.$entry['id']" name="reason" type="textarea" :label="__('Reason for this change')" :value="$draftValue($formKey,'reason')" :restore-old-input="false" maxlength="2000" required />
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Withdraw instructions') }}</button>
                    </form>@endif

                @endif

            </div></article>
@empty
<p>{{ __('No support history entries.') }}</p>@endforelse

            @foreach(['Previous page' => -1, 'Next page' => 1] as $label => $offset)@if($history['page']+$offset >= 1 && $history['page']+$offset <= $history['last_page'])<a class="m3-button m3-button--outlined" href="{{ route('support-plans.index', ['audience' => 'professional','plan' => $record['id'],'history' => $historyKind,'history_page' => $history['page']+$offset]) }}#support-history">{{ __($label) }}</a>@endif
@endforeach

        </section>@endif

    @endif

    <nav aria-label="{{ __('Support page navigation') }}">@foreach(['Previous page' => -1,'Next page' => 1] as $label => $offset)@if($page+$offset >= 1 && $page+$offset <= max($listing['last_page'],$configuration['last_page'] ?? 1,$configuration['staff_last_page'] ?? 1))<a class="m3-button m3-button--outlined" href="{{ route('support-plans.index', ['audience' => $audience,'page' => $page+$offset,'search' => request('search','')]) }}">{{ __($label) }}</a>@endif
@endforeach
</nav>
</div>
@endsection
