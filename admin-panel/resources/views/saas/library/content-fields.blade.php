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
            <label for="{{ $prefix }}-answers">{{ __('Accepted answers, one per line (option codes for choice questions)') }}</label>
            <textarea id="{{ $prefix }}-answers" name="answers" maxlength="1100" rows="2">{{ $value('answers') }}</textarea>
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
