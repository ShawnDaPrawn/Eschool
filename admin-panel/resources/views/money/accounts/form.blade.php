@extends('layouts.master')
@section('title', __('Family accounts'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$account?__('Review family account'):__('New family account')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <x-m3.button variant="text" :href="route('fee-accounts.index')">{{ __('Back to family accounts') }}</x-m3.button>
    <form method="get">
        <x-m3.select name="year_id" :label="__('School year for payer assignments')" :options="$years->pluck('name','id')" :value="$year?->id" required />
        <x-m3.button type="submit" variant="outlined">{{ __('Load this school year') }}</x-m3.button>
    </form>
    @if(!$canWrite)<p>{{ __('Set up a school year and enable fees before changing family accounts.') }}</p>@endif
    <form method="post" action="{{ $account?route('fee-accounts.save',['account'=>$account->id]):route('fee-accounts.create') }}" data-year-safe data-family-account-form data-holder-error="{{ __('Choose a guardian and a positive share for every holder. Shares must total 100%.') }}">
        @include('money.fee-plans.hidden',['revision'=>$account?->revision??0])
        <input type="hidden" name="session_year_id" value="{{ $year?->id }}">
        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('Account details') }}</legend>
            <x-m3.text-field name="name" :label="__('Account name')" :value="$account?->name" maxlength="160" required />
            <x-m3.text-field name="currency" :label="__('Currency code')" :value="$account?->currency??\App\Services\Platform\Ledger\Money::defaultCurrency()" maxlength="3" pattern="[A-Z]{3}" :readonly="$account?->ledger_account_id!==null" required />
            <p>{{ __('Learners and shares below apply to the selected school year. Posted invoices keep their original details.') }}</p>
            <x-m3.text-field name="learner_search" type="search" :label="__('Find a learner')" data-billing-learner-search autocomplete="off" />
            <x-m3.select name="learner_ids[]" :label="__('Learners on this account')" :options="$learners" :value="$selected" multiple size="6" id="billing-learners" :required="$account===null" :hint="__('Choose all learners who share these guardians and payer shares.')" />
            @if($account)<p>{{ __('An existing account may be left empty to release unbilled learners. Posted charges keep their payer account.') }}</p>@endif
            <p>{{ __('Each guardian must currently be allowed to see fees for every selected learner.') }}</p>
            <div id="billing-holders" data-next-index="{{ max(2,$holders->count()) }}">
                @for($i=0;$i<max(2,$holders->count());$i++)
                    @include('money.accounts.holder',['index'=>$i,'number'=>$i+1,'holder'=>$holders->get($i)])
                @endfor
            </div>
            <x-m3.button type="button" variant="outlined" data-add-holder>{{ __('Add account holder') }}</x-m3.button>
            <p>{{ __('Shares must total exactly 100%. Saving activates this family account.') }}</p>
            <x-m3.button type="submit">{{ __('Save active family account') }}</x-m3.button>
        </fieldset>
    </form>
    <template id="billing-holder-template">@include('money.accounts.holder',['index'=>'__index__','number'=>'__number__','holder'=>null])</template>

@if($account && $year && count($selected)>0)
<section aria-labelledby="payment-options-title"><h2 id="payment-options-title">{{ __('Learner payment options') }}</h2>
<p>{{ __('Choose a payment option before tuition is posted. The matched plan and reviewed bill preview must agree. Posted invoices keep their original schedule.') }}</p>
@foreach($selected as $learnerId)
@php($choice=$paymentChoices->get($learnerId))
@php($posted=in_array($learnerId,$postedLearners,true))
<form method="post" action="{{ route('fee-accounts.payment-choice',['account'=>$account->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$choice?->revision??0])
<input type="hidden" name="learner_id" value="{{ $learnerId }}"><input type="hidden" name="session_year_id" value="{{ $year->id }}">
<fieldset @disabled(!$canWrite || $posted)><legend>{{ $learners->get($learnerId) }}</legend>
<x-m3.select name="fee_plan_id" :label="__('Fee plan for payment choice')" :options="$paymentPlans->pluck('name','id')" :value="$choice?->fee_plan_id" required />
@php($keys=[])
@foreach($paymentPlans as $paymentPlan)
@foreach($paymentPlan->payment_options??\App\Services\Money\FeePaymentSchedules::defaults() as $option)
@php($keys[$option['key']]=$option['label'])
@endforeach
@endforeach
<x-m3.select name="option_key" :label="__('Payment option')" :options="$keys" :value="$choice?->option_key" required />
<x-m3.button type="submit">{{ __('Save learner payment option') }}</x-m3.button>
</fieldset>
@if($posted)<p>{{ __('Tuition has been posted; its payment schedule is retained.') }}</p>@endif
</form>
@endforeach
</section>
@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/family-billing.js') }}?v={{ filemtime(public_path('assets/js/custom/family-billing.js')) }}" defer></script>@endsection
