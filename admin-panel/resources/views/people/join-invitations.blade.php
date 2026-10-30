@extends('layouts.master')
@section('title', __('Invitations to join'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Invitations to join')" :subtitle="__('Some people you add as staff already use NavigateEd, for example at another school. They are emailed an invitation and appear on the staff list once they accept it. Invitations that are still waiting are listed here.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="join-waiting">
        <h2 id="join-waiting">{{ __('Waiting for an answer') }}</h2>
        @if ($invitations->isEmpty())
            <x-page.empty-state :title="__('No invitations are waiting')" :message="__('Invitations that were accepted, declined, withdrawn or have expired are not listed.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('Waiting for an answer') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Waiting for an answer') }}</caption>
                <thead><tr><th scope="col">{{ __('Name') }}</th><th scope="col">{{ __('Email') }}</th><th scope="col">{{ __('Role') }}</th><th scope="col">{{ __('Expires') }}</th><th scope="col"><span class="m3-visually-hidden">{{ __('Actions') }}</span></th></tr></thead>
                <tbody>
                @foreach ($invitations as $invitation)
                    <tr>
                        <th scope="row">{{ $invitation['name'] }}</th>
                        <td>{{ $invitation['email'] }}</td>
                        <td>{{ $invitation['role'] }}</td>
                        <td>{{ $invitation['expires']->timezone(schoolSetting('time_zone', 'Africa/Johannesburg'))->format('j M Y') }}</td>
                        <td>
                            @if ($invitation['may_withdraw'])
                                <form method="POST" action="{{ route('join-invitations.withdraw', $invitation['id']) }}">
                                    @csrf
                                    <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Withdraw the invitation for :name', ['name' => $invitation['name']]) }}">{{ __('Withdraw') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
