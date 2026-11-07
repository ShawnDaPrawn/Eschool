@extends('groups.layout')
@section('title', $dashboard['group']['name'])
@use('App\Services\Platform\Groups\SchoolGroupDashboard')
@section('content')
@php $show = fn (array $cell): string => SchoolGroupDashboard::display($cell, $dashboard['k']); @endphp
<x-page.header :title="$dashboard['group']['name']" :subtitle="__('Figures across the group\'s schools, computed now. Counts and shares only: no learner, parent or staff member is named here.')" :breadcrumbs="false">
    @if ($others)
        <x-slot:actions><a href="{{ route('group.index') }}" class="m3-button m3-button--text">{{ __('Your other groups') }}</a></x-slot:actions>
    @endif
</x-page.header>

<p>{{ __('Numbers from 1 to :max are hidden, so that no child can be picked out. A group total is hidden when showing it would reveal a hidden number.', ['max' => $dashboard['k'] - 1]) }}</p>

@if ($dashboard['schools'] === [])
    <x-page.empty-state icon="inbox" :title="__('No school is in the group yet')" :message="__('A school appears here once its Super Admin agrees to join the group.')" />
@else
<div class="group-tiles">
    <x-m3.card :title="__('Learners')" variant="filled"><p class="group-figure" data-total="learners">{{ $show($dashboard['totals']['learners']) }}</p></x-m3.card>
    <x-m3.card :title="__('Weekly active families')" variant="filled"><p class="group-figure" data-total="weekly_active_families">{{ $show($dashboard['totals']['weekly_active_families']) }}</p></x-m3.card>
    <x-m3.card :title="__('Guardian reach')" variant="filled"><p class="group-figure" data-total="guardian_reach">{{ $show($dashboard['totals']['guardian_reach']) }}</p></x-m3.card>
    <x-m3.card :title="__('Attendance, last :days days', ['days' => $dashboard['attendance_days']])" variant="filled"><p class="group-figure" data-total="attendance">{{ $show($dashboard['totals']['attendance']) }}</p></x-m3.card>
</div>

<div class="m3 m3-card m3-card--outlined page-table">
    <div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('Each school') }}">
        <table class="page-table__table">
            <caption class="m3-visually-hidden">{{ __('Each school') }}</caption>
            <thead><tr>
                <th scope="col">{{ __('School') }}</th>
                <th scope="col">{{ __('Learners') }}</th>
                <th scope="col">{{ __('Weekly active families') }}</th>
                <th scope="col">{{ __('Guardian reach') }}</th>
                <th scope="col">{{ __('Learners active in the app this week') }}</th>
                <th scope="col">{{ __('Attendance, last :days days', ['days' => $dashboard['attendance_days']]) }}</th>
            </tr></thead>
            <tbody>
            @foreach ($dashboard['schools'] as $row)
                <tr data-group-school="{{ $row['id'] }}">
                    <th scope="row">{{ $row['name'] }}</th>
                    <td>{{ $show($row['learners']) }}</td>
                    <td>{{ $show($row['weekly_active_families']) }}</td>
                    <td>{{ $show($row['guardian_reach']) }}</td>
                    <td>{{ $show($row['active_learners']) }}</td>
                    <td>{{ $show($row['attendance']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<p class="group-note">{{ __('Weekly active families: guardians who used NavigateEd in the last 7 days. Guardian reach: learners with a guardian the school can reach by verified email or the app. Attendance: present half-days of recorded school days. Computed :time.', ['time' => $dashboard['computed_at']]) }}</p>
<p class="group-note">{{ __('Being a group administrator gives no access inside a school. To work in one of the schools you need a role there, given by that school.') }}</p>
@endif
@endsection
