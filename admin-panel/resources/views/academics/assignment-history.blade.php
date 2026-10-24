@php
    $person = match ($kind) { 'memberships' => $row->learner?->user, 'teachers', 'homerooms' => $row->teacher?->user, 'leadership' => $row->user };
    $personName = $person ? trim($person->first_name.' '.$person->last_name) : __('Archived person');
    $identity = match ($kind) { 'memberships' => ['learner_id', 'mark_rule'], 'teachers' => ['teacher_id'], 'homerooms' => ['teacher_id', 'class_section_id', 'session_year_id'], 'leadership' => ['user_id', 'session_year_id', 'kind', 'grade_level_id'] };
    $prefix = 'assignment-'.$kind.'-'.$row->id;
@endphp
<section class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
    <h3>{{ $personName }}</h3>
    <p>{{ $row->starts_on->toDateString() }} – {{ $row->ends_on->toDateString() }}</p>
    @if ($kind === 'memberships')<p>{{ __('Mark rule') }}: {{ $row->mark_rule }}</p>@endif
    @if ($kind === 'homerooms')<p>{{ $rooms[$row->class_section_id] ?? '' }}</p>@endif
    @if ($kind === 'leadership')<p>{{ __($row->kind) }} · {{ $grades[$row->grade_level_id] ?? __('All grades') }}</p>@endif
    <p>{{ $row->reason }}</p>
    @if ($manage)
        <details><summary>{{ __('Correct effective dates') }}: {{ $personName }}</summary>
            <form method="POST" action="{{ isset($group) ? route('teaching-groups.rows.update', [$group->id, $kind, $row->id]) : route('academic-allocations.update', [$kind, $row->id]) }}">@csrf @method('PUT')
                <input type="hidden" name="revision" value="{{ $row->revision }}">
                @foreach ($identity as $key)<input type="hidden" name="{{ $key }}" value="{{ $row->$key }}">@endforeach
                <label class="m3-text-field" for="{{ $prefix }}-start"><input class="m3-text-field__input" id="{{ $prefix }}-start" name="starts_on" type="date" required value="{{ $row->starts_on->toDateString() }}"><span class="m3-text-field__label">{{ __('Starts on') }}</span></label>
                <label class="m3-text-field" for="{{ $prefix }}-end"><input class="m3-text-field__input" id="{{ $prefix }}-end" name="ends_on" type="date" required value="{{ $row->ends_on->toDateString() }}"><span class="m3-text-field__label">{{ __('Ends on') }}</span></label>
                <label class="m3-text-field" for="{{ $prefix }}-reason"><input class="m3-text-field__input" id="{{ $prefix }}-reason" name="reason" maxlength="500" required value="{{ $row->reason }}"><span class="m3-text-field__label">{{ __('Reason') }}</span></label>
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Save dated correction') }}</button>
            </form>
        </details>
    @endif
</div></section>
