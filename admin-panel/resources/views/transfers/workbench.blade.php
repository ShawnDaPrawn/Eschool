@extends('layouts.master')
@section('title', __('School transfers'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/school-transfers.css') }}">@endsection
@section('content')
@php
    $lanes = ['outgoing' => __('Sending school'), 'incoming' => __('Receiving school'), 'receipts' => __('Received history')];
    $choiceLabels = ['learners' => __('Learner'), 'guardians' => __('Consenting guardian'), 'destinations' => __('Receiving school'), 'reports' => __('Released reports'), 'documents' => __('Shared documents'), 'years' => __('School year'), 'rooms' => __('Homeroom'), 'categories' => __('Learner category')];
    $options = fn(string $kind) => array_column($choices[$kind]['items'] ?? [], 'label', 'key');
    $navParams = ['lane' => $lane, 'record' => $record['id'] ?? null, 'learner_id' => $learner, 'guardian_id' => $guardian, 'choice_kind' => $choiceKind, 'search' => $search];
@endphp
<div class="content-wrapper m3 page-content transfer-workbench">
    <x-page.header :title="__('School transfers')" :subtitle="__('A named guardian reviews the records and destination before either school completes a transfer.')" />
    @include('academics.groups-status')
    @if($messages)<div role="alert">@foreach($messages as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    <nav aria-label="{{ __('Transfer workbenches') }}">@foreach($lanes as $key => $label)<a class="m3-button m3-button--outlined" @if($key === $lane) aria-current="page" @endif href="{{ route('school-transfers.index', ['lane' => $key]) }}">{{ $label }}</a>@endforeach</nav>
    <p>{{ __('Transferred marks remain historical source records. The receiving school handles learner approval separately.') }}</p>
    @if(!$canWrite)<p role="status">{{ __('Changes are unavailable. Check the current school year and module access.') }}</p>@endif

    <section class="page-section" aria-labelledby="transfer-list"><h2 id="transfer-list">{{ $lanes[$lane] }}</h2>
        @forelse($listing['items'] as $item)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3><a href="{{ route('school-transfers.index', ['lane' => $lane, 'record' => $item['id']]) }}">{{ $item['learner_name'] }}</a></h3><p>{{ $item['from_school_name'] }} → {{ $item['to_school_name'] }}</p><p>{{ __('Transfer state: '.$item['state']) }} · {{ __('Starts on') }} {{ $item['starts_on'] }}</p></div></article>
        @empty<p>{{ __('No transfers in this view.') }}</p>@endforelse
        <nav aria-label="{{ __('Transfer pages') }}">@if($page > 1)<a href="{{ route('school-transfers.index', ['lane' => $lane, 'page' => $page - 1]) }}">{{ __('Previous') }}</a>@endif @if($listing['has_more'])<a href="{{ route('school-transfers.index', ['lane' => $lane, 'page' => $page + 1]) }}">{{ __('Next') }}</a>@endif</nav>
    </section>

    @if($record)
    <section class="page-section" aria-labelledby="transfer-current"><h2 id="transfer-current">{{ $record['learner_name'] }} · {{ __('Transfer package') }}</h2>
        <dl class="transfer-facts"><div><dt>{{ __('Student number') }}</dt><dd>{{ $record['student_number'] ?? __('Not available') }}</dd></div><div><dt>{{ __('Sending school') }}</dt><dd>{{ $record['from_school_name'] }}</dd></div><div><dt>{{ __('Receiving school') }}</dt><dd>{{ $record['to_school_name'] }}</dd></div><div><dt>{{ __('Consenting guardian') }}</dt><dd>{{ $record['guardian_name'] }}</dd></div><div><dt>{{ __('Last attended date') }}</dt><dd>{{ $record['last_attended_on'] }}</dd></div><div><dt>{{ __('Starts on') }}</dt><dd>{{ $record['starts_on'] }}</dd></div></dl>
        <p>{{ __('Transfer state: '.$record['state']) }} · {{ __('Revision') }} {{ $record['revision'] }}</p>
        @if($record['export_only'])<p>{{ __('Historical export only. This copy does not close a current enrolment.') }}</p>@endif
        @if($record['expires_at'])<p>{{ __('Handoff copy expires') }}: {{ $record['expires_at'] }}</p>@endif
        <p>{{ __('Package fingerprint') }} <code class="transfer-fingerprint">{{ $record['fingerprint'] }}</code></p>
        <h3>{{ __('Released reports') }}</h3><ul>@forelse($record['reports'] as $report)<li>{{ $report['year_name'] }} · {{ $report['term_name'] }}</li>@empty<li>{{ __('No selected reports.') }}</li>@endforelse</ul>
        <h3>{{ __('Shared documents') }}</h3><ul>@forelse($record['documents'] as $document)<li>{{ $document['name'] }}</li>@empty<li>{{ __('No selected documents.') }}</li>@endforelse</ul>
        <h3>{{ __('Historical released marks') }}</h3><div class="transfer-table"><table><caption>{{ __('Released source marks') }}</caption><thead><tr><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Percentage') }}</th><th scope="col">{{ __('Released') }}</th></tr></thead><tbody>@forelse($record['released_marks'] as $mark)<tr><th scope="row">{{ $mark['subject_name'] }}</th><td>{{ $mark['percentage_bp'] === null ? __('Not available') : number_format($mark['percentage_bp'] / 100, 2).'%' }}</td><td>{{ substr($mark['released_at'], 0, 10) }}</td></tr>@empty<tr><td colspan="3">{{ __('No released marks.') }}</td></tr>@endforelse</tbody></table></div>
        <h3>{{ __('Dated attendance') }}</h3><div class="transfer-table"><table><caption>{{ __('Source attendance history') }}</caption><thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Register') }}</th><th scope="col">{{ __('Attendance') }}</th></tr></thead><tbody>@forelse($record['attendance'] as $entry)<tr><th scope="row">{{ $entry['on_date'] }}</th><td>{{ $entry['kind'] === 'homeroom' ? __('Homeroom') : __('Lesson') }}</td><td>{{ __('Attendance state: '.$entry['state']) }}</td></tr>@empty<tr><td colspan="3">{{ __('No dated attendance.') }}</td></tr>@endforelse</tbody></table></div>
        @if($record['can_download'])<a class="m3-button m3-button--outlined" href="{{ route('school-transfers.archive', ['lane' => $lane, 'record' => $record['id']]) }}">{{ __('Download signed transfer archive') }}</a>@endif
        @if($canWrite && $record['can_release'])<form method="POST" action="{{ route('school-transfers.release', ['package' => $record['id']]) }}" aria-label="{{ __('Release consented transfer') }}">@include('transfers.hidden', ['revision' => $record['revision']])<button type="submit" class="m3-button m3-button--filled">{{ __('Release consented transfer') }}</button></form>@endif
        @if($canWrite && $record['can_cancel'])<form method="POST" action="{{ route('school-transfers.cancel', ['package' => $record['id']]) }}" aria-label="{{ __('Cancel transfer') }}">@include('transfers.hidden', ['revision' => $record['revision']])<button type="submit" class="m3-button m3-button--outlined">{{ __('Cancel transfer') }}</button></form>@endif
    </section>
    @endif

    @if($canWrite && $lane !== 'receipts')
    <section class="page-section" aria-labelledby="transfer-choices"><h2 id="transfer-choices">{{ __('Find transfer choices') }}</h2>
        <form method="GET" action="{{ route('school-transfers.index') }}" class="transfer-fields" aria-label="{{ __('Find transfer choices') }}">
            <input type="hidden" name="lane" value="{{ $lane }}">@if($record)<input type="hidden" name="record" value="{{ $record['id'] }}">@endif
            @if($lane === 'outgoing')
            <x-page.field id="transfer-select-learner" name="learner_id" type="select" :label="__('Learner')" :options="$options('learners')" :value="$learner" :restore-old-input="false" />
            @if($learner)<x-page.field id="transfer-select-guardian" name="guardian_id" type="select" :label="__('Consenting guardian')" :options="$options('guardians')" :value="$guardian" :restore-old-input="false" />@endif
            @endif
            <x-page.field id="transfer-choice-kind" name="choice_kind" type="select" :label="__('Choices to search')" :options="array_intersect_key($choiceLabels, $choices)" :value="$choiceKind" :restore-old-input="false" />
            <x-page.field id="transfer-search" name="search" :label="__('Find by name')" :value="$search" :restore-old-input="false" maxlength="100" />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Load transfer choices') }}</button>
        </form>
        <nav aria-label="{{ __('Choice pages') }}">@if($choicePage > 1)<a href="{{ route('school-transfers.index', $navParams + ['choice_page' => $choicePage - 1]) }}">{{ __('Previous choices') }}</a>@endif @if($choices[$choiceKind]['has_more'] ?? false)<a href="{{ route('school-transfers.index', $navParams + ['choice_page' => $choicePage + 1]) }}">{{ __('More choices') }}</a>@endif</nav>
    </section>
    @endif

    @if($canWrite && $lane === 'outgoing' && $learner && $guardian)
    <section class="page-section" aria-labelledby="transfer-prepare"><h2 id="transfer-prepare">{{ __('Prepare a transfer for guardian review') }}</h2>
        <p>{{ __('Only reports, released marks, attendance and the selected shared documents are included.') }}</p>
        <form method="POST" action="{{ route('school-transfers.prepare') }}" class="transfer-fields" aria-label="{{ __('Prepare a transfer for guardian review') }}">
            @include('transfers.hidden')
            <input type="hidden" name="learner_id" value="{{ $learner }}"><input type="hidden" name="guardian_id" value="{{ $guardian }}">
            <x-page.field id="transfer-destination" name="to_school_id" type="select" :label="__('NavigateEd receiving school')" :placeholder="__('External receiving school')" :options="$options('destinations')" :value="$draft['to_school_id'] ?? ''" :restore-old-input="false" :help="__('Leave blank for an external school.')" />
            <x-page.field id="transfer-external" name="external_school" :label="__('External receiving school')" :value="$draft['external_school'] ?? ''" :restore-old-input="false" maxlength="160" />
            <x-page.field id="transfer-last" name="last_attended_on" type="date" :label="__('Last attended date')" :value="$draft['last_attended_on'] ?? $access['today']" :restore-old-input="false" required />
            <x-page.field id="transfer-start" name="starts_on" type="date" :label="__('Receiving start date')" :value="$draft['starts_on'] ?? ''" :restore-old-input="false" required />
            <fieldset><legend>{{ __('Released reports to include') }}</legend>@foreach($choices['reports']['items'] as $item)<label for="transfer-report-{{ $item['key'] }}"><input id="transfer-report-{{ $item['key'] }}" type="checkbox" name="report_ids[]" value="{{ $item['key'] }}"> {{ $item['label'] }}</label>@endforeach</fieldset>
            <fieldset><legend>{{ __('Shared documents to include') }}</legend>@foreach($choices['documents']['items'] as $item)<label for="transfer-document-{{ $item['key'] }}"><input id="transfer-document-{{ $item['key'] }}" type="checkbox" name="document_ids[]" value="{{ $item['key'] }}"> {{ $item['label'] }}</label>@endforeach</fieldset>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Prepare for guardian review') }}</button>
        </form>
    </section>
    @endif

    @if($canWrite && $lane === 'incoming' && $record && $record['can_accept'])
    <section class="page-section" aria-labelledby="transfer-accept"><h2 id="transfer-accept">{{ __('Accept this transfer') }}</h2>
        <form method="POST" action="{{ route('school-transfers.accept', ['package' => $record['id']]) }}" class="transfer-fields" aria-label="{{ __('Accept this transfer') }}">
            @include('transfers.hidden', ['revision' => $record['revision']])<input type="hidden" name="fingerprint" value="{{ $record['fingerprint'] }}">
            <x-page.field id="transfer-year" name="session_year_id" type="select" :label="__('Receiving school year')" :options="$options('years')" :value="$draft['session_year_id'] ?? ''" :restore-old-input="false" required />
            <x-page.field id="transfer-room" name="class_section_id" type="select" :label="__('Receiving homeroom')" :options="$options('rooms')" :value="$draft['class_section_id'] ?? ''" :restore-old-input="false" required />
            <x-page.field id="transfer-category" name="category_id" type="select" :label="__('Learner category')" :options="$options('categories')" :value="$draft['category_id'] ?? ''" :restore-old-input="false" required />
            <label for="transfer-confirm"><input id="transfer-confirm" name="confirmation" value="1" type="checkbox" required> {{ __('I reviewed these records and will open the receiving enrolment on the consented start date. Learner approval remains separate.') }}</label>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Accept and place learner') }}</button>
        </form>
    </section>
    @endif
</div>
@endsection
