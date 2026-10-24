@extends('layouts.master')
@section('title', __('Mid-year learner changes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/transition-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content transition-workbench">
    <x-page.header :title="__('Mid-year learner changes')" :subtitle="__('Record a dated join, departure or subject change. Review the effect before saving.')" />
    @include('academics.groups-status')
    @if($conflict)<div role="alert"><h2>{{ __('Review the current learner state') }}</h2><p>{{ $conflict }}</p><p>{{ __('Your submitted draft is below. Preview it again before applying.') }}</p></div>@endif
    <form method="GET" action="{{ route('academic-transitions.index') }}" aria-label="{{ __('Find a learner') }}" class="transition-fields">
        <x-page.field :restore-old-input="false" name="search" :label="__('Learner name')" :value="$search" maxlength="100" />
        <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Find learner') }}</button>
    </form>
    <details class="page-section" @if(!$learner) open @endif><summary>{{ __('Learner search results') }}</summary>
        @forelse($results['learners'] as $row)<p><a href="{{ route('academic-transitions.index', ['learner_id' => $row['id'], 'session_year_id' => $configuration['session_year_id'], 'kind' => $kind]) }}">{{ $row['name'] }} · {{ $row['student_number'] }}</a></p>@empty<p>{{ __('No learners match this name.') }}</p>@endforelse
        <nav aria-label="{{ __('Learner search pages') }}">@if($results['page'] > 1)<a href="{{ route('academic-transitions.index', ['search' => $search, 'page' => $results['page'] - 1]) }}">{{ __('Previous learners') }}</a>@endif @if($results['page'] < $results['last_page'])<a href="{{ route('academic-transitions.index', ['search' => $search, 'page' => $results['page'] + 1]) }}">{{ __('More learners') }}</a>@endif</nav>
    </details>
    @if($learner)
    <section class="page-section"><h2>{{ $learner['name'] }}</h2><p>{{ $learner['student_number'] }}</p>
        <form method="GET" action="{{ route('academic-transitions.index') }}" aria-label="{{ __('Choose learner change') }}" class="transition-fields">
            <input type="hidden" name="learner_id" value="{{ $learner['id'] }}">
            <x-page.field :restore-old-input="false" name="session_year_id" type="select" :label="__('School year')" :options="array_column($configuration['school_years'], 'name', 'id')" :value="$configuration['session_year_id']" required />
            <x-page.field :restore-old-input="false" name="kind" type="select" :label="__('Change type')" :options="['join' => __('Join'), 'leave' => __('Leave'), 'subject_change' => __('Subject change')]" :value="$kind" required />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Open change form') }}</button>
        </form>
        <form method="POST" action="{{ route('academic-transitions.preview') }}" aria-label="{{ __('Preview learner change') }}" class="transition-form">@csrf
            <input type="hidden" name="kind" value="{{ $kind }}"><input type="hidden" name="learner_id" value="{{ $learner['id'] }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
            <x-page.field :restore-old-input="false" name="effective_on" type="date" :label="$kind === 'leave' ? __('Last attended day') : __('First day of this change')" :value="$draft['effective_on'] ?? app(\App\Services\Academics\AcademicAuthority::class)->today()" required />
            @if($kind === 'join')
                <x-page.field :restore-old-input="false" name="class_section_id" type="select" :label="__('New homeroom')" :options="collect($configuration['homerooms'])->whereNotNull('grade_level_id')->pluck('name', 'id')->all()" :value="$draft['class_section_id'] ?? ''" required />
                <fieldset><legend>{{ __('Subjects and timetable groups') }}</legend><p>{{ __('Choose groups for the new homeroom grade. You can finish allocations later.') }}</p><div class="transition-options">@foreach($configuration['groups'] as $group)<label><input type="checkbox" name="group_ids[]" value="{{ $group['id'] }}" @checked(in_array($group['id'], $draft['group_ids'] ?? []))> {{ $group['name'] }} · {{ $group['subject_name'] }}</label>@endforeach</div></fieldset>
                <x-page.field :restore-old-input="false" name="fee_prorata_requested" type="select" :label="__('Request fee pro-rata review')" :options="[0 => __('No'), 1 => __('Yes')]" :value="(int) ($draft['fee_prorata_requested'] ?? false)" :help="__('Records a request for finance. This does not post an invoice or payment.')" required />
            @elseif($kind === 'subject_change')
                @php $membershipChoices = collect($learner['state']['memberships'])->whereNull('cancelled_at')->where('session_year_id', $configuration['session_year_id'])->mapWithKeys(fn ($m) => [$m['id'] => (collect($configuration['groups'])->firstWhere('id', $m['teaching_group_id'])['name'] ?? __('Archived group')).' · '.$m['starts_on'].' – '.$m['ends_on']])->all(); @endphp
                <x-page.field :restore-old-input="false" name="source_membership_id" type="select" :label="__('Current subject membership')" :options="$membershipChoices" :value="$draft['source_membership_id'] ?? ''" required />
                <x-page.field :restore-old-input="false" name="target_group_id" type="select" :label="__('New teaching group')" :options="collect($configuration['groups'])->mapWithKeys(fn ($g) => [$g['id'] => $g['name'].' · '.$g['subject_name']])->all()" :value="$draft['target_group_id'] ?? ''" required />
            @endif
            @if($kind !== 'leave')<x-page.field :restore-old-input="false" name="mark_rule_override" type="select" :label="__('Earlier task mark rule')" :placeholder="__('Use the grade default')" :options="['catch_up' => __('Catch up'), 'excused' => __('Excused')] + ($kind === 'subject_change' ? ['carried' => __('Carry existing marks for the same subject')] : [])" :value="$draft['mark_rule_override'] ?? ''" />@endif
            <x-page.field :restore-old-input="false" name="reason" type="textarea" :label="__('Reason for this change')" :value="$draft['reason'] ?? ''" minlength="3" maxlength="2000" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Preview learner change') }}</button>
        </form>
    </section>
    @if($preview)
        <section class="page-section" aria-label="{{ __('Change review') }}"><h2>{{ __('Review dated change') }}</h2>
            <p>{{ $preview['learner_name'] }} · {{ $preview['homeroom_name'] }} · {{ $preview['input']['effective_on'] }}</p><p>{{ $preview['input']['reason'] }}</p>
            @if($preview['mark_rule'])<p>{{ __('Earlier task mark rule') }}: {{ __(ucfirst(str_replace('_', ' ', $preview['mark_rule']))) }}</p>@endif
            @if($kind === 'leave')<p>{{ __('Enrolments and group memberships end on the last attended day. Later scheduled entries are cancelled and kept in history. Learner access ends the next day; guardian access and educational records remain.') }}</p>@else<p>{{ __('Chosen memberships select the timetable. Existing marks and guardian approval are preserved.') }}</p>@endif
            @foreach($preview['group_assignments'] as $group)<p>{{ $group['name'] }} · {{ $group['subject_name'] }} · {{ $preview['input']['effective_on'] }} – {{ $preview['membership_ends_on'] }}</p>@endforeach
            <form method="POST" action="{{ route('academic-transitions.commit') }}">@csrf
                @include('academics.transitions.hidden', ['values' => $preview['input']])
                <input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}"><input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <button class="m3-button m3-button--filled" type="submit">{{ __('Apply reviewed change') }}</button>
            </form>
        </section>
    @endif
    <section class="page-section"><h2>{{ __('Enrolment history') }}</h2>
        @forelse($learner['state']['enrolments'] as $row)<p>{{ $row['starts_on'] }} – {{ $row['ends_on'] ?? __('Open') }} · {{ __('Homeroom') }} {{ $row['class_section_id'] }} @if($row['cancelled_at']) · {{ __('Cancelled future entry') }} @endif</p>@empty<p>{{ __('No dated enrolments yet.') }}</p>@endforelse
        <h3>{{ __('Recorded changes') }}</h3>@foreach($learner['transitions'] as $change)<p>{{ $change['effective_on'] }} · {{ __(ucfirst(str_replace('_', ' ', $change['kind']))) }} · {{ $change['reason'] }}</p>@endforeach
        <nav aria-label="{{ __('Change history pages') }}">@foreach(['Previous changes' => $learner['page'] - 1, 'More changes' => $learner['page'] + 1] as $label => $page)@if($page >= 1 && $page <= $learner['last_page'])<a href="{{ route('academic-transitions.index', ['learner_id' => $learner['id'], 'kind' => $kind, 'session_year_id' => $configuration['session_year_id'], 'history_page' => $page]) }}">{{ __($label) }}</a>@endif @endforeach</nav>
    </section>
    @if($learner['can_export'])@include('academics.transitions.exports')@endif
    @endif
    <details class="page-section"><summary>{{ __('Grade defaults and export retention') }}</summary>
        <form method="GET" action="{{ route('academic-transitions.index') }}" class="transition-fields"><x-page.field :restore-old-input="false" name="grade_level_id" type="select" :label="__('Grade defaults to edit')" :options="array_column($configuration['grades'], 'name', 'id')" :value="$grade['id'] ?? ''" required /><button class="m3-button m3-button--outlined" type="submit">{{ __('Open grade defaults') }}</button></form>
        @if($grade)<form method="POST" action="{{ route('academic-transitions.policy', $grade['id']) }}" aria-label="{{ __('Save grade defaults') }}" class="transition-form">@csrf<input type="hidden" name="revision" value="{{ $grade['policy']['revision'] }}">
            <x-page.field :restore-old-input="false" name="join_mark_rule" type="select" :label="__('Default for a join')" :options="['catch_up' => __('Catch up'), 'excused' => __('Excused')]" :value="$grade['policy']['join_mark_rule']" required />
            <x-page.field :restore-old-input="false" name="change_mark_rule" type="select" :label="__('Default for a subject change')" :options="['catch_up' => __('Catch up'), 'excused' => __('Excused'), 'carried' => __('Carry same-subject marks')]" :value="$grade['policy']['change_mark_rule']" required /><button class="m3-button m3-button--filled" type="submit">{{ __('Save grade defaults') }}</button></form>@endif
        <form method="POST" action="{{ route('academic-transitions.retention') }}" aria-label="{{ __('Save export retention') }}" class="transition-form">@csrf<input type="hidden" name="revision" value="{{ $configuration['retention']['revision'] }}"><x-page.field :restore-old-input="false" name="days" type="number" :label="__('Keep export copies for this many days')" :value="$configuration['retention']['days']" min="1" max="30" required :help="__('Applies to new copies. Educational records remain until an approved retention schedule exists. Downloaded copies remain with their recipient.')" /><button class="m3-button m3-button--filled" type="submit">{{ __('Save export retention') }}</button></form>
    </details>
</div>
@endsection
