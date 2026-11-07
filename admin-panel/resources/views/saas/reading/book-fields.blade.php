<div class="saas-grid">
    <div class="saas-field">
        <label for="book-gutenberg">{{ __('Gutenberg number') }}</label>
        <input id="book-gutenberg" name="gutenberg_id" type="number" min="1" required value="{{ old('gutenberg_id', $book['gutenberg_id'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="book-title">{{ __('Title') }}</label>
        <input id="book-title" name="title" required maxlength="255" value="{{ old('title', $book['title'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="book-author">{{ __('Author') }}</label>
        <input id="book-author" name="author" required minlength="2" maxlength="191" value="{{ old('author', $book['author'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="book-born">{{ __('Author born (year)') }}</label>
        <input id="book-born" name="author_birth_year" type="number" value="{{ old('author_birth_year', $book['author_birth_year'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="book-died">{{ __('Author died (year)') }}</label>
        <input id="book-died" name="author_death_year" type="number" value="{{ old('author_death_year', $book['author_death_year'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="book-language">{{ __('Language code (such as en)') }}</label>
        <input id="book-language" name="language" required pattern="[a-z]{2,3}" maxlength="3" value="{{ old('language', $book['language'] ?? 'en') }}">
    </div>
    <div class="saas-field">
        <label for="book-level">{{ __('Reading level') }}</label>
        <select id="book-level" name="reading_level" required>
            @foreach (\App\Services\Reading\ReadingCatalogue::LEVELS as $value => $label)<option value="{{ $value }}" @selected(old('reading_level', $book['reading_level'] ?? '') === $value)>{{ __($label) }}</option>@endforeach
        </select>
    </div>
    <div class="saas-field">
        <label for="book-subjects">{{ __('Subjects, one per line') }}</label>
        <textarea id="book-subjects" name="subjects" rows="3" maxlength="1000">{{ old('subjects', $book['subjects'] ?? '') }}</textarea>
    </div>
    <div class="saas-field">
        <label for="book-summary">{{ __('Short summary for learners') }}</label>
        <textarea id="book-summary" name="summary" rows="3" maxlength="1000">{{ old('summary', $book['summary'] ?? '') }}</textarea>
    </div>
</div>
