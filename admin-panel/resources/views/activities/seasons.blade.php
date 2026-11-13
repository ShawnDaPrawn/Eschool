@extends('layouts.master')
@section('title', __('Start a new season'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/activity-workbench.css') }}?v={{ filemtime(public_path('assets/css/activity-workbench.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content academic-workbench">
    <x-page.header :title="__('Start a new season')" :subtitle="__('Copy one season\'s teams and groups into the next season, for example summer to winter. The new groups keep their place, their sport or activity and their eligibility rules, but nobody is in them: members, captains and staff are chosen again. The old season\'s groups stay as they are.')" />
    <p><a href="{{ route('activity-groups.index') }}">{{ __('Back to activity groups') }}</a></p>
    <p>{{ __('School year') }}: {{ $year->name }}. {{ __('A season is within this school year. To change the school year for all modules, use the global school-year workspace.') }}</p>
    @if($readOnly)<p role="status">{{ __('This school year is read only. Select the current school year to start a season.') }}</p>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="season-list">
        <h2 id="season-list">{{ __('Seasons in :year', ['year' => $year->name]) }}</h2>
        @if ($seasons === [])
            <x-page.empty-state :title="__('No activity groups yet')" :message="__('Add groups under Activity groups first.')" />
        @else
            <ul>
                @foreach ($seasons as $season => $count)
                    <li>{{ trans_choice(':season: :count active group|:season: :count active groups', $count, ['season' => $season, 'count' => $count]) }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($seasons !== [] && !$readOnly)
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="season-start"><div class="m3-card__content">
        <h2 id="season-start">{{ __('Start the next season') }}</h2>
        <form method="POST" action="{{ route('activity-seasons.store') }}" aria-label="{{ __('Start the next season') }}">
            @csrf
            <x-page.field name="from" id="season-from" type="select" :label="__('Copy the groups of')" :options="array_combine(array_keys($seasons), array_keys($seasons))" required />
            <x-page.field name="to" id="season-to" :label="__('New season')" maxlength="64" required />
            <x-page.field name="starts_on" id="season-starts" type="date" :label="__('First day')" required />
            <x-page.field name="ends_on" id="season-ends" type="date" :label="__('Last day')" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Start the season') }}</button>
        </form>
    </div></section>
    @endif
</div>
@endsection
