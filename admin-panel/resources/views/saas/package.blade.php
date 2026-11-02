@extends('saas.layout')

@section('title', $package['name'].' · '.__('Packages'))

@use('App\Services\Platform\Saas\PlatformPackages')
@section('content')
@php
    $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'packages.manage');
    $products = (array) config('product_modules');
    $label = fn (string $product): string => __($products[$product]['label'] ?? $product);
    $retired = $package['status'] === 'retired';
    $today = PlatformPackages::today();
    $states = ['in_force' => __('In force'), 'scheduled' => __('Not started'), 'ended' => __('Ended'), 'withdrawn' => __('Withdrawn')];
@endphp
<p><a href="{{ route('saas.packages') }}">{{ __('All packages') }}</a></p>
<h1>{{ $package['name'] }} <small>({{ $package['key'] }})</small></h1>
<p class="saas-lead">
    <span class="saas-badge {{ $package['status'] === 'active' ? '' : 'saas-badge--warn' }}">{{ __(PlatformPackages::STATUSES[$package['status']]) }}</span>
    · {{ trans_choice('{0} No school on it|{1} One school on it|[2,*] :count schools on it', $package['schools']) }}
    · {{ __('Trial: :days days', ['days' => $package['trial_days']]) }}
    · {{ __('Modules version :version', ['version' => $package['version']]) }}
</p>
@if ($package['description'])<p>{{ $package['description'] }}</p>@endif
<nav aria-label="{{ __('Package sections') }}" class="saas-actions">
    <a href="#modules">{{ __('Modules') }}</a><a href="#prices">{{ __('Prices') }}</a><a href="#price-on">{{ __('Price on a date') }}</a>@if ($canManage)<a href="#details">{{ __('Details and status') }}</a>@endif
</nav>

<h2 id="modules">{{ __('Modules') }}</h2>
@if ($package['schools_behind'] > 0)
<div class="saas-card" role="region" aria-label="{{ __('Schools on an earlier version') }}">
    <p>{{ trans_choice('{1} One school is on an earlier version of these modules and keeps what it has until the package is re-applied.|[2,*] :count schools are on an earlier version of these modules and keep what they have until the package is re-applied.', $package['schools_behind']) }}</p>
    @if ($canManage)
    <form method="POST" action="{{ route('saas.packages.reapply', $package['id']) }}" aria-label="{{ __('Re-apply the package') }}">
        @csrf
        <label for="reapply-reason">{{ __('Reason (recorded in the audit log)') }}</label>
        <input id="reapply-reason" name="reason" required minlength="5" maxlength="500">
        <button class="saas-btn" type="submit">{{ __('Re-apply to these schools') }}</button>
    </form>
    @endif
</div>
@endif
@if ($canManage && ! $retired)
<form method="POST" action="{{ route('saas.packages.modules', $package['id']) }}" class="saas-card" aria-label="{{ __('Modules in this package') }}">
    @csrf
    @method('PUT')
    <p>{{ $package['status'] === 'draft'
        ? __('While the package is a draft, its modules change in place.')
        : __('Saving a change adds a new version. Schools already on the package keep their version until it is re-applied; schools put on it from now get the new one.') }}</p>
    <fieldset>
        <legend>{{ __('Product modules') }}</legend>
        @foreach ($products as $key => $product)
            <div>
                @if ($product['always_included'] ?? false)
                    <input type="hidden" name="product_modules[]" value="{{ $key }}">
                    <input id="product-{{ $key }}" type="checkbox" checked disabled>
                @else
                    <input id="product-{{ $key }}" type="checkbox" name="product_modules[]" value="{{ $key }}" @checked(in_array($key, $package['product_modules'], true))>
                @endif
                <label for="product-{{ $key }}">{{ __($product['label']) }}@if ($product['always_included'] ?? false) ({{ __('always included') }})@endif</label>
            </div>
        @endforeach
    </fieldset>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save modules') }}</button></div>
</form>
@else
<p class="saas-card">{{ implode(', ', array_map($label, $package['product_modules'])) }}</p>
@endif
<details class="saas-card">
    <summary>{{ __('Module history') }}</summary>
    <ul>
        @foreach ($versions as $version)
            <li>{{ __('Version :version', ['version' => $version['version']]) }} ({{ $version['created_at'] }}): {{ implode(', ', array_map($label, $version['product_modules'])) }}</li>
        @endforeach
    </ul>
</details>

