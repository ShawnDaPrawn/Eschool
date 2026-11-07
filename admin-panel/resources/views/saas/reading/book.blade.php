@extends('saas.layout')

@section('title', $book['title'])

@section('content')
<p><a href="{{ route('saas.reading.index') }}">{{ __('Reading library') }}</a></p>
<h1>{{ $book['title'] }}</h1>
<p class="saas-lead">{{ $book['author'] }}@if ($book['author_birth_year'] || $book['author_death_year']) ({{ $book['author_birth_year'] ?? '?' }}–{{ $book['author_death_year'] ?? '?' }})@endif · {{ __('Gutenberg number') }} {{ $book['gutenberg_id'] }} · {{ __($levels[$book['reading_level']] ?? $book['reading_level']) }}</p>
<p><span class="saas-badge {{ $book['cleared'] ? '' : 'saas-badge--warn' }}">{{ __($book['rights']) }}</span>
    @if ($shelves !== []) · {{ __('On shelves') }}: @foreach ($shelves as $shelf)<a href="{{ route('saas.reading.shelves.show', $shelf['id']) }}">{{ $shelf['name'] }}</a>@if (! $loop->last), @endif @endforeach @endif</p>

<h2>{{ __('Rights decision') }}</h2>
<p>{{ __('South African copyright in a literary work generally lasts for the author\'s life and 50 years from the end of the year they died. A translation, an edited text or illustrations can carry their own copyright. This rule still needs our legal adviser\'s confirmation, so write down how you checked.') }}</p>
@if ($book['term_ended'] === false)
    <p class="saas-card" role="status">{{ __('By the author\'s death year, South African copyright has not ended. This book cannot be cleared.') }}</p>
