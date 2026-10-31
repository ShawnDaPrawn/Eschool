@extends('layouts.master')
@section('title', __('Navigate Ed support access'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Navigate Ed support access')" :subtitle="__('Navigate Ed staff can open your school only when you approve, for a limited time and read-only. They never see wellbeing, counselling, health, safeguarding, discipline, early-warning or support-plan records. Every page they open is listed here.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="support-pending">
        <h2 id="support-pending">{{ __('Waiting for your decision') }}</h2>
        @if ($pending === [])
            <x-page.empty-state :title="__('No request is waiting')" :message="__('Navigate Ed has not asked for access.')" />
        @else
            @foreach ($pending as $grant)
                <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                    <h3>{{ __(':person (Navigate Ed :role) asks for :minutes minutes of read-only access', ['person' => $grant['requester'], 'role' => $grant['role'], 'minutes' => $grant['minutes']]) }}</h3>
                    <p>{{ __('Reason: :reason', ['reason' => $grant['reason']]) }}</p>
                    <p>{{ __('Asked :date', ['date' => $grant['requested_at']]) }}</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem">
                    <form method="POST" action="{{ route('support-access.approve', $grant['id']) }}" aria-label="{{ __('Approve') }}">
                        @csrf
                        <button class="m3-button m3-button--filled" type="submit">{{ __('Approve') }}</button>
                    </form>
                    <form method="POST" action="{{ route('support-access.decline', $grant['id']) }}" aria-label="{{ __('Decline') }}">
                        @csrf
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Decline') }}</button>
                    </form>
                    </div>
                </div></div>
            @endforeach
        @endif
    </section>

    <section class="page-section" aria-labelledby="support-open">
        <h2 id="support-open">{{ __('Open now') }}</h2>
        @if ($open === [])
            <x-page.empty-state :title="__('No support session is open')" :message="__('Nobody from Navigate Ed can see your school right now.')" />
        @else
            @foreach ($open as $grant)
                <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                    <h3>{{ __(':person (Navigate Ed :role) until :time', ['person' => $grant['requester'], 'role' => $grant['role'], 'time' => $grant['expires_at']]) }}</h3>
                    @if ($grant['emergency'])<p role="alert">{{ __('Opened by a Navigate Ed emergency override, without your approval.') }}</p>
                        <p>{{ __('Emergency reason: :reason', ['reason' => $grant['emergency_reason']]) }}</p>@endif
                    <p>{{ __('Reason: :reason', ['reason' => $grant['reason']]) }}</p>
                    <p><a href="{{ route('support-access.show', $grant['id']) }}">{{ trans_choice('{0} No pages viewed yet|{1} See the 1 page viewed|[2,*] See the :count pages viewed', $grant['views'], ['count' => $grant['views']]) }}</a></p>
                    <form method="POST" action="{{ route('support-access.end', $grant['id']) }}" aria-label="{{ __('End it now') }}">
                        @csrf
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('End it now') }}</button>
                    </form>
                </div></div>
            @endforeach
        @endif
    </section>

    <section class="page-section" aria-labelledby="support-history">
        <h2 id="support-history">{{ __('History') }}</h2>
        @if ($history === [])
            <x-page.empty-state :title="__('No history yet')" :message="__('Earlier requests and sessions will be listed here.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('History') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('History') }}</caption>
                <thead><tr><th scope="col">{{ __('Asked') }}</th><th scope="col">{{ __('Navigate Ed staff member') }}</th><th scope="col">{{ __('Outcome') }}</th><th scope="col">{{ __('Pages viewed') }}</th></tr></thead>
                <tbody>
                @foreach ($history as $grant)
                    <tr>
                        <td>{{ $grant['requested_at'] }}</td>
                        <th scope="row">{{ $grant['requester'] }}</th>
                        <td>{{ $grant['state_label'] }}@if ($grant['emergency']) · {{ __('Emergency override') }}: {{ $grant['emergency_reason'] }}@endif</td>
                        <td><a href="{{ route('support-access.show', $grant['id']) }}">{{ $grant['views'] }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
