@extends('layouts.master')
@section('title', __('Discounts and bursaries'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Discounts and bursaries')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Discount history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-discounts.index')">{{ __('Back to discounts') }}</x-m3.button>
<p>{{ __('Saving a rule creates a new draft. Review and approve its exact current settings before applying it. Existing posted reductions never change.') }}</p>
@if($year)
<form method="post" action="{{ $rule ? route('fee-discounts.save',['rule'=>$rule->id]) : route('fee-discounts.create') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$rule?->revision??0])
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('Discount rule details') }}</legend>
<x-m3.text-field name="name" :label="__('Private Finance rule name')" :value="$rule?->name" maxlength="160" required />
<x-m3.select name="kind" :label="__('Discount type')" :options="['sibling'=>__('Sibling discount'),'staff'=>__('Staff discount'),'early_settlement'=>__('Early settlement discount'),'bursary'=>__('Bursary'),'exemption'=>__('Exemption')]" :value="$rule?->kind" :placeholder="__('Choose a discount type')" required />
<x-m3.select name="learner_id" :label="__('Named learner')" :options="$learners" :value="$rule?->learner_id" :placeholder="__('All qualifying learners')" />
<p>{{ __('Bursaries and exemptions require a named learner. Other rules can target a named learner or all qualifying learners.') }}</p>
<x-m3.select name="source_kind" :label="__('Charge source')" :options="['annual_tuition'=>__('Annual tuition'),'fee_item'=>__('Fee item')]" :value="$rule?->source_kind" :placeholder="__('Choose a charge source')" required />
<x-m3.select name="fee_plan_id" :label="__('Tuition plan filter')" :options="$plans->pluck('name','id')" :value="$rule?->fee_plan_id" :placeholder="__('All tuition plans')" />
<x-m3.select name="fee_item_id" :label="__('Fee item')" :options="$items->pluck('name','id')" :value="$rule?->fee_item_id" :placeholder="__('No item for tuition rules')" />
<x-m3.text-field name="currency" :label="__('Currency')" :value="$rule?->currency" pattern="[A-Z]{3}" maxlength="3" required />
<x-m3.text-field name="starts_on" :label="__('Valid from')" type="date" :value="$rule?->starts_on" required />
<x-m3.text-field name="ends_on" :label="__('Valid until')" type="date" :value="$rule?->ends_on" required />
<x-m3.select name="amount_kind" :label="__('Reduction calculation')" :options="['percent'=>__('Percentage'),'fixed'=>__('Fixed amount')]" :value="$rule?->amount_kind" :placeholder="__('Choose a calculation')" required />
<x-m3.text-field name="discount_value" :label="__('Percentage or fixed money amount')" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" :value="$rule?->amount_value ? \App\Services\Platform\Ledger\Money::ofCents($rule->amount_value,$rule->currency)->toDecimalString() : null" required />
<p>{{ __('Enter 10 for ten percent or a fixed money amount of 10.00. Exemptions use Percentage and 100. The final cents and original tax are reviewed before posting.') }}</p>
<x-m3.text-field name="sibling_position" :label="__('Qualifying child position for sibling rules')" type="number" min="2" max="100" :value="$rule?->sibling_position" />
<p>{{ __('Sibling order uses current enrolled learners in this family, ordered by their learner record number. Position 2 qualifies the second and later children.') }}</p>
<x-m3.text-field name="minimum_paid_percent" :label="__('Minimum percentage paid for early settlement')" inputmode="decimal" :value="$rule?->minimum_paid_bp ? \App\Services\Platform\Ledger\Money::ofCents($rule->minimum_paid_bp)->toDecimalString() : null" />
<x-m3.text-field name="payment_deadline" :label="__('Early settlement receipt deadline')" type="date" :value="$rule?->payment_deadline" />
<x-m3.select name="staff_roles[]" :label="__('Eligible staff roles for staff rules')" :options="$staffRoles" :value="$rule?->staff_roles??[]" multiple />
<p>{{ __('Only actual allocated receipts received by this deadline qualify. Staff rules require a current guardian in a selected school role with fee visibility.') }}</p>
<x-m3.select name="overlap" :label="__('Other discounts on the same charges')" :options="['exclusive'=>__('Refuse another discount'),'remaining'=>__('Apply to the remaining charge')]" :value="$rule?->overlap" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="independent_approval" :label="__('Separate Finance approval')" :options="['0'=>__('Same Finance operator may approve'),'1'=>__('Another Finance operator must approve')]" :value="$rule ? ($rule->independent_approval?'1':'0') : null" :placeholder="__('Choose explicitly')" required />
<x-m3.button type="submit">{{ __('Save discount draft') }}</x-m3.button>
</fieldset></form>
@if($rule && $canWrite)
<h2>{{ __('Current rule approval') }}</h2><p>{{ $rule->state }} · {{ __('Revision') }} {{ $rule->revision }}</p>
@if($rule->state==='draft' || $rule->state==='active')
<form method="post" action="{{ route('fee-discounts.transition',['rule'=>$rule->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$rule->revision])
<x-m3.select name="action" :label="__('Rule action')" :options="$rule->state==='draft' ? ['approve'=>__('Approve this rule'),'archive'=>__('Archive this rule')] : ['archive'=>__('Archive this rule')]" required />
<x-m3.checkbox name="confirmed" value="1" :label="__('I reviewed this exact rule and approve the selected action.')" required />
<x-m3.button type="submit">{{ __('Confirm rule action') }}</x-m3.button></form>
@endif @endif
@else<p>{{ __('Configure a current school year before creating discount rules.') }}</p>@endif
</div>
@endsection