@endif
@if ($decisions !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Rights decisions') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Decision') }}</th><th scope="col">{{ __('Basis') }}</th><th scope="col">{{ __('Translator checked') }}</th><th scope="col">{{ __('By') }}</th><th scope="col">{{ __('When') }}</th></tr></thead>
        <tbody>
        @foreach ($decisions as $decision)
            <tr>
                <th scope="row">{{ __($decision['decision']) }}@if ($decision['current']) · {{ __('current') }}@endif</th>
                <td>{{ $decision['basis'] }}</td>
                <td>{{ $decision['translator_checked'] ? __('Yes') : __('No') }}</td>
                <td>{{ $decision['by'] }}</td>
                <td>{{ $decision['at'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.reading.books.rights', $book['id']) }}" class="saas-card" aria-label="{{ __('Record a rights decision') }}">
    @csrf
    <input type="hidden" name="revision" value="{{ $book['revision'] }}">
    <h3>{{ __('Record a rights decision') }}</h3>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="rights-decision">{{ __('Decision') }}</label>
            <select id="rights-decision" name="decision" required>
                @foreach ($decisionChoices as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="rights-death">{{ __('Author died (year)') }}</label>
            <input id="rights-death" name="author_death_year" type="number" value="{{ old('author_death_year', $book['author_death_year']) }}">
        </div>
        <div class="saas-field">
            <label for="rights-basis">{{ __('Basis: how you checked, and for whom') }}</label>
            <textarea id="rights-basis" name="basis" rows="3" required minlength="20" maxlength="2000">{{ old('basis') }}</textarea>
        </div>
        <div class="saas-field">
            <input type="hidden" name="translator_checked" value="0">
            <label><input type="checkbox" name="translator_checked" value="1"> {{ __('I checked the translator, editor and illustrator of this edition') }}</label>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Record the decision') }}</button></div>
</form>

<h2>{{ __('Text') }}</h2>
<p>{{ __($text['state']) }}@if ($text['pages']) · {{ trans_choice(':count page|:count pages', $text['pages'], ['count' => $text['pages']]) }} · {{ __(':count words', ['count' => $text['words']]) }}@endif @if ($text['fetched_at']) · {{ __('fetched :at', ['at' => $text['fetched_at']]) }}@endif</p>
@if ($text['error'])<p class="saas-card" role="status">{{ __('Last fetch failed') }}: {{ $text['error'] }}</p>@endif
@if ($text['removed_lines'] > 0)<p>{{ trans_choice('{1} 1 line naming Project Gutenberg was removed from the body.|[2,*] :count lines naming Project Gutenberg were removed from the body.', $text['removed_lines'], ['count' => $text['removed_lines']]) }}</p>@endif
<form method="POST" action="{{ route('saas.reading.books.fetch', $book['id']) }}" class="saas-inline">
    @csrf
    <button class="saas-btn saas-btn--quiet" type="submit" @disabled(! $mirror)>{{ $text['ready'] ? __('Fetch the text again') : __('Fetch the text now') }}</button>
</form>
@if ($text['header'] !== null)
    <h3>{{ __('Original header (for the rights check, never shown to learners)') }}</h3>
    <pre class="saas-card">{{ $text['header'] }}</pre>
@endif
@if ($text['preview'] !== null)
    <h3>{{ __('First page as learners see it') }}</h3>
    <pre class="saas-card">{{ $text['preview'] }}</pre>
@endif

<h2>{{ __('Comprehension questions') }}</h2>
<p>{{ __('Learners see a question once they reach its page, or at the end of the book. They see the right answer only after answering. No marks are compared between learners.') }}</p>
@if ($questions !== [])
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Comprehension questions') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Question') }}</th><th scope="col">{{ __('Options') }}</th><th scope="col">{{ __('Asked') }}</th><th scope="col">{{ __('Change') }}</th></tr></thead>
        <tbody>
        @foreach ($questions as $question)
            <tr>
                <th scope="row">{{ $question['prompt'] }}@if ($question['explanation'])<br><small>{{ $question['explanation'] }}</small>@endif</th>
                <td>@foreach ($question['options'] as $index => $option){{ $option }}@if ($index === $question['answer']) ({{ __('right') }})@endif @if (! $loop->last)<br>@endif @endforeach</td>
                <td>{{ $question['after_page'] === null ? __('At the end') : __('From page :page', ['page' => $question['after_page']]) }}</td>
                <td>
                    @if ($question['active'])
                        <form class="saas-inline" method="POST" action="{{ route('saas.reading.questions.retire', $question['id']) }}">
                            @csrf
                            <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Retire') }}</button>
                        </form>
                    @else
                        {{ __('Retired') }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<form method="POST" action="{{ route('saas.reading.questions.store', $book['id']) }}" class="saas-card" aria-label="{{ __('Add a question') }}">
    @csrf
    <h3>{{ __('Add a question') }}</h3>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="question-prompt">{{ __('Question') }}</label>
            <input id="question-prompt" name="prompt" required minlength="5" maxlength="500" value="{{ old('prompt') }}">
        </div>
        @foreach (range(1, 5) as $box)
            <div class="saas-field">
                <label for="question-option-{{ $box }}">{{ __('Option :number', ['number' => $box]) }}</label>
                <input id="question-option-{{ $box }}" name="option_{{ $box }}" maxlength="200" @if ($box <= 2) required @endif value="{{ old('option_'.$box) }}">
            </div>
        @endforeach
        <div class="saas-field">
            <label for="question-right">{{ __('Right option') }}</label>
            <select id="question-right" name="right_option" required>
                @foreach (range(1, 5) as $box)<option value="{{ $box }}">{{ __('Option :number', ['number' => $box]) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="question-page">{{ __('Ask from page (empty: at the end)') }}</label>
            <input id="question-page" name="after_page" type="number" min="1" value="{{ old('after_page') }}">
        </div>
        <div class="saas-field">
            <label for="question-explanation">{{ __('Explanation shown after answering') }}</label>
            <input id="question-explanation" name="explanation" maxlength="1000" value="{{ old('explanation') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the question') }}</button></div>
</form>

<h2>{{ __('Book details') }}</h2>
<form method="POST" action="{{ route('saas.reading.books.update', $book['id']) }}" class="saas-card" aria-label="{{ __('Book details') }}">
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $book['revision'] }}">
    <p>{{ __('Changing the Gutenberg number or the author\'s death year of a decided book sets its rights back to not decided.') }}</p>
    @include('saas.reading.book-fields', ['book' => $book])
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save the details') }}</button></div>
</form>
@endsection
