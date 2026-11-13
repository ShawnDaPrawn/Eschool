@extends('layouts.master')
@section('title', __('Write a practice question'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/question-author.css') }}?v={{ filemtime(public_path('assets/css/question-author.css')) }}">@endsection
@section('content')
@php
    $kindHelp = [
        'number' => __('One number. Learners type it with a dot for decimals.'),
        'choice' => __('Learners choose one of two to six options.'),
        'steps' => __('Two to eight worked steps; each step gets its own mark, and mastery counts the share of steps right.'),
        'short_text' => __('A few words. Answers that match an accepted answer (ignoring capitals, spaces and punctuation) are marked at once; any other answer waits for you in Marking.'),
        'diagram_label' => __('Learners place labels on numbered points of your picture. Marked right only when every label is in its place.'),
        'audio' => __('Learners record a spoken answer. You listen and mark it in Marking. Recordings are deleted after 90 days, or 30 days after you mark them.'),
    ];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Write a practice question')" :subtitle="__('Questions you write here join this subject and grade\'s question bank and are approved for the group you choose. Learners see only the question until the school has their answer; the accepted answers and your explanation follow it.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach
    <p><a class="m3-button m3-button--text" href="{{ route('question-banks.index') }}">{{ __('Question banks') }}</a> <a class="m3-button m3-button--text" href="{{ route('question-banks.marking') }}">{{ __('Marking') }}</a></p>

    @if ($groups === [])
        <x-page.empty-state :title="__('No groups to write for')" :message="__('You can write practice questions for the learning groups you teach this year.')" />
    @else
        <form method="GET" action="{{ route('question-banks.write') }}" class="page-section" aria-label="{{ __('Choose a group') }}">
            <x-page.field name="group" id="write-group" type="select" :label="__('Group')" :restore-old-input="false"
                :options="collect($groups)->mapWithKeys(fn ($g) => [$g['id'] => $g['name'].' · '.$g['subject_name']])->all()" :value="$group['id'] ?? null" />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Choose group') }}</button>
        </form>
    @endif

    @if ($choices !== null)
        @if ($choices['topics'] === [] || $choices['sources'] === [])
            <x-page.empty-state :title="__('Nothing to link the question to yet')" :message="__('A question needs a topic from the group\'s published curriculum plan and lesson material published to the group.')" />
        @else
        <form method="POST" action="{{ route('question-banks.create') }}" enctype="multipart/form-data" class="m3-card m3-card--outlined page-section" aria-labelledby="write-heading" data-question-author>
            <div class="m3-card__content">
                <h2 id="write-heading">{{ __('New question for :group', ['group' => $group['name']]) }}</h2>
                @csrf
                <input type="hidden" name="operation_id" value="{{ old('operation_id', $operationId) }}">
                <input type="hidden" name="group_id" value="{{ $group['id'] }}">
                <x-page.field name="topic_id" id="write-topic" type="select" :label="__('Topic')" :options="collect($choices['topics'])->pluck('name', 'id')->all()" required />
                <x-page.field name="content_version_id" id="write-source" type="select" :label="__('Lesson material it practises')" :options="collect($choices['sources'])->pluck('name', 'id')->all()" required />
                <x-page.field name="question_kind" id="write-kind" type="select" :label="__('Answer type')" :options="collect($kinds)->map(fn ($label) => __($label))->all()" value="number" required />
                <ul class="page-field-help">@foreach ($kinds as $kind => $label)<li data-question-kind-help="{{ $kind }}"><strong>{{ __($label) }}</strong>: {{ $kindHelp[$kind] }}</li>@endforeach</ul>
                <x-page.field name="estimated_seconds" id="write-seconds" type="number" :label="__('Expected time in seconds (60 to 300)')" value="120" min="60" max="300" required />
                <x-page.field name="prompt" id="write-prompt" type="textarea" :label="__('Question')" maxlength="4000" required />
                <x-page.field name="explanation" id="write-explanation" type="textarea" :label="__('Worked explanation')" maxlength="4000" required
                    :help="__('Learners see it only after the school has their answer.')" />

                <fieldset class="page-section" data-question-kinds="number choice short_text"><legend>{{ __('Number, choice or short written answer') }}</legend>
                    <x-page.field name="options" id="write-options" type="textarea" :label="__('Options, one per line as code: text (choice questions only)')" maxlength="7000" />
                    <x-page.field name="answers" id="write-answers" type="textarea" :label="__('Accepted answers, one per line (option codes for choice questions; any wording that counts for a short written answer)')" maxlength="4500" />
                </fieldset>
                <fieldset class="page-section" data-question-kinds="steps"><legend>{{ __('Worked steps') }}</legend>
                    <x-page.field name="steps" id="write-steps" type="textarea" rows="5" :label="__('Worked steps, one per line as: step | answer, or step | answer code | a: option; b: option')" maxlength="12000" />
                </fieldset>
                <fieldset class="page-section" data-question-kinds="diagram_label"><legend>{{ __('Label a diagram') }}</legend>
                    <x-page.field name="diagram" id="write-diagram" type="file" :label="__('Diagram picture')" accept="image/jpeg,image/png" :restore-old-input="false"
                        :help="__('Choose a JPEG or PNG up to 4 MB. It is checked, stripped of camera details and stored privately for your school.')" />
                    @if (old('diagram_id'))<input type="hidden" name="diagram_id" value="{{ old('diagram_id') }}">@endif
                    <div data-diagram-preview hidden>
                        <p>{{ __('Tap the diagram to add a region. With a keyboard, activate the preview to add a centre point, then adjust its position below.') }}</p>
                        <button type="button" class="question-diagram-preview" data-diagram-add aria-label="{{ __('Add a region to this diagram') }}">
                            <img data-diagram-picture alt="{{ __('Diagram preview') }}" @if(old('diagram_id')) src="{{ route('question-banks.diagram', old('diagram_id')) }}" @endif>
                            <svg data-diagram-points viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"></svg>
                        </button>
                    </div>
                    <x-page.field name="regions" id="write-regions" type="textarea" :label="__('Diagram regions, one per line as code: across, down (per cent from the top left)')" maxlength="400" />
                    <x-page.field name="labels" id="write-labels" type="textarea" :label="__('Labels to place, one per line as code: text (extra labels are allowed)')" maxlength="4000" />
                    <x-page.field name="mapping" id="write-mapping" type="textarea" :label="__('Correct label for each region, one per line as region: label')" maxlength="400" />
                </fieldset>
                <fieldset class="page-section" data-question-kinds="audio"><legend>{{ __('A spoken answer') }}</legend>
                    <x-page.field name="max_seconds" id="write-max-seconds" type="number" :label="__('Longest recording in seconds, 10 to 180 (spoken answers only)')" min="10" max="180" />
                </fieldset>
                @php($editorLabels = [
                    'code' => __('Code'), 'text' => __('Option or label text'), 'remove' => __('Remove row'),
                    'options' => __('Choice options'), 'option' => __('Option'), 'addOption' => __('Add option'),
                    'answers' => __('Accepted answers'), 'answer' => __('Answer'), 'addAnswer' => __('Add accepted answer'),
                    'correctCode' => __('Correct option'), 'steps' => __('Worked steps'), 'step' => __('Step'),
                    'stepPrompt' => __('Step question'), 'stepAnswers' => __('Accepted step answers, one per line'),
                    'stepHelp' => __('Use numbers for a numeric step. Add choice options and use their codes for a choice step.'),
                    'addStep' => __('Add step'), 'removeStep' => __('Remove step'), 'labels' => __('Diagram labels'),
                    'label' => __('Label'), 'addLabel' => __('Add label'), 'regions' => __('Diagram regions and correct labels'),
                    'region' => __('Region'), 'across' => __('Across from left (%)'), 'down' => __('Down from top (%)'),
                    'correctLabel' => __('Correct diagram label'), 'choose' => __('Choose an option'), 'unavailable' => __('No matching option'), 'addRegion' => __('Add region'),
                ])
                <script type="application/json" data-question-editor-labels>@json($editorLabels)</script>
                <button class="m3-button m3-button--filled" type="submit">{{ __('Approve this question') }}</button>
            </div>
        </form>
        @endif
    @endif
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/custom/question-author.js') }}?v={{ filemtime(public_path('assets/js/custom/question-author.js')) }}" defer></script>
@endsection
