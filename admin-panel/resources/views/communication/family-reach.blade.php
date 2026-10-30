@extends('layouts.master')
@section('title', __('Family reach'))
@section('content')
@php
    $total = max(1, $report['total']);
    $reached = $report['counts']['app'] + $report['counts']['email'];
    $reasons = ['unreachable' => __('Guardians cannot be reached yet'), 'no_guardian' => __('No guardian recorded')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Family reach')" :subtitle="__('Current learners with a guardian the school can reach: in the parent app, or by a verified email address.')" />
    <section class="page-section" aria-labelledby="reach-summary">
        <h2 id="reach-summary">{{ __(':reached of :total learners reached (:percent%)', ['reached' => $reached, 'total' => $report['total'], 'percent' => (int) round($reached * 100 / $total)]) }}</h2>
        <ul>
            <li>{{ __('Parent app') }}: {{ $report['counts']['app'] }}</li>
            <li>{{ __('Verified email only') }}: {{ $report['counts']['email'] }}</li>
            <li>{{ __('Guardians cannot be reached yet') }}: {{ $report['counts']['unreachable'] }}</li>
            <li>{{ __('No guardian recorded') }}: {{ $report['counts']['no_guardian'] }}</li>
        </ul>
    </section>
    <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <table class="table">
            <caption>{{ __('Reach by homeroom') }}</caption>
            <thead><tr><th scope="col">{{ __('Homeroom') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Reached') }}</th></tr></thead>
            <tbody>
            @forelse ($report['by_homeroom'] as $row)
                <tr><th scope="row">{{ $row['homeroom'] }}</th><td>{{ $row['learners'] }}</td><td>{{ $row['reached'] }}</td></tr>
            @empty
                <tr><td colspan="3">{{ __('No current learners') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <section class="page-section" aria-labelledby="unreached-title">
        <h2 id="unreached-title">{{ __('Learners to follow up') }}</h2>
        @if ($report['unreached'] === [])
            <p>{{ __('Every current learner has a guardian the school can reach.') }}</p>
        @else
            <p>{{ __('Invite a guardian, or ask them to verify their email address or sign in to the parent app.') }}</p>
            <table class="table">
                <caption>{{ __('Learners without a reachable guardian') }}</caption>
                <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Homeroom') }}</th><th scope="col">{{ __('Reason') }}</th></tr></thead>
                <tbody>
                @foreach ($report['unreached'] as $row)
                    <tr><th scope="row">{{ $row['name'] }}</th><td>{{ $row['homeroom'] }}</td><td>{{ $reasons[$row['reason']] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
