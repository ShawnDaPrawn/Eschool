@extends('saas.layout')

@section('title', __('Product modules and packages'))

@use('App\Services\Platform\Saas\PlatformPackages')
@section('content')
@php
    $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'packages.manage');
    $byKey = collect($packageRows)->keyBy('key');
@endphp
<h1>{{ __('Product modules and packages') }}</h1>
<p class="saas-lead">{{ __('What NavigateEd sells: ten product modules, each a bundle of the switches a school sees, put together into packages with a monthly price per learner. Only verified (level 4) or production-grade (level 5) modules are offered to paying schools.') }}</p>

<h2>{{ __('Packages') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Packages') }}" tabindex="0">
    <table class="saas-table">
        <caption>{{ __('Packages, their status, schools and price today (per learner per month, excluding VAT)') }}</caption>
        <thead>
        <tr>
            <th scope="col">{{ __('Package') }}</th>
            <th scope="col">{{ __('Status') }}</th>
            <th scope="col" class="num">{{ __('Trial days') }}</th>
            <th scope="col" class="num">{{ __('Schools') }}</th>
            <th scope="col" class="num">{{ __('Per learner per month') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($packageRows as $row)
            <tr>
                <th scope="row"><a href="{{ route('saas.packages.show', $row['id']) }}">{{ $row['name'] }}</a> <small>({{ $row['key'] }})</small>
                    @if ($row['description'])<br><small>{{ $row['description'] }}</small>@endif</th>
                <td><span class="saas-badge {{ $row['status'] === 'active' ? '' : 'saas-badge--warn' }}">{{ __(PlatformPackages::STATUSES[$row['status']]) }}</span></td>
                <td class="num">{{ $row['trial_days'] }}</td>
                <td class="num">{{ $row['schools'] }}</td>
                <td class="num">{{ ($row['quote']['net_cents'] ?? null) === null ? __('Not priced yet') : PlatformPackages::rands($row['quote']['net_cents']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if ($canManage)
<h2>{{ __('New package') }}</h2>
<form method="POST" action="{{ route('saas.packages.store') }}" class="saas-card" aria-label="{{ __('New package') }}">
    @csrf
    <p>{{ __('A new package starts as a draft: no school can be put on it until it is activated. Choose its modules and prices on its page.') }}</p>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="package-key">{{ __('Key (lower case letters, digits and dashes; cannot change later)') }}</label>
            <input id="package-key" name="package_key" required pattern="[a-z][a-z0-9\-]{1,31}" maxlength="32" value="{{ old('package_key') }}">
        </div>
        <div class="saas-field">
            <label for="package-name">{{ __('Name') }}</label>
            <input id="package-name" name="name" required minlength="2" maxlength="120" value="{{ old('name') }}">
        </div>
        <div class="saas-field">
            <label for="package-trial">{{ __('Trial days') }}</label>
            <input id="package-trial" name="trial_days" type="number" required min="0" max="{{ PlatformPackages::MAX_TRIAL_DAYS }}" value="{{ old('trial_days', 0) }}">
        </div>
        <div class="saas-field">
            <label for="package-description">{{ __('Description') }}</label>
            <textarea id="package-description" name="description" maxlength="1000" rows="2">{{ old('description') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Create the draft') }}</button></div>
</form>
@endif

<h2>{{ __('What each package includes') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('What each package includes') }}" tabindex="0">
    <table class="saas-table">
        <caption>{{ __('Product modules, readiness and the packages that include them') }}</caption>
        <thead>
        <tr>
            <th scope="col">{{ __('Product module') }}</th>
            <th scope="col">{{ __('Readiness') }}</th>
            @foreach ($packages as $package)<th scope="col">{{ $package }}@if (($byKey[$package]['status'] ?? 'active') !== 'active') <small>({{ __(PlatformPackages::STATUSES[$byKey[$package]['status']]) }})</small>@endif</th>@endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($products as $product)
            <tr>
                <th scope="row">{{ __($product['label']) }}@if ($product['always_included']) <span class="saas-badge">{{ __('Always included') }}</span>@endif
                    <br><small>{{ __($product['description']) }}</small></th>
                <td><span class="saas-badge {{ $product['sellable'] ? '' : 'saas-badge--warn' }}">{{ __('Level :level', ['level' => $product['readiness']]) }}: {{ __($product['readiness_label']) }}</span></td>
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
