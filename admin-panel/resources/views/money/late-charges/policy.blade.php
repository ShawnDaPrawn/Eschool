@extends('layouts.master')
@section('title', __('School late-charge rule'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench late-charge-workbench">
<x-page.header :title="__('School late-charge rule')" :subtitle="__('Late charges start disabled. Set an explicit gross flat amount or rate, cap, grace period, due days and VAT treatment for one original learner debt share per payer.')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($readOnly)<p role="status">{{ __('Late-charge rules are read-only while fees are paused.') }}</p>@endif
<form method="get" action="{{ route('fee-late-charges.policy') }}">
<x-page.field id="late-select-year" type="select" name="year_id" :restore-old-input="false" :label="__('School year')" :options="$years->pluck('name','id')->all()" :value="$year?->id" required />
<x-page.field id="late-select-currency" name="currency" :restore-old-input="false" :label="__('Currency')" :value="$currency" pattern="[A-Z]{3}" maxlength="3" required />
<button type="submit" class="btn btn-outline-primary">{{ __('Open this school-year currency rule') }}</button>
</form>
@if($year!==null)
<form method="post" action="{{ route('fee-late-charges.save-policy') }}" autocomplete="off">
@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="operation_id" value="{{ $operation }}">
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<input type="hidden" name="revision" value="{{ $policy?->revision??0 }}">
<fieldset @disabled($readOnly)><legend>{{ __('Explicit one-off school rule') }}</legend>
<x-page.field id="late-currency" name="currency" :restore-old-input="false" :label="__('Rule currency')" :value="$currency" pattern="[A-Z]{3}" maxlength="3" required />
<label for="late-enabled"><input id="late-enabled" name="enabled" type="checkbox" value="1" @checked($policy?->enabled)> {{ __('Enable this explicitly configured late-charge rule') }}</label>
<x-page.field id="late-scope" name="scope" type="select" :restore-old-input="false" :label="__('Original charges covered')" :value="$policy?->scope" :options="[''=>__('Choose an explicit scope'),'annual_only'=>__('Original annual tuition only'),'all_original_charges'=>__('All original charges, excluding late fees')]" />
<x-page.field id="late-basis" name="basis" type="select" :restore-old-input="false" :label="__('Gross charge basis')" :value="$policy?->basis" :options="[''=>__('Choose an explicit basis'),'flat'=>__('Flat gross amount'),'rate'=>__('Rate on overdue debt after allocations')]" />
<x-page.field id="late-flat" name="flat_amount" :restore-old-input="false" :label="__('Flat gross amount, if selected')" :value="$decimal($policy?->flat_cents)" inputmode="decimal" />
<x-page.field id="late-rate" name="rate_percent" :restore-old-input="false" :label="__('Rate percentage, if selected')" :value="$decimal($policy?->rate_bp)" inputmode="decimal" />
<x-page.field id="late-cap" name="maximum_gross" :restore-old-input="false" :label="__('Maximum gross charge including VAT')" :value="$decimal($policy?->cap_cents)" inputmode="decimal" />
<x-page.field id="late-grace" name="grace_days" type="number" :restore-old-input="false" :label="__('Grace days after original due date')" :value="$policy?->grace_days" min="0" max="3650" />
<x-page.field id="late-due" name="due_days" type="number" :restore-old-input="false" :label="__('Days until the new late invoice is due')" :value="$policy?->due_days" min="0" max="3650" />
<x-page.field id="late-tax" name="tax_treatment" type="select" :restore-old-input="false" :label="__('Explicit VAT treatment')" :value="$policy?->tax_treatment" :options="[''=>__('Choose explicit tax treatment'),'no_vat'=>__('No VAT charged'),'vat_exempt'=>__('VAT exempt'),'vat_zero'=>__('VAT zero-rated'),'vat_inclusive'=>__('Gross late charge includes VAT')]" />
<x-page.field id="late-vat-rate" name="vat_rate" :restore-old-input="false" :label="__('VAT rate percentage; zero for untaxed or zero-rated charges')" :value="$decimal($policy?->vat_rate_bp)" inputmode="decimal" />
<p>{{ __('Leave the unused flat amount or rate empty. VAT registrations must be configured before taxable charging. Review separate held credit before posting; this rule does not allocate receipts.') }}</p>
<x-page.field id="late-policy-reason" name="private_reason" type="textarea" :restore-old-input="false" :label="__('Private reason for this school rule revision')" minlength="10" maxlength="2000" required />
<button type="submit" class="btn btn-primary">{{ __('Retain this late-charge rule revision') }}</button>
</fieldset>
</form>
@endif
<h2>{{ __('Retained rule revisions') }}</h2>
<ul>
@foreach($revisions as $row)
<li>{{ __('Revision') }} {{ $row->revision }} · {{ $row->created_at }} · {{ $row->private_reason }}</li>
@endforeach
</ul>
{{ $revisions->links() }}
<p><a href="{{ route('fee-late-charges.index') }}">{{ __('Review original overdue family debt') }}</a></p>
</div>
@endsection
