@extends('layouts.master')
@section('title', __('Academic report cards'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/report-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content report-workbench">
    <x-page.header :title="__('Academic report cards')" :subtitle="__('Review evidence, collect comments, sign reports and release private PDFs to families. Fees never restrict reports.')" />
    @include('academics.groups-status')
    @if($conflict)<div role="alert"><h2>{{ __('Review changes before continuing') }}</h2><p>{{ $conflict }}</p><p>{{ __('The latest saved evidence is shown. Nothing was overwritten.') }}</p></div>@endif
    @if($submittedComment)
        <section class="page-section"><h2>{{ __('Your unsaved comment') }}</h2><p class="report-comment-text">{{ $submittedComment['text'] }}</p>
            <form method="POST" action="{{ route('academic-reports.review-comment', $submittedComment['card_id']) }}">@csrf
                @foreach(['subject_id', 'text', 'revision', 'batch_revision', 'reason', 'session_year_id', 'batch_id', 'comment_page'] as $key)@if(array_key_exists($key, $submittedComment))<input type="hidden" name="{{ $key }}" value="{{ $submittedComment[$key] }}">@endif @endforeach
                <button type="submit" class="m3-button m3-button--outlined">{{ __('Prepare reviewed comment draft') }}</button>
            </form>
        </section>
    @endif
    <form method="GET" action="{{ route('academic-reports.index') }}" class="report-fields" aria-label="{{ __('Choose report period') }}">
        <x-page.field :restore-old-input="false" name="session_year_id" type="select" :label="__('School year')" :options="array_column($configuration['school_years'], 'name', 'id')" :value="$configuration['session_year_id']" required />
        @if(count($configuration['homerooms']))<x-page.field :restore-old-input="false" name="class_section_id" type="select" :label="__('Homeroom')" :options="array_column($configuration['homerooms'], 'name', 'id')" :value="$room" required />
            @php $gradeOrder = collect($configuration['homerooms'])->firstWhere('id', $room)['grade_order'] ?? null; $terms = collect($configuration['terms'])->filter(fn ($t) => $gradeOrder !== null && $t['grade_from'] <= $gradeOrder && $t['grade_to'] >= $gradeOrder); @endphp
            <x-page.field :restore-old-input="false" name="term_id" type="select" :label="__('Reporting period')" :options="[0 => __('Annual report')] + $terms->mapWithKeys(fn ($t) => [$t['id'] => $t['name']])->all()" :value="$term ?? 0" />
        @endif
        <button type="submit" class="m3-button m3-button--outlined">{{ __('View reports') }}</button>
    </form>
    @if($configuration['can_design'])
        <details class="page-section"><summary>{{ __('Report designs and comment banks') }}</summary>
            <form method="GET" action="{{ route('academic-reports.index') }}" class="report-fields" aria-label="{{ __('Choose report design') }}">
                <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                <x-page.field :restore-old-input="false" name="template_id" type="select" :label="__('Design to edit')" :options="array_column($configuration['templates'], 'name', 'id')" :placeholder="__('New design')" :value="$template['id'] ?? ''" />
                <button type="submit" class="m3-button m3-button--outlined">{{ __('Open design') }}</button>
            </form>
            <form method="POST" action="{{ route('academic-reports.templates') }}" aria-label="{{ __('Save report design') }}" class="report-form">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                @if($template)<input type="hidden" name="id" value="{{ $template['id'] }}"><input type="hidden" name="revision" value="{{ $template['revision'] }}">@endif
                <x-page.field :restore-old-input="false" name="name" :id="'report-design-name'" :label="__('Design name')" :value="$template['name'] ?? ''" required maxlength="191" />
                <x-page.field :restore-old-input="false" name="design[heading]" :label="__('Report heading')" :value="$template['design']['heading'] ?? __('School report')" required maxlength="191" />
                <x-page.field :restore-old-input="false" name="design[introduction]" type="textarea" :label="__('Introduction')" :value="$template['design']['introduction'] ?? ''" maxlength="3000" />
                <x-page.field :restore-old-input="false" name="design[footer]" type="textarea" :label="__('Footer')" :value="$template['design']['footer'] ?? ''" maxlength="3000" />
                <x-page.field :restore-old-input="false" name="design[show_crest]" type="select" :label="__('Include school crest')" :options="[1 => __('Yes'), 0 => __('No')]" :value="(int) ($template['design']['show_crest'] ?? true)" required />
                <fieldset><legend>{{ __('Printed section order') }}</legend><p>{{ __('Choose each section once. Subject results are required.') }}</p>
                    @php $sectionNames = ['subjects' => __('Subject results'), 'attendance' => __('Attendance'), 'subject_comments' => __('Subject comments'), 'homeroom_comment' => __('Homeroom comment')]; $sections = $template['design']['sections'] ?? array_keys($sectionNames); @endphp
                    @foreach(range(0, 3) as $index)<x-page.field :restore-old-input="false" :name="'design[sections]['.$index.']'" type="select" :label="__('Section').' '.($index + 1)" :options="$sectionNames" :placeholder="__('Omit section')" :value="$sections[$index] ?? ''" />@endforeach
                </fieldset>
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save design') }}</button>
            </form>
            <form method="GET" action="{{ route('academic-reports.index') }}" class="report-fields" aria-label="{{ __('Choose comment bank') }}">
                <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><x-page.field :restore-old-input="false" name="bank_id" type="select" :label="__('Bank entry to edit')" :options="array_column($configuration['comment_banks'], 'label', 'id')" :placeholder="__('New bank entry')" :value="$bank['id'] ?? ''" /><button type="submit" class="m3-button m3-button--outlined">{{ __('Open bank entry') }}</button>
            </form>
            <form method="POST" action="{{ route('academic-reports.banks') }}" aria-label="{{ __('Save comment bank') }}" class="report-form">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                @if($bank)<input type="hidden" name="id" value="{{ $bank['id'] }}"><input type="hidden" name="revision" value="{{ $bank['revision'] }}">@endif
                <p>{{ __('Keep reusable comments generic. Do not put learner details in a bank.') }}</p>
                <x-page.field :restore-old-input="false" name="scope" type="select" :label="__('Comment scope')" :options="['homeroom' => __('Homeroom'), 'subject' => __('Subject')]" :value="$bank['scope'] ?? 'homeroom'" required />
                <x-page.field :restore-old-input="false" name="subject_id" type="select" :id="'report-bank-subject'" :label="__('Subject for subject comments')" :options="array_column($configuration['subjects'], 'name', 'id')" :placeholder="__('None for homeroom')" :value="$bank['subject_id'] ?? ''" />
                <x-page.field :restore-old-input="false" name="label" :label="__('Bank label')" :value="$bank['label'] ?? ''" required maxlength="191" /><x-page.field :restore-old-input="false" name="text" type="textarea" :id="'report-bank-text'" :label="__('Reusable comment')" :value="$bank['text'] ?? ''" required maxlength="3000" />
                <x-page.field :restore-old-input="false" name="active" type="select" :label="__('Available for comments')" :options="[1 => __('Yes'), 0 => __('No')]" :value="(int) ($bank['active'] ?? true)" required />
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save bank entry') }}</button>
            </form>
        </details>
    @endif
    @if($roster && !$batch)
        <section class="page-section"><h2>{{ __('Prepare report cards') }}</h2>
            <form method="POST" action="{{ route('academic-reports.preview') }}" aria-label="{{ __('Preview selected reports') }}" class="report-form">@csrf
                <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><input type="hidden" name="class_section_id" value="{{ $room }}"><input type="hidden" name="term_id" value="{{ $term }}">
                <x-page.field :restore-old-input="false" name="template_id" type="select" :id="'prepare-report-design'" :label="__('Report design')" :options="array_column($configuration['templates'], 'name', 'id')" :value="old('template_id', $preview['selection']['template_id'] ?? '')" required />
                <x-page.field :restore-old-input="false" name="mode" type="select" :label="__('Report type')" :options="['official' => __('Official completed results'), 'running' => __('Provisional running report')]" :value="old('mode', $preview['selection']['mode'] ?? 'official')" required />
                <fieldset><legend>{{ __('Select learners') }}</legend><p>{{ __('An incomplete learner can wait while the selected learners progress.') }}</p><div class="report-learners">
                @foreach($roster['learners'] as $learner)<label><input type="checkbox" name="learner_ids[]" value="{{ $learner['id'] }}" @checked(in_array($learner['id'], $preview['selection']['learner_ids'] ?? old('learner_ids', [])))> {{ $learner['name'] }}</label>@endforeach
                </div></fieldset><button type="submit" class="m3-button m3-button--filled" @disabled(!count($configuration['templates']) || !count($roster['learners']))>{{ __('Preview selected reports') }}</button>
            </form>
        </section>
    @endif
    @if($preview)
        <section class="page-section"><h2>{{ __('Evidence preview') }}</h2>
            @if(!$preview['can_prepare'])<p role="status">{{ __('Some selected learners need published plans or completed results. Adjust the selection, complete results, or explicitly choose provisional reports.') }}</p>@endif
            @foreach($preview['cards'] as $payload)@include('academics.reports.results', ['payload' => $payload])@endforeach
            <form method="POST" action="{{ route('academic-reports.prepare') }}" aria-label="{{ __('Prepare reviewed reports') }}">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="fingerprint" value="{{ $preview['fingerprint'] }}">
                @foreach($preview['selection'] as $key => $value)@if(is_array($value))@foreach($value as $item)<input type="hidden" name="{{ $key }}[]" value="{{ $item }}">@endforeach @else<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                <button type="submit" class="m3-button m3-button--filled" @disabled(!$preview['can_prepare'])>{{ __('Prepare reviewed reports') }}</button>
            </form>
        </section>
    @endif
    @if($batch)
        <section class="page-section"><h2>{{ __('Report batch') }} {{ $batch['id'] }} · {{ __(ucfirst(str_replace('_', ' ', $batch['state']))) }}</h2><p>{{ $batch['design']['name'] }} · {{ count($batch['cards']) }} {{ __('learners') }}</p>
            @foreach((array) $batch['signatures'] as $signature)<p>{{ $signature['name'] }} · {{ $signature['signed_at'] }}</p>@endforeach
            <a href="{{ route('academic-reports.index', ['batch_id' => $batch['id']]) }}" class="m3-button m3-button--outlined">{{ __('Refresh batch') }}</a>
            @if($batch['state'] === 'approved')<p role="status">{{ __('Private PDFs are queued. Refresh after the jobs finish, then preview before release.') }}</p>@endif
            <div class="report-actions">
            @php $actions = []; if ($batch['state'] !== 'released') $actions['source-preview'] = __('Review current evidence'); if ($batch['state'] === 'draft' && $batch['can_grade_sign']) $actions['grade-sign'] = __('Grade-head sign-off'); if ($batch['state'] === 'grade_signed' && $batch['can_principal_sign']) $actions['principal-sign'] = __('Principal sign-off'); if (in_array($batch['state'], ['approved', 'ready'], true)) $actions['retry'] = __('Retry PDF generation'); if ($batch['state'] === 'ready') $actions['release-preview'] = __('Preview release'); @endphp
            @foreach($actions as $action => $label)<form method="POST" action="{{ route('academic-reports.action', [$batch['id'], $action]) }}">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="batch_id" value="{{ $batch['id'] }}"><input type="hidden" name="revision" value="{{ $batch['revision'] }}"><input type="hidden" name="fingerprint" value="{{ $batch['fingerprint'] }}"><button type="submit" class="m3-button m3-button--outlined">{{ $label }}</button>
            </form>@endforeach
            </div>
            @if($sourcePreview)
                <h3>{{ __('Current source evidence') }}</h3><p>{{ __('Refreshing clears signatures and requires fresh sign-off.') }}</p>
                @foreach($sourcePreview['cards'] as $payload)@include('academics.reports.results', ['payload' => $payload])@endforeach
                <form method="POST" action="{{ route('academic-reports.action', [$batch['id'], 'refresh']) }}">@csrf<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="batch_id" value="{{ $batch['id'] }}"><input type="hidden" name="revision" value="{{ $batch['revision'] }}"><input type="hidden" name="fingerprint" value="{{ $sourcePreview['fingerprint'] }}"><button type="submit" class="m3-button m3-button--filled" @disabled(!$sourcePreview['can_prepare'])>{{ __('Refresh reviewed evidence') }}</button></form>
            @endif
            @if($releasePreview)
                <h3>{{ __('Release preview') }}</h3><p>{{ count($releasePreview['cards']) }} {{ __('reports will become available to their learners and current guardians. This queues every eligible family notice. Fees never restrict delivery.') }}</p>
                <form method="POST" action="{{ route('academic-reports.action', [$batch['id'], 'release']) }}">@csrf<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="batch_id" value="{{ $batch['id'] }}"><input type="hidden" name="revision" value="{{ $releasePreview['revision'] }}"><input type="hidden" name="fingerprint" value="{{ $releasePreview['fingerprint'] }}"><button type="submit" class="m3-button m3-button--filled" @disabled(!$releasePreview['can_release'])>{{ __('Release and notify families') }}</button></form>
            @endif
            @foreach($batch['cards'] as $card)
                @include('academics.reports.results', ['payload' => $card['payload']])
                @if($card['panel_pdf'])<a href="{{ $card['panel_pdf'] }}" target="_blank" rel="noopener" class="m3-button m3-button--outlined">{{ __('Preview PDF for') }} {{ $card['payload']['learner_name'] }}</a>@endif
                @foreach($card['comments'] as $comment)<p class="report-comment-text">{{ $comment['scope_key'] === 'homeroom' ? __('Homeroom') : (collect($card['payload']['subjects'])->first(fn ($s) => 'subject:'.$s['subject_id'] === $comment['scope_key'])['subject_name'] ?? __('Subject')) }}: {{ $comment['text'] }}</p>@endforeach
                @if($batch['state'] !== 'released')
                    @foreach([null, ...array_column($card['payload']['subjects'], 'subject_id')] as $subject)
                        @php $scope = $subject === null ? 'homeroom' : 'subject:'.$subject; $saved = collect($card['comments'])->firstWhere('scope_key', $scope); $commentRow = ['card_id' => $card['id'], 'batch_revision' => $batch['revision'], 'subject_id' => $subject, 'revision' => $saved['revision'] ?? 0, 'text' => $saved['text'] ?? '', 'learner_name' => $card['payload']['learner_name'], 'period_name' => $card['payload']['year_name'].' '.($card['payload']['term_name'] ?? __('Annual report')), 'scope_label' => $subject === null ? __('Homeroom') : collect($card['payload']['subjects'])->firstWhere('subject_id', $subject)['subject_name']]; @endphp
                        @include('academics.reports.comment-form', ['commentRow' => $commentRow, 'allowOverride' => true])
                    @endforeach
                @endif
            @endforeach
        </section>
    @endif
    @if(!$batch)<section class="page-section"><h2>{{ __('My report comments') }}</h2><p>{{ __('Saving changed comments clears prior report signatures.') }}</p>
        @forelse($comments['rows'] as $commentRow)@include('academics.reports.comment-form', ['commentRow' => $commentRow, 'allowOverride' => false])@empty<p>{{ __('No report comments are currently allocated to you.') }}</p>@endforelse
        <nav aria-label="{{ __('Comment pages') }}">@if($comments['page'] > 1)<a href="{{ route('academic-reports.index', ['session_year_id' => $configuration['session_year_id'], 'comment_page' => $comments['page'] - 1]) }}">{{ __('Previous comments') }}</a>@endif @if($comments['page'] < $comments['last_page'])<a href="{{ route('academic-reports.index', ['session_year_id' => $configuration['session_year_id'], 'comment_page' => $comments['page'] + 1]) }}">{{ __('More allocated comments') }}</a>@endif</nav>
    </section>@endif
    <section class="page-section"><h2>{{ __('Report batches') }}</h2>
        @forelse($batches['batches'] as $row)<p><a href="{{ route('academic-reports.index', ['batch_id' => $row['id']]) }}">{{ __('Report batch') }} {{ $row['id'] }} · {{ __(ucfirst(str_replace('_', ' ', $row['state']))) }} · {{ $row['learner_count'] }} {{ __('learners') }}</a></p>@empty<p>{{ __('No report batches are available in your leadership scope.') }}</p>@endforelse
        <nav aria-label="{{ __('Batch pages') }}">@if($batches['page'] > 1)<a href="{{ route('academic-reports.index', ['session_year_id' => $configuration['session_year_id'], 'batch_page' => $batches['page'] - 1]) }}">{{ __('Newer report batches') }}</a>@endif @if($batches['page'] < $batches['last_page'])<a href="{{ route('academic-reports.index', ['session_year_id' => $configuration['session_year_id'], 'batch_page' => $batches['page'] + 1]) }}">{{ __('Earlier report batches') }}</a>@endif</nav>
    </section>
</div>
@endsection
