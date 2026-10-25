@extends('layouts.master')
@section('title', __('AI settings and caps'))
@section('content')
<div class="content-wrapper m3 page-content" data-ai-private-review>
    <x-page.header :title="__('AI settings and caps')" :subtitle="__('School limits reduce the platform allowance. These settings never activate an unapproved provider.')" />
    @if($messages)<div role="alert">@foreach(\Illuminate\Support\Arr::flatten($messages) as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    <p>{{ __('Billing currency') }}: {{ config('ai.billing_currency') }}</p>
    <form method="POST" action="{{ route('ai-controls.save') }}" aria-label="{{ __('School AI limits') }}">
        @csrf
        <input type="hidden" name="expected_school_id" value="{{ $schoolId }}"><input type="hidden" name="expected_account_id" value="{{ $accountId }}">
        <input type="hidden" name="operation_id" value="{{ $operationId }}"><input type="hidden" name="revision" value="{{ $settings['revision'] }}">
        <x-page.field name="paused" type="select" :label="__('Learning inference')" :options="[0 => __('Follow platform availability'), 1 => __('Paused by this school')]" :value="(int) $settings['paused']" :restore-old-input="false" />
        <p>{{ __('Leave a limit empty to follow the platform ceiling. Zero blocks new usage in that allowance. Paid inference budgets are quoted in the platform billing currency; local transcription has separate seconds limits.') }}</p>
        @foreach(['school' => __('School'), 'actor' => __('Person'), 'learner' => __('Learner')] as $scope => $label)
            @foreach(['tokens' => __('Daily tokens'), 'cents' => __('Daily billing cents')] as $unit => $unitLabel)
                @php($key = $scope.'_daily_'.$unit)
                <x-page.field :name="'limits['.$key.']'" type="number" :label="$label.' · '.$unitLabel" :value="$settings['limits'][$key] ?? ''" :restore-old-input="false" min="0" max="100000000" step="1" />
            @endforeach
        @endforeach
        <button type="submit" class="m3-button m3-button--filled">{{ __('Save AI limits') }}</button>
    </form>
    <section class="page-section"><h2>{{ __('Platform availability') }}</h2>
        @forelse($settings['features'] as $feature)
        <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $feature['name'] }}</h3><p>{{ $feature['enabled_by_platform'] ? __('Enabled by platform configuration') : __('Disabled by platform configuration') }}</p><p>{{ $feature['qualified'] ? __('Provider qualification recorded') : __('Awaiting provider qualification') }}</p></div></article>
        @empty<p>{{ __('No inference feature has been configured for activation.') }}</p>@endforelse
    </section>
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/ai-flag-review.js') }}" defer></script>@endsection
