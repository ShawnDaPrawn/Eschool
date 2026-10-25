@extends('layouts.master')
@section('title', __('Flagged learning content'))
@section('content')
<div class="content-wrapper m3 page-content" data-ai-private-review data-expires-at="{{ $detail['expires_at'] ?? '' }}">
    <x-page.header :title="__('Flagged learning content')" :subtitle="__('Safeguarding review of withheld learning interactions. A review never sends the withheld response to a learner.')" />
    @if($messages)<div role="alert">@foreach(\Illuminate\Support\Arr::flatten($messages) as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    @if($detail)
    <section class="page-section" aria-labelledby="ai-flag-detail">
        <h2 id="ai-flag-detail">{{ __('Review flag') }} {{ $detail['id'] }}</h2>
        <p>{{ $detail['code'] }} · {{ $detail['status'] }}</p>
        <p>{{ __('Learning tool') }}: {{ $detail['feature'] }}</p>
        @if($detail['prompt'])<p>{{ __('Prompt version') }}: {{ $detail['prompt'] }} {{ $detail['prompt_version'] }}</p>@endif
        @if(is_string($detail['content']['text'] ?? null))
            <div dir="auto" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $detail['content']['text'] }}</div>
        @elseif(isset($detail['content']['embedding_dimensions']))
            <p>{{ __('Flagged embedding dimensions') }}: {{ $detail['content']['embedding_dimensions'] }}</p>
        @else<p>{{ __('Only a category was retained for this older flag.') }}</p>@endif
        @foreach($detail['content']['media'] ?? [] as $media)<img src="{{ route('ai-review.media', ['flag' => $detail['id'], 'reference' => $media['id']]) }}" alt="{{ __('Private learning photograph') }}" style="max-width:100%;max-height:32rem" referrerpolicy="no-referrer">@endforeach
        <form method="POST" action="{{ route('ai-review.review', ['flag' => $detail['id']]) }}" aria-label="{{ __('Record safeguarding review') }}">
            @csrf
            <input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
            <input type="hidden" name="expected_account_id" value="{{ $accountId }}">
            <input type="hidden" name="operation_id" value="{{ $operationId }}">
            <input type="hidden" name="revision" value="{{ $detail['revision'] }}">
            <x-page.field name="decision" type="select" :label="__('Review decision')" :options="['reviewed' => __('Reviewed'), 'escalated' => __('Escalated for safeguarding follow-up')]" value="reviewed" :restore-old-input="false" required />
            <x-page.field name="reason" type="textarea" :label="__('Private review note')" :value="$reason" :restore-old-input="false" maxlength="2000" required />
            <button type="submit" class="m3-button m3-button--filled">{{ __('Record review') }}</button>
        </form>
        @foreach($detail['reviews'] as $review)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ __('Review') }} {{ $review['revision'] }} · {{ $review['decision'] }}</h3><p dir="auto">{{ $review['reason'] }}</p><p>{{ $review['at'] }}</p></div></article>
        @endforeach
    </section>
    @endif
    <section class="page-section" aria-labelledby="ai-flag-list"><h2 id="ai-flag-list">{{ __('Current flags') }}</h2>
        @forelse($listing['items'] as $item)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3><a href="{{ route('ai-review.show', ['flag' => $item['id']]) }}">{{ __('Review flag') }} {{ $item['id'] }}</a></h3><p>{{ $item['code'] }} · {{ $item['status'] }}</p></div></article>
        @empty<p>{{ __('No current flags need review.') }}</p>@endforelse
        @if($listing['page'] > 1)<a href="{{ route('ai-review.index', ['page' => $listing['page'] - 1]) }}">{{ __('Previous page') }}</a>@endif
        @if($listing['page'] < $listing['last_page'])<a href="{{ route('ai-review.index', ['page' => $listing['page'] + 1]) }}">{{ __('Next page') }}</a>@endif
    </section>
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/ai-flag-review.js') }}" defer></script>@endsection
