@extends('layouts.master')
@section('title', __('Alumni group bookings'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Alumni group bookings')" :subtitle="__('Arrange career talks or group job shadows with current consent, valid vetting and school supervision.')" />
    @include('people.pool.navigation')
    <section class="m3-card m3-card--outlined workspace-section"><div class="m3-card__content"><h2>{{ __('Book a group session') }}</h2>
        <form method="POST" action="{{ route('people-pool-panel.book') }}" class="panel-form-grid">@csrf
            <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
            <x-page.field name="pool_member_id" type="select" :label="__('Alumnus or volunteer')" :options="collect($members['items'])->filter(fn ($member) => $member['bookable_today'])->pluck('name', 'id')->all()" :placeholder="__('Choose an eligible member')" required />
            <x-page.field name="activity_session_id" type="select" :label="__('Group session')" :options="$sessions->mapWithKeys(fn ($session) => [$session->id => ($groups->get($session->activity_group_id)?->name ?? '').' / '.$session->name.' / '.$session->on_date->toDateString()])->all()" :placeholder="__('Choose a session')" required />
            <x-page.field name="kind" type="select" :label="__('Contribution')" :options="['career_talk' => __('Career talk'), 'job_shadow' => __('Group job shadow')]" required />
            <p>{{ __('The school checks the member’s offering, consent, clearance on the session date, the current learner count and the allocated school supervisor before saving. One-to-one bookings are refused.') }}</p>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Book group session') }}</button>
        </form>
        <nav class="panel-actions" aria-label="{{ __('Member choices') }}">@if($members['page'] > 1)<a href="{{ request()->fullUrlWithQuery(['member_page' => $members['page'] - 1]) }}">{{ __('Previous members') }}</a>@endif @if($members['page'] < $members['last_page'])<a href="{{ request()->fullUrlWithQuery(['member_page' => $members['page'] + 1]) }}">{{ __('More members') }}</a>@endif</nav>
        <details class="workspace-section"><summary>{{ __('Session readiness') }}</summary>
            <div class="panel-table-scroll"><table class="page-table__table"><caption>{{ __('Current learner numbers and supervision for available sessions') }}</caption><thead><tr><th scope="col">{{ __('Session') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('School supervision') }}</th><th scope="col">{{ __('Readiness') }}</th></tr></thead><tbody>
                @forelse($sessions as $session)@php($eligibility = $sessionEligibility[$session->id])<tr><th scope="row">{{ $session->name }} · {{ $session->on_date->toDateString() }}</th><td>{{ $eligibility['learners'] }}</td><td>{{ $eligibility['supervised'] ? __('Allocated') : __('Required') }}</td><td>{{ $eligibility['ready'] ? __('Group ready; member clearance still required') : __('Review learner numbers or supervision') }}</td></tr>@empty<tr><td colspan="4">{{ __('No future sessions available in the selected school year.') }}</td></tr>@endforelse
            </tbody></table></div>
        </details>
        {{ $sessions->links() }}
    </div></section>
    <section class="workspace-section"><h2>{{ __('Existing bookings') }}</h2><div class="panel-record-list">
    @forelse($listing['items'] as $booking)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $booking['pool_member_name'] }}</h3><p>{{ $booking['group_name'] }} · {{ $booking['session_name'] }}</p><p>{{ $booking['on_date'] }} {{ $booking['starts_at'] }} · {{ $booking['venue'] }}</p><x-m3.chip :status="$booking['status'] === 'booked' ? 'success' : 'neutral'">{{ __(ucfirst($booking['status'])) }}</x-m3.chip>
        @if($booking['status'] === 'booked')<form method="POST" action="{{ route('people-pool-panel.cancel', $booking['id']) }}">@csrf<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="revision" value="{{ $booking['revision'] }}"><button type="submit" class="m3-button m3-button--outlined" data-confirm="{{ __('Cancel this group booking? Its history will be retained.') }}">{{ __('Cancel booking') }}</button></form>@endif
        </div></article>
    @empty<x-page.empty-state :title="__('No group bookings yet')" :message="__('Choose a vetted member and a supervised group session to arrange the first booking.')" />@endforelse
    </div>{{ $bookingRecords->links() }}</section>
</div>
@endsection
