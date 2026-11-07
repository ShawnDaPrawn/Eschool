@extends('layouts.master')
@section('title', __('School groups'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('School groups')" :subtitle="__('A school group lets one owner see figures across its schools. Your school joins a group only if you agree here, and you can leave at any time.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="groups-invited">
        <h2 id="groups-invited">{{ __('Waiting for your answer') }}</h2>
        @forelse ($invitations as $member)
            <x-m3.card :title="__('Join :group?', ['group' => $member['group']])" :subtitle="__('Invited :date', ['date' => $member['invited_at']])">
                @if ($member['note'])<p>{{ __('Note from Navigate Ed: :note', ['note' => $member['note']]) }}</p>@endif
                <p>{{ $member['agreement'] }}</p>
                <x-slot:footer>
                    <form method="POST" action="{{ route('school-groups.agree', $member['id']) }}" aria-label="{{ __('Agree and join') }}">
                        @csrf
                        <button class="m3-button m3-button--filled" type="submit">{{ __('Agree and join') }}</button>
                    </form>
                    <form method="POST" action="{{ route('school-groups.decline', $member['id']) }}" aria-label="{{ __('Decline') }}">
                        @csrf
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Decline') }}</button>
                    </form>
                </x-slot:footer>
            </x-m3.card>
        @empty
            <x-page.empty-state :title="__('No invitation is waiting')" :message="__('Navigate Ed has not invited your school to a group.')" />
        @endforelse
    </section>

    <section class="page-section" aria-labelledby="groups-current">
        <h2 id="groups-current">{{ __('Your group') }}</h2>
        @forelse ($current as $member)
            <x-m3.card :title="$member['group']" :subtitle="__('Joined :date', ['date' => $member['decided_at']])">
                <p>{{ $member['agreement'] }}</p>
                <p>{{ $member['billing'] === 'group' ? __('Navigate Ed bills your school through the group.') : __('Navigate Ed bills your school on its own.') }}</p>
                <x-slot:footer>
                    <form method="POST" action="{{ route('school-groups.leave', $member['id']) }}" aria-label="{{ __('Leave :group', ['group' => $member['group']]) }}">
                        @csrf
                        <x-page.field id="leave-reason-{{ $member['id'] }}" name="reason" :label="__('Reason (optional)')" maxlength="500" />
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Leave the group') }}</button>
                    </form>
                </x-slot:footer>
            </x-m3.card>
        @empty
            <x-page.empty-state :title="__('Your school is not in a group')" :message="__('No group sees anything about your school.')" />
        @endforelse
    </section>

    @if ($history !== [])
    <section class="page-section" aria-labelledby="groups-history">
        <h2 id="groups-history">{{ __('History') }}</h2>
        <div class="page-table__scroll" role="region" aria-label="{{ __('History') }}" tabindex="0"><table class="page-table__table">
            <caption class="m3-visually-hidden">{{ __('History') }}</caption>
            <thead><tr><th scope="col">{{ __('Group') }}</th><th scope="col">{{ __('Invited') }}</th><th scope="col">{{ __('Outcome') }}</th></tr></thead>
            <tbody>
            @foreach ($history as $member)
                <tr>
                    <th scope="row">{{ $member['group'] }}</th>
                    <td>{{ $member['invited_at'] }}</td>
                    <td>{{ ['declined' => __('Declined'), 'withdrawn' => __('Invitation withdrawn'), 'left' => __('Left the group'), 'removed' => __('Removed by Navigate Ed')][$member['status']] ?? $member['status'] }} {{ $member['ended_at'] ?? $member['decided_at'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </section>
    @endif
</div>
@endsection