<h2 id="prices">{{ __('Prices') }}</h2>
<p>{{ __('Per learner per month, in rands, excluding VAT. A price in force or past is never changed: add a new price from a date and the open-ended one ends the day before.') }}</p>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Price history') }}" tabindex="0">
    <table class="saas-table">
        <caption>{{ __('Price history, newest first') }}</caption>
        <thead><tr><th scope="col">{{ __('Kind') }}</th><th scope="col" class="num">{{ __('Amount') }}</th><th scope="col">{{ __('From') }}</th><th scope="col">{{ __('To') }}</th><th scope="col">{{ __('State') }}</th><th scope="col">{{ __('Note') }}</th>@if ($canManage)<th scope="col">{{ __('Withdraw') }}</th>@endif</tr></thead>
        <tbody>
        @forelse ($prices as $price)
            <tr>
                <th scope="row">{{ __(PlatformPackages::KINDS[$price['kind']]) }}@if ($price['product_module']): {{ $label($price['product_module']) }}@endif</th>
                <td class="num">{{ $price['kind'] === 'discount' ? '− ' : '' }}{{ PlatformPackages::rands($price['amount_cents']) }}</td>
                <td>{{ $price['effective_from'] }}</td>
                <td>{{ $price['effective_to'] ?? __('No end date') }}</td>
                <td>{{ $states[$price['state']] }}</td>
                <td>{{ $price['note'] }}</td>
                @if ($canManage)
                <td>
                    @if ($price['state'] === 'scheduled')
                    <form method="POST" action="{{ route('saas.packages.prices.withdraw', [$package['id'], $price['id']]) }}" aria-label="{{ __('Withdraw this price') }}">
                        @csrf
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Withdraw') }}</button>
                    </form>
                    @endif
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $canManage ? 7 : 6 }}">{{ __('Not priced yet') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if ($canManage)
<form method="POST" action="{{ route('saas.packages.prices.store', $package['id']) }}" class="saas-card" aria-label="{{ __('Add a price') }}">
    @csrf
    <h3>{{ __('Add a price') }}</h3>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="price-kind">{{ __('Kind') }}</label>
            <select id="price-kind" name="kind" required>
                @foreach (PlatformPackages::KINDS as $value => $text)<option value="{{ $value }}" @selected(old('kind') === $value)>{{ __($text) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="price-module">{{ __('Product module (add-on prices only)') }}</label>
            <select id="price-module" name="product_module">
                <option value="">—</option>
                @foreach (PlatformPackages::addOnModules() as $key)<option value="{{ $key }}" @selected(old('product_module') === $key)>{{ $label($key) }}</option>@endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="price-amount">{{ __('Rands per learner per month, excluding VAT') }}</label>
            <input id="price-amount" name="amount" required inputmode="decimal" pattern="\d{1,6}(\.\d{1,2})?" value="{{ old('amount') }}">
        </div>
        <div class="saas-field">
            <label for="price-from">{{ __('From') }}</label>
            <input id="price-from" name="effective_from" type="date" required min="{{ $today }}" value="{{ old('effective_from', $today) }}">
        </div>
        <div class="saas-field">
            <label for="price-to">{{ __('To (optional)') }}</label>
            <input id="price-to" name="effective_to" type="date" min="{{ $today }}" value="{{ old('effective_to') }}">
        </div>
        <div class="saas-field">
            <label for="price-note">{{ __('Note (optional)') }}</label>
            <input id="price-note" name="note" maxlength="191" value="{{ old('note') }}">
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Add the price') }}</button></div>
</form>
@endif

<h2 id="price-on">{{ __('Price on a date') }}</h2>
<form method="GET" action="{{ route('saas.packages.show', $package['id']) }}#price-on" class="saas-card" aria-label="{{ __('Price on a date') }}">
    <div class="saas-actions">
        <div class="saas-field">
            <label for="price-on-date">{{ __('Date') }}</label>
            <input id="price-on-date" name="on" type="date" value="{{ $on }}">
        </div>
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Show') }}</button>
    </div>
    <dl>
        <dt>{{ __('Base price') }}</dt><dd>{{ $quote['base_cents'] === null ? __('Not priced yet') : PlatformPackages::rands($quote['base_cents']) }}</dd>
        <dt>{{ __('Discount') }}</dt><dd>{{ $quote['discount_cents'] === 0 ? '—' : '− '.PlatformPackages::rands($quote['discount_cents']) }}</dd>
        <dt>{{ __('Package per learner per month') }}</dt><dd><strong>{{ $quote['net_cents'] === null ? __('Not priced yet') : PlatformPackages::rands($quote['net_cents']) }}</strong> {{ __('excluding VAT') }}</dd>
        @foreach ($quote['modules'] as $module => $cents)
            <dt>{{ __('Add-on: :module', ['module' => $label($module)]) }}</dt><dd>{{ PlatformPackages::rands($cents) }}</dd>
        @endforeach
    </dl>
</form>

@if ($canManage)
<h2 id="details">{{ __('Details and status') }}</h2>
@if (! $retired)
<form method="POST" action="{{ route('saas.packages.update', $package['id']) }}" class="saas-card" aria-label="{{ __('Package details') }}">
    @csrf
    @method('PUT')
    <div class="saas-grid">
        <div class="saas-field">
            <label for="detail-name">{{ __('Name') }}</label>
            <input id="detail-name" name="name" required minlength="2" maxlength="120" value="{{ old('name', $package['name']) }}">
        </div>
        <div class="saas-field">
            <label for="detail-trial">{{ __('Trial days') }}</label>
            <input id="detail-trial" name="trial_days" type="number" required min="0" max="{{ PlatformPackages::MAX_TRIAL_DAYS }}" value="{{ old('trial_days', $package['trial_days']) }}">
        </div>
        <div class="saas-field">
            <label for="detail-description">{{ __('Description') }}</label>
            <textarea id="detail-description" name="description" maxlength="1000" rows="2">{{ old('description', $package['description']) }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Save details') }}</button></div>
</form>
<form method="POST" action="{{ route('saas.packages.status', $package['id']) }}" class="saas-card" aria-label="{{ __('Package status') }}">
    @csrf
    @method('PUT')
    @if ($package['status'] === 'draft')
        <input type="hidden" name="status" value="active">
        <p>{{ __('Activating lets platform staff put schools on this package. Its modules then change only by adding a version.') }}</p>
        <button class="saas-btn" type="submit">{{ __('Activate') }}</button>
    @else
        <input type="hidden" name="status" value="retired">
        <p>{{ __('Retiring stops new schools being put on this package. Schools already on it keep it, and its prices stay.') }}</p>
        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Retire') }}</button>
    @endif
</form>
@else
<p class="saas-card">{{ __('This package is retired: it is kept as history and no school can be put on it.') }}</p>
@endif
@endif
@endsection
