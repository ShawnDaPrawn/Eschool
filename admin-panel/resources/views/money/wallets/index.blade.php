@extends('layouts.master')
@section('title', __('Wallets'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')<div class="content-wrapper m3 fee-plan-workbench"><x-page.header :title="__('Wallets')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Wallet history is read-only while wallets are disabled.') }}</p>@endif
<p>{{ __('Personal wallets are separate from fee accounts. Opening a wallet creates no funds.') }}</p><h2>{{ __('Child wallet returns') }}</h2>
<p>{{ __('Parents can move their own funds to an approved child. Returning a child balance requires an explicit school decision, including the effect on separated families.') }}</p>
<form method="post" action="{{ route('wallet-finance.return-policy') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>$transferPolicy?->revision??0])
<fieldset @disabled(!$canWrite||!$canConfigureReturns)><legend>{{ __('Choose the child return policy') }}</legend>
<x-m3.select name="child_returns" :label="__('Child return rule')" :options="['disabled'=>__('Do not allow child wallet returns'),'current_approving_guardian'=>__('Allow any current approving guardian to return funds to their own wallet')]" :value="$transferPolicy?->child_returns" :placeholder="__('Choose explicitly')" required />
<label><input type="checkbox" name="confirmed" value="1" required> {{ __('I understand that enabling returns lets any currently approving guardian return available child funds, regardless of who funded them.') }}</label>
<x-m3.button type="submit">{{ __('Save child return policy') }}</x-m3.button></fieldset></form>
<h2>{{ __('School wallet spending rules') }}</h2>
<form method="post" action="{{ route('wallet-finance.save') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>$settings?->revision??0])<fieldset @disabled(!$canWrite)><legend>{{ __('Explicit wallet currency and spending controls') }}</legend>
<x-m3.text-field name="currency" :label="__('Wallet currency code')" :value="$settings?->currency" maxlength="3" pattern="[A-Z]{3}" :readonly="$issued" required />
<x-m3.text-field name="daily_spend_amount" :label="__('Daily wallet spending cap')" :value="$settings ? \App\Services\Platform\Ledger\Money::ofCents($settings->daily_spend_cents,$settings->currency)->toDecimalString() : null" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-m3.select name="child_limits_required" :label="__('Require guardian controls before learner spending')" :options="['1'=>__('Yes'),'0'=>__('Use the school cap until a guardian sets controls')]" :value="$settings ? ($settings->child_limits_required?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<p>{{ __('A zero daily cap stops purchases. It does not change existing money or fee access.') }}</p><x-m3.button type="submit">{{ __('Save wallet spending rules') }}</x-m3.button></fieldset></form>
<h2>{{ __('Personal wallet balances') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Personal wallet balances') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Personal wallet balances') }}</caption><thead><tr><th scope="col">{{ __('Wallet owner') }}</th><th scope="col">{{ __('Balance') }}</th><th scope="col">{{ __('Effective daily spending cap') }}</th></tr></thead><tbody>
@foreach($wallets as $wallet)<tr><td><a href="{{ route('wallet-finance.show',['wallet'=>$wallet->id]) }}">{{ $summaries[$wallet->id]['owner_name'] }}</a></td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($summaries[$wallet->id]['balance_cents'],$wallet->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($summaries[$wallet->id]['limits']['effective_daily_spend_cents'],$wallet->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>{{ $wallets->links() }}</div>@endsection
