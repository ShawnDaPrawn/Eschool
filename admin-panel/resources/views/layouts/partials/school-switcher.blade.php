{{--
    The school switcher, beside the year switcher: only for people with
    memberships at more than one school. Choosing a school posts the form
    (panel.js, .js-school-switcher); without JavaScript the button does.
    $suffix keeps the ids unique between the header and the drawer.
--}}
@php
    $switcherSchools = Auth::check() ? app(\App\Services\Platform\Identity\MembershipService::class)->schools(Auth::user()) : [];
    $switcherCurrent = app(\App\Services\Platform\Tenancy\CurrentSchool::class)->idOrNull();
    $suffix = $suffix ?? '';
@endphp
@if (count($switcherSchools) > 1)
    <form method="POST" action="{{ route('school.switch') }}" class="panel-school {{ $class ?? '' }}" data-year-safe>
        @csrf
        <label class="m3-text-field m3-text-field--select panel-field--compact panel-school__field">
            <select name="school_id" id="panel-school{{ $suffix }}" class="m3-text-field__input js-school-switcher"
                data-selected="{{ $switcherCurrent }}" aria-labelledby="panel-school-label{{ $suffix }}">
                @foreach ($switcherSchools as $membership)
                    <option value="{{ $membership['school']->id }}" @selected($membership['school']->id === $switcherCurrent)>
                        {{ $membership['school']->name }}
                    </option>
                @endforeach
            </select>
            <span class="m3-text-field__label" id="panel-school-label{{ $suffix }}">{{ __('school_switcher') }}</span>
        </label>
        <noscript>
            <button type="submit" class="m3-button m3-button--text">{{ __('switch_school') }}</button>
        </noscript>
    </form>
@endif
