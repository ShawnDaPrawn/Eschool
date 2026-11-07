{{-- The content fields of a library item. $kind is null when creating (every field shown, by kind), or the item's kind when editing. --}}
@php
    $value = fn (string $key) => old($key, $fields[$key] ?? null);
    $question = $kind === null || in_array($kind, ['practice_question', 'past_paper_question'], true);
@endphp
@if ($question)
<fieldset class="saas-card">
    <legend>{{ $kind === null ? __('Practice and past-paper questions') : __('Question') }}</legend>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="{{ $prefix }}-question-kind">{{ __('Answer type') }}</label>
            <select id="{{ $prefix }}-question-kind" name="question_kind">
                <option value="number" @selected($value('question_kind') === 'number')>{{ __('A number') }}</option>
                <option value="choice" @selected($value('question_kind') === 'choice')>{{ __('Choose an option') }}</option>
                <option value="steps" @selected($value('question_kind') === 'steps')>{{ __('Worked steps, each marked') }}</option>
                <option value="short_text" @selected($value('question_kind') === 'short_text')>{{ __('A short written answer') }}</option>
                <option value="diagram_label" @selected($value('question_kind') === 'diagram_label')>{{ __('Label a diagram') }}</option>
                <option value="audio" @selected($value('question_kind') === 'audio')>{{ __('A spoken answer') }}</option>
            </select>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-seconds">{{ __('Expected time in seconds (60 to 300)') }}</label>
            <input id="{{ $prefix }}-seconds" name="estimated_seconds" type="number" min="60" max="300" value="{{ $value('estimated_seconds') ?? 120 }}">
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-prompt">{{ __('Question') }}</label>
            <textarea id="{{ $prefix }}-prompt" name="prompt" maxlength="4000" rows="3">{{ $value('prompt') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-explanation">{{ __('Worked explanation') }}</label>
            <textarea id="{{ $prefix }}-explanation" name="explanation" maxlength="4000" rows="3">{{ $value('explanation') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-options">{{ __('Options, one per line as code: text (choice questions only)') }}</label>
            <textarea id="{{ $prefix }}-options" name="options" maxlength="7000" rows="4">{{ $value('options') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-answers">{{ __('Accepted answers, one per line (option codes for choice questions; any wording that counts for a short written answer)') }}</label>
            <textarea id="{{ $prefix }}-answers" name="answers" maxlength="4500" rows="2">{{ $value('answers') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-steps">{{ __('Worked steps, one per line as: step | answer, or step | answer code | a: option; b: option') }}</label>
            <textarea id="{{ $prefix }}-steps" name="steps" maxlength="12000" rows="4">{{ $value('steps') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-diagram">{{ __('Diagram picture (JPEG or PNG; labelling questions only)') }}</label>
            <input id="{{ $prefix }}-diagram" name="diagram" type="file" accept="image/jpeg,image/png">
            @if ($value('diagram_id'))
                <input type="hidden" name="diagram_id" value="{{ $value('diagram_id') }}">
                <p><a href="{{ route('saas.library.diagrams.show', $value('diagram_id')) }}" target="_blank" rel="noopener">{{ __('Current diagram picture') }}</a> · {{ __('Choose a new picture only to replace it.') }}</p>
            @endif
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-regions">{{ __('Diagram regions, one per line as code: across, down (per cent from the top left)') }}</label>
            <textarea id="{{ $prefix }}-regions" name="regions" maxlength="400" rows="3">{{ $value('regions') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-labels">{{ __('Labels to place, one per line as code: text (extra labels are allowed)') }}</label>
            <textarea id="{{ $prefix }}-labels" name="labels" maxlength="4000" rows="3">{{ $value('labels') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-mapping">{{ __('Correct label for each region, one per line as region: label') }}</label>
            <textarea id="{{ $prefix }}-mapping" name="mapping" maxlength="400" rows="3">{{ $value('mapping') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-max-seconds">{{ __('Longest recording in seconds, 10 to 180 (spoken answers only)') }}</label>
            <input id="{{ $prefix }}-max-seconds" name="max_seconds" type="number" min="10" max="180" value="{{ $value('max_seconds') }}">
        </div>
        @if ($kind === null || $kind === 'past_paper_question')
        <div class="saas-field">
            <label for="{{ $prefix }}-paper-year">{{ __('Past paper: year') }}</label>
            <input id="{{ $prefix }}-paper-year" name="paper_year" type="number" min="2000" max="2100" value="{{ $value('paper_year') }}">
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-paper-code">{{ __('Past paper: paper code') }}</label>
            <input id="{{ $prefix }}-paper-code" name="paper_code" maxlength="32" value="{{ $value('paper_code') }}">
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-question-number">{{ __('Past paper: question number') }}</label>
            <input id="{{ $prefix }}-question-number" name="question_number" maxlength="16" value="{{ $value('question_number') }}">
        </div>
        @endif
    </div>
</fieldset>
@endif
@if ($kind === null || $kind === 'lesson')
<fieldset class="saas-card">
    <legend>{{ __('Lesson') }}</legend>
    <div class="saas-field">
        <label for="{{ $prefix }}-body">{{ __('Lesson text (Markdown)') }}</label>
        <textarea id="{{ $prefix }}-body" name="body" maxlength="20000" rows="8">{{ $value('body') }}</textarea>
    </div>
</fieldset>
@endif
@if ($kind === null || $kind === 'diagnostic')
<fieldset class="saas-card">
    <legend>{{ __('Diagnostic') }}</legend>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="{{ $prefix }}-instructions">{{ __('Instructions') }}</label>
            <textarea id="{{ $prefix }}-instructions" name="instructions" maxlength="2000" rows="2">{{ $value('instructions') }}</textarea>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-items">{{ __('Published practice question numbers to include, one per line') }}</label>
            <textarea id="{{ $prefix }}-items" name="item_list" maxlength="400" rows="3" aria-describedby="{{ $prefix }}-items-hint">{{ old('item_list', implode("\n", (array) ($fields['item_ids'] ?? []))) }}</textarea>
            <p id="{{ $prefix }}-items-hint">{{ __('Item numbers are shown on each item\'s page.') }}</p>
        </div>
    </div>
</fieldset>
@endif
