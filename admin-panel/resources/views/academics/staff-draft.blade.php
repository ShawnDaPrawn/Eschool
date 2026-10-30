@extends('layouts.master')
@section('title', __('Staff draft'))
@section('content')
@php
    $choices = ['' => __('Nobody')] + $plan['teachers'];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Staff draft for :year', ['year' => $plan['to']->name])" :subtitle="__('Last year\'s teachers are filled in wherever the same group or homeroom carried over from :from. Change what is different, then apply the draft. Teachers see nothing until it is applied.', ['from' => $plan['from']->name])" />
    @include('academics.groups-status')
    <a href="{{ route('academic-allocations.index') }}">{{ __('Academic allocations') }}</a>
    <form method="GET" action="{{ route('academic-allocations.draft') }}" aria-label="{{ __('Choose the school year') }}" class="page-section">
        <x-page.field id="staff-draft-year" name="year" type="select" :label="__('School year')" :options="$years" :value="$plan['to']->id" :restore-old-input="false" required />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Show') }}</button>
    </form>

    <form method="POST" action="{{ route('academic-allocations.draft.apply') }}" aria-label="{{ __('Staff draft') }}">@csrf
        <input type="hidden" name="year" value="{{ $plan['to']->id }}">
        <input type="hidden" name="fingerprint" value="{{ $plan['fingerprint'] }}">
        <p>{{ __('Applying writes each chosen teacher for the whole year, :start to :end.', ['start' => $plan['starts_on'], 'end' => $plan['ends_on']]) }}</p>
        @if ($plan['waiting'] > 0)<p>{{ __('This draft is too large to send at once. Apply it, then the page offers the remaining :waiting.', ['waiting' => $plan['waiting']]) }}</p>@endif

        @foreach (['groups' => __('Teaching groups'), 'homerooms' => __('Homeroom teachers')] as $kind => $heading)
            <section class="page-section" aria-labelledby="staff-draft-{{ $kind }}">
                <h2 id="staff-draft-{{ $kind }}">{{ $heading }}</h2>
                @if ($plan[$kind] === [])
                    <p>{{ $kind === 'groups' ? __('This year has no teaching groups yet.') : __('No homeroom is in use this year or last year.') }}</p>
                @endif
                @foreach ($plan[$kind] as $row)
                    @php $title = $kind === 'groups' ? $row['grade'].' · '.$row['subject'].' · '.$row['name'] : $row['name']; @endphp
                    <section class="m3-card m3-card--outlined page-section" aria-labelledby="staff-draft-{{ $kind }}-{{ $row['id'] }}"><div class="m3-card__content">
                        <h3 id="staff-draft-{{ $kind }}-{{ $row['id'] }}">{{ $title }}</h3>
                        @if ($row['staffed'] !== [])
                            <p>{{ __('Already allocated: :names', ['names' => collect($row['staffed'])->pluck('name')->join(', ')]) }}</p>
                            @if ($kind === 'groups')<a href="{{ route('teaching-groups.show', $row['id']) }}">{{ __('Open the group') }}</a>@endif
                        @elseif (! $row['in_form'])
                            <p>{{ __('Offered once the rows above are applied.') }}</p>
                        @else
                            @if ($row['last_year'] === [])
                                <p>{{ $kind === 'groups' && $row['source_id'] === null ?__('New this year: there was no such group last year.') : __('Nobody held it at the end of last year.') }}</p>
                            @else
                                <p>{{ __('Last year: :names', ['names' => collect($row['last_year'])->map(fn ($t) => $t['on_staff'] ? $t['name'] : __(':name (no longer on the staff)', ['name' => $t['name']]))->join(', ')]) }}</p>
                            @endif
                            {{-- One field per drafted teacher, or one empty field: the form stays within the server's input limit. --}}
                            @foreach ($row['draft'] === [] ? [''] : $row['draft'] as $slot => $teacher)
                                <x-page.field :id="'staff-draft-'.$kind.'-'.$row['id'].'-'.$slot" :name="$kind.'['.$row['id'].'][]'" type="select"
                                    :label="$teacher === '' ? __('Add a teacher') : __('Teacher')" :options="$choices" :value="$teacher" :restore-old-input="false" />
                            @endforeach
                        @endif
                    </div></section>
                @endforeach
            </section>
        @endforeach

        {{-- Last on purpose: if the server drops trailing fields, this goes first and nothing is applied. --}}
        <input type="hidden" name="complete" value="1">
        <button class="m3-button m3-button--filled" type="submit">{{ __('Apply the draft') }}</button>
    </form>
</div>
@endsection
