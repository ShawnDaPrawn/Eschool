{{--
    The draft fields of an HQ announcement (SA.13), shared by the new-draft
    form and a draft's edit form. $values holds the saved draft (or nothing).
--}}
@use('App\Services\Platform\Saas\PlatformAnnouncements')
@php
    $values ??= [];
    $audience = old('audience', $values['audience'] ?? 'all');
    $chosen = array_map('strval', old($audience, ($values['audience'] ?? null) === $audience ? ($values['audience_values'] ?? []) : []));
    $selected = fn (string $field, string $value): bool => $audience === $field && in_array($value, $chosen, true);
@endphp
<div class="saas-grid">
    <div class="saas-field">
        <label for="{{ $prefix }}-kind">{{ __('Kind') }}</label>
        <select id="{{ $prefix }}-kind" name="kind" required>
            @foreach (PlatformAnnouncements::KINDS as $value => $label)<option value="{{ $value }}" @selected(old('kind', $values['kind'] ?? 'release_notes') === $value)>{{ __($label) }}</option>@endforeach
        </select>
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-severity">{{ __('Severity') }}</label>
        <select id="{{ $prefix }}-severity" name="severity" required>
            @foreach (PlatformAnnouncements::SEVERITIES as $value => $label)<option value="{{ $value }}" @selected(old('severity', $values['severity'] ?? 'info') === $value)>{{ __($label) }}</option>@endforeach
        </select>
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-title">{{ __('Title') }}</label>
        <input id="{{ $prefix }}-title" name="title" required minlength="5" maxlength="150" value="{{ old('title', $values['title'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-from">{{ __('Shows from (South African time)') }}</label>
        <input id="{{ $prefix }}-from" name="shows_from" type="datetime-local" required value="{{ old('shows_from', $values['shows_from_input'] ?? $now) }}">
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-until">{{ __('Shows until (optional)') }}</label>
        <input id="{{ $prefix }}-until" name="shows_until" type="datetime-local" value="{{ old('shows_until', $values['shows_until_input'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-window-start">{{ __('Maintenance starts (maintenance only)') }}</label>
        <input id="{{ $prefix }}-window-start" name="window_starts_at" type="datetime-local" value="{{ old('window_starts_at', $values['window_starts_input'] ?? '') }}">
    </div>
    <div class="saas-field">
        <label for="{{ $prefix }}-window-end">{{ __('Maintenance ends') }}</label>
        <input id="{{ $prefix }}-window-end" name="window_ends_at" type="datetime-local" value="{{ old('window_ends_at', $values['window_ends_input'] ?? '') }}">
    </div>
</div>
<div class="saas-field">
    <label for="{{ $prefix }}-body">{{ __('Text') }}</label>
    <textarea id="{{ $prefix }}-body" name="body" required minlength="10" maxlength="5000" rows="8" aria-describedby="{{ $prefix }}-body-help">{{ old('body', $values['body'] ?? '') }}</textarea>
    <p id="{{ $prefix }}-body-help">{{ __('Plain text. A blank line starts a new paragraph; lines starting with "- " make a list; **bold**; [words](https://link). Anything else, HTML included, shows exactly as typed.') }}</p>
</div>
<fieldset class="saas-field">
    <legend>{{ __('Which schools') }}</legend>
    @foreach (PlatformAnnouncements::AUDIENCES as $value => $label)
        <label><input type="radio" name="audience" value="{{ $value }}" @checked($audience === $value)> {{ __($label) }}</label>
    @endforeach
    <div class="saas-grid">
        <div class="saas-field">
            <label for="{{ $prefix }}-packages">{{ __('Packages (for schools on a package)') }}</label>
            <select id="{{ $prefix }}-packages" name="packages[]" multiple size="4">
                @foreach ($choices['packages'] as $package)<option value="{{ $package }}" @selected($selected('packages', $package))>{{ $package }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-stages">{{ __('Stages (for schools at a stage)') }}</label>
            <select id="{{ $prefix }}-stages" name="stages[]" multiple size="7">
                @foreach ($choices['stages'] as $stage)<option value="{{ $stage }}" @selected($selected('stages', $stage))>{{ __(ucfirst(str_replace('_', ' ', $stage))) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="{{ $prefix }}-schools">{{ __('Schools (for chosen schools)') }}</label>
            <select id="{{ $prefix }}-schools" name="schools[]" multiple size="7">
                @foreach ($choices['schools'] as $id => $name)<option value="{{ $id }}" @selected($selected('schools', (string) $id))>{{ $name }}</option>@endforeach
            </select>
        </div>
    </div>
</fieldset>
