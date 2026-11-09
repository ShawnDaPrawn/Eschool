@extends('saas.layout')

@section('title', __('Ratings of shared items'))

@section('content')
<p><a href="{{ route('saas.library.reviews') }}">{{ __('Library reviews') }}</a></p>
<h1>{{ __('Ratings of shared items') }}</h1>
<p class="saas-lead">{{ __('Teachers\' ratings of questions schools shared, in your subjects. Ratings from the sharing school are kept but never counted. Teachers see an average only from :count counted ratings. No rating or comment shows who wrote it or their school.', ['count' => $threshold]) }}</p>

@forelse ($items as $item)
    <section class="saas-card" aria-label="{{ $item['title'] }}">
        <h2>#{{ $item['id'] }} {{ $item['title'] }}</h2>
        <p>{{ $item['topic'] }} · @if ($item['withdrawn']){{ __('Withdrawn by its school') }}@elseif ($item['published']){{ __('Published') }}@elseif ($item['unpublished']){{ __('Unpublished') }}@else{{ __('Not published') }}@endif</p>
        <p>{{ $item['count'] === 0 ? __('No counted ratings yet.') : __(':average out of 5 from :count counted ratings.', ['average' => number_format($item['average'], 1), 'count' => $item['count']]) }} @if ($item['own_school'] > 0){{ __(':count from the sharing school, not counted.', ['count' => $item['own_school']]) }}@endif</p>
        @foreach ($item['comments'] as $comment)<p>{{ str_repeat('★', $comment['stars']) }} {{ $comment['comment'] }}</p>@endforeach
        @if ($item['published'])
            <form method="POST" action="{{ route('saas.library.items.unpublish', $item['id']) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ $item['revision'] }}">
                <div class="saas-field">
                    <label for="unpublish-{{ $item['id'] }}">{{ __('Reason for taking it down') }}</label>
                    <textarea id="unpublish-{{ $item['id'] }}" name="reason" required minlength="10" maxlength="1000" rows="2"></textarea>
                </div>
                <div class="saas-actions"><button class="saas-btn saas-btn--danger" type="submit">{{ __('Unpublish') }}</button></div>
            </form>
        @endif
    </section>
@empty
    <p class="saas-card">{{ __('No shared item in your subjects has a rating yet.') }}</p>
@endforelse
@if ($lastPage > 1)
    <nav aria-label="{{ __('Rating pages') }}">
        @if ($page > 1)<a href="{{ route('saas.library.ratings', ['page' => $page - 1]) }}">{{ __('Previous') }}</a>@endif
        {{ __('Page :page of :last', ['page' => $page, 'last' => $lastPage]) }}
        @if ($page < $lastPage)<a href="{{ route('saas.library.ratings', ['page' => $page + 1]) }}">{{ __('Next') }}</a>@endif
    </nav>
@endif
@endsection
