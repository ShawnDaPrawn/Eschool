@extends('layouts.master')
@section('title', __('Promote by grade'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Promote by grade')" :subtitle="__('Move every learner up a grade into next year in one go. Each homeroom goes to the homeroom with the same letter in the next grade; change any learner who repeats, leaves or goes to another homeroom.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($problems as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <form method="GET" action="{{ route('promotions.by-grade') }}" aria-label="{{ __('Choose the school years') }}" class="page-section">
        <x-page.field name="from" id="promotion-from" type="select" :label="__('Promote from')" :value="$from->id" :options="$years" :restore-old-input="false" />
        <x-page.field name="to" id="promotion-to" type="select" :label="__('Into')" :value="$to->id" :options="$years" :restore-old-input="false" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Show') }}</button>
    </form>

    @if ($plan !== null)
        <section class="page-section" aria-labelledby="promotion-summary">
            <h2 id="promotion-summary">{{ __('From :from into :to', ['from' => $from->name, 'to' => $to->name]) }}</h2>
            <p>{{ __(':promote promoted, :repeat repeating, :place placed, :leave leaving. :already already have a place for next year and stay as they are.', $plan['totals']) }}</p>
            @if ($plan['unresolved'] > 0)
                <p role="alert">{{ __('Choose a homeroom for every learner who has none (:count).', ['count' => $plan['unresolved']]) }}</p>
            @endif
            @if (! $checked && $plan['homerooms'] !== [])
                <p>{{ __('These are the default moves. Change any learner below, or paste a sheet, then check the changes before you promote.') }}</p>
            @endif
        </section>

        @if ($plan['homerooms'] === [])
            <x-page.empty-state :title="__('No learners to promote')" :message="__('No learner is in a homeroom on the last day of :year.', ['year' => $from->name])" />
        @else
            <form method="POST" action="{{ route('promotions.by-grade.preview') }}" id="grade-promotion-form" aria-label="{{ __('Promotion choices') }}" data-grade-promotion>
                @csrf
                <input type="hidden" name="from" value="{{ $from->id }}">
                <input type="hidden" name="to" value="{{ $to->id }}">
                <input type="hidden" name="fingerprint" value="{{ $plan['fingerprint'] }}">
                <datalist id="promotion-rooms">@foreach ($rooms as $room)<option value="{{ $room }}"></option>@endforeach</datalist>

                @foreach ($plan['homerooms'] as $homeroom)
                    <section class="page-section" aria-labelledby="promotion-room-{{ $homeroom['id'] }}">
                        <h3 id="promotion-room-{{ $homeroom['id'] }}">
                            @if ($homeroom['last_grade'])
                                {{ __(':room: last grade, leaving the school', ['room' => $homeroom['name']]) }}
                            @elseif ($homeroom['default_target_name'] !== null)
                                {{ __(':room to :next', ['room' => $homeroom['name'], 'next' => $homeroom['default_target_name']]) }}
                            @elseif ($homeroom['grade_unset'])
                                {{ __(':room: its grade is not set', ['room' => $homeroom['name']]) }}
                            @else
                                {{ __(':room: no homeroom with the same letter in the next grade', ['room' => $homeroom['name']]) }}
                            @endif
                        </h3>
                        @if ($homeroom['grade_unset'] && $homeroom['moved'] === null)
                            <p>{{ __('Set the grade of :room under Grades and homerooms, or choose next year\'s homeroom for everyone in it below.', ['room' => $homeroom['name']]) }}</p>
                        @endif
                        <x-page.field :name="'homerooms['.$homeroom['id'].']'" :id="'promotion-move-'.$homeroom['id']" :label="__('Next year\'s homeroom for :room', ['room' => $homeroom['name']])"
                            :value="$homeroom['moved'] !== null ? $homeroom['default_target_name'] : ''" :restore-old-input="false" list="promotion-rooms" maxlength="191" data-default=""
                            :help="__('Leave empty for the default. Learners you change below keep their own choice.')" />
                        <div class="page-table__scroll" role="region" aria-label="{{ __('Learners in :room', ['room' => $homeroom['name']]) }}" tabindex="0"><table class="page-table__table">
                            <caption class="m3-visually-hidden">{{ __('Learners in :room', ['room' => $homeroom['name']]) }}</caption>
                            <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Admission number') }}</th><th scope="col">{{ __('Next year') }}</th><th scope="col">{{ __('Homeroom') }}</th></tr></thead>
                            <tbody>
                            @foreach ($homeroom['learners'] as $learner)
                                <tr>
                                    <th scope="row">{{ $learner['name'] }}</th>
                                    <td>{{ $learner['admission_no'] }}</td>
                                    @if ($learner['already'] === 'placed')
                                        <td colspan="2">{{ __('Already placed in :room', ['room' => $learner['already_name'] ?? '']) }}</td>
                                    @elseif ($learner['already'] === 'leaving')
                                        <td colspan="2">{{ __('Already marked as leaving') }}</td>
                                    @else
                                        <td>
                                            <select name="learners[{{ $learner['id'] }}][outcome]" aria-label="{{ __('Next year for :name', ['name' => $learner['name']]) }}" data-default="{{ $homeroom['last_grade'] ? 'leave' : 'promote' }}">
                                                <option value="promote" @selected($learner['outcome'] === 'promote')>{{ __('Promote') }}</option>
                                                <option value="repeat" @selected($learner['outcome'] === 'repeat')>{{ __('Repeat the grade') }}</option>
                                                <option value="place" @selected($learner['outcome'] === 'place')>{{ __('Place in a homeroom') }}</option>
                                                <option value="leave" @selected($learner['outcome'] === 'leave')>{{ __('Leaves the school') }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" list="promotion-rooms" name="learners[{{ $learner['id'] }}][homeroom]" maxlength="191" data-default=""
                                                value="{{ $learner['outcome'] === 'place' ? $learner['target_name'] : '' }}"
                                                aria-label="{{ __('Homeroom for :name', ['name' => $learner['name']]) }}"
                                                @if ($learner['missing']) aria-invalid="true" @endif
                                                placeholder="{{ $learner['outcome'] === 'place' ? __('Type a homeroom') : ($learner['target_name'] ?? '') }}">
                                            @if ($learner['missing'])<span role="alert">{{ __('Choose a homeroom') }}</span>@endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table></div>
                    </section>
                @endforeach

                <section class="m3-card m3-card--outlined page-section" aria-labelledby="promotion-sheet"><div class="m3-card__content">
                    <h3 id="promotion-sheet">{{ __('Paste changes from a sheet') }}</h3>
                    <p>{{ __('One learner per line: admission number, outcome (promote, repeat, leave or place), and the homeroom for place, for example: 2024-0113, place, Grade 8 C') }}</p>
                    <x-page.field name="sheet" id="promotion-sheet-text" type="textarea" :label="__('Changes')" :value="''" :restore-old-input="false" />
                </div></section>

                <p>{{ __('Promoting writes each learner\'s homeroom for :year from its first day, :date. Learners who leave are marked as leaving; they keep their records.', ['year' => $to->name, 'date' => \Illuminate\Support\Carbon::parse($to->start_date)->toDateString()]) }}</p>
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Check changes') }}</button>
                @if ($canCommit)
                    <button class="m3-button m3-button--filled" type="submit" formaction="{{ route('promotions.by-grade.store') }}" @disabled($plan['unresolved'] > 0)>{{ __('Promote learners') }}</button>
                @endif
                {{-- Last on purpose: missing when the server drops fields from a very long form. --}}
                <input type="hidden" name="complete" value="1">
            </form>
        @endif
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/grade-promotion.js') }}" defer></script>@endsection
