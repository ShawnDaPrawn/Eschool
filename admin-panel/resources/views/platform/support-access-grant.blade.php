@extends('layouts.master')
@section('title', __('Navigate Ed support access'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Pages viewed by :person', ['person' => $grant['requester']])" :subtitle="__('Each page a Navigate Ed staff member opened in this support session: when, which page and which record. What the page showed is never stored.')" />
    <p><a href="{{ route('support-access.index') }}">{{ __('Navigate Ed support access') }}</a></p>

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="support-grant"><div class="m3-card__content">
        <h2 id="support-grant">{{ $grant['state_label'] }}@if ($grant['emergency']) · {{ __('Emergency override') }}@endif</h2>
        <p>{{ __('Reason: :reason', ['reason' => $grant['reason']]) }}</p>
        @if ($grant['emergency_reason'])<p>{{ __('Emergency reason: :reason', ['reason' => $grant['emergency_reason']]) }}</p>@endif
        <p>{{ __('Asked :date', ['date' => $grant['requested_at']]) }}</p>
        @if ($grant['starts_at'])<p>{{ __('Open from :from to :to', ['from' => $grant['starts_at'], 'to' => $grant['expires_at']]) }}</p>@endif
        @if ($grant['decided_by'])<p>{{ __('Decided by :person', ['person' => $grant['decided_by']]) }}</p>@endif
        @if ($grant['ended_at'])<p>{{ __('Ended early :date by :person', ['date' => $grant['ended_at'], 'person' => $grant['ended_by']]) }}</p>@endif
    </div></section>

    <section class="page-section" aria-labelledby="support-views">
        <h2 id="support-views">{{ __('Pages viewed') }}</h2>
        @if ($views->isEmpty())
            <x-page.empty-state :title="__('No page has been viewed')" :message="__('Pages opened in this session will be listed here.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('Pages viewed') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Pages viewed') }}</caption>
                <thead><tr><th scope="col">{{ __('When') }}</th><th scope="col">{{ __('Page') }}</th><th scope="col">{{ __('Record') }}</th></tr></thead>
                <tbody>
                @foreach ($views as $view)
                    <tr><td>{{ $view['at'] }}</td><th scope="row">{{ $view['page'] }}</th>
                        <td>@if ($view['listed'] !== null){{ trans_choice('{0} No accounts listed|{1} 1 account listed|[2,*] :count accounts listed', count($view['listed']), ['count' => count($view['listed'])]) }}@if ($view['listed'] !== []) (#{{ implode(', #', $view['listed']) }})@endif @else{{ $view['record'] ?? '—' }}@endif</td></tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $views->links() }}
        @endif
    </section>
</div>
@endsection
