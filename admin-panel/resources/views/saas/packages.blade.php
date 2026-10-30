@extends('saas.layout')

@section('title', __('Product modules and packages'))

@section('content')
<h1>{{ __('Product modules and packages') }}</h1>
<p class="saas-lead">{{ __('What NavigateEd sells: ten product modules, each a bundle of the switches a school sees, with its readiness and monthly price per learner. Only verified (level 4) or production-grade (level 5) modules are offered to paying schools.') }}</p>
<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('Product modules, readiness, price and the packages that include them') }}</caption>
        <thead>
        <tr>
            <th scope="col">{{ __('Product module') }}</th>
            <th scope="col">{{ __('Readiness') }}</th>
            <th scope="col" class="num">{{ __('Per learner per month') }}</th>
            @foreach ($packages as $package)<th scope="col">{{ $package }}</th>@endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($products as $product)
            <tr>
                <th scope="row">{{ __($product['label']) }}@if ($product['always_included']) <span class="saas-badge">{{ __('Always included') }}</span>@endif
                    <br><small>{{ __($product['description']) }}</small></th>
                <td><span class="saas-badge {{ $product['sellable'] ? '' : 'saas-badge--warn' }}">{{ __('Level :level', ['level' => $product['readiness']]) }}: {{ __($product['readiness_label']) }}</span></td>
                <td class="num">{{ $product['price_cents'] === null ? __('Not priced yet') : 'R '.number_format(intdiv($product['price_cents'], 100)).'.'.str_pad((string) ($product['price_cents'] % 100), 2, '0', STR_PAD_LEFT) }}</td>
                @foreach ($packages as $package)
                    <td>{{ ['all' => __('Included'), 'some' => __('Partly'), 'none' => '—'][$product['packages'][$package]] }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<h2>{{ __('What each product module contains') }}</h2>
<div class="saas-card">
    <dl>
        @foreach ($products as $product)
            <dt>{{ __($product['label']) }}</dt>
            <dd>{{ implode(', ', array_map(fn ($name) => __($name), $product['modules'])) }}</dd>
        @endforeach
    </dl>
</div>
@endsection
