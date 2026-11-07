<div class="saas-grid">
    <div class="saas-field">
        <label for="shelf-name">{{ __('Name') }}</label>
        <input id="shelf-name" name="name" required minlength="2" maxlength="120" value="{{ old('name', $shelf['name'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="shelf-from">{{ __('From grade') }}</label>
        <select id="shelf-from" name="grade_from" required>
            @foreach ($gradeChoices as $order => $grade)<option value="{{ $order }}" @selected((string) old('grade_from', $shelf['grade_from'] ?? '') === (string) $order)>{{ $grade }}</option>@endforeach
        </select>
    </div>
    <div class="saas-field">
        <label for="shelf-to">{{ __('To grade') }}</label>
        <select id="shelf-to" name="grade_to" required>
            @foreach ($gradeChoices as $order => $grade)<option value="{{ $order }}" @selected((string) old('grade_to', $shelf['grade_to'] ?? '') === (string) $order)>{{ $grade }}</option>@endforeach
        </select>
    </div>
    <div class="saas-field">
        <label for="shelf-description">{{ __('Description for learners') }}</label>
        <input id="shelf-description" name="description" maxlength="500" value="{{ old('description', $shelf['description'] ?? '') }}">
    </div>
    <div class="saas-field">
        <input type="hidden" name="active" value="0">
        <label><input type="checkbox" name="active" value="1" @checked(old('active', ($shelf['active'] ?? true) ? '1' : '0') === '1')> {{ __('Shown to learners') }}</label>
    </div>
</div>
