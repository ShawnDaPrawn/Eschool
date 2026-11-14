@extends('layouts.master')
@section('title', __('Fee plans'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$plan?__('Review fee plan'):__('New fee plan')" />
@php
$section=request('section','details');
if(!in_array($section,['details','copy'],true)) $section='details';
$tabs=[];
$tabs['details']=['label'=>__('Plan configuration'),'url'=>request()->fullUrlWithQuery(['section'=>'details','page'=>null])];
if($plan && $canWrite) $tabs['copy']=['label'=>__('Copy to another year'),'url'=>request()->fullUrlWithQuery(['section'=>'copy','page'=>null])];
@endphp
@include('money.billing.tabs')

    <x-m3.button variant="text" :href="route('fee-plans.index', $plan?['year_id'=>$plan->session_year_id]:[])">{{ __('Back to fee plans') }}</x-m3.button>
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @php
        $targets = ['all:0'=>__('All learners')];
        foreach($grades as $grade) $targets['grade:'.$grade->id] = __('Grade').': '.$grade->name;
        foreach($classes as $class) $targets['class:'.$class->id] = __('Class').': '.$class->name;
        if($plan && !isset($targets[$plan->scope.':'.($plan->target_id??0)])) $targets[$plan->scope.':'.$plan->target_id] = __('Unavailable target');
    @endphp
    @if(!$canWrite)<p>{{ __('Fee plans are read-only while fees are disabled.') }}</p>@endif
@if($section==='details')
    <form method="post" action="{{ $plan?route('fee-plans.save',['plan'=>$plan->id]):route('fee-plans.create') }}" class="m3-card m3-card--outlined fee-plan-card"><div class="m3-card__content">
        @include('money.fee-plans.hidden', ['revision'=>$plan?->revision??0])
        <fieldset @disabled(!$canWrite)>
            <x-m3.text-field name="name" :label="__('Plan name')" :value="$plan?->name" maxlength="160" required />
            <x-m3.select name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')" :value="$plan?->session_year_id" required />
            <x-m3.text-field name="amount" :label="__('Annual amount')" :hint="__('Use a decimal point, for example 12500.00.')" inputmode="decimal" :value="$plan?\App\Services\Platform\Ledger\Money::ofCents($plan->amount_cents,$plan->currency)->toDecimalString():'0.00'" required />
            <x-m3.text-field name="currency" :label="__('Currency code')" maxlength="3" pattern="[A-Z]{3}" :value="$plan?->currency??\App\Services\Platform\Ledger\Money::defaultCurrency()" required />
            <x-m3.select name="scope_choice" :label="__('Applies to')" :options="$targets" :value="($plan?->scope??'all').':'.($plan?->target_id??0)" required />

            <section class="workspace-section" data-fee-rules><input type="hidden" name="rules_present" value="1">
            <h2>{{ __('Additional matching rules') }}</h2>
            <p>{{ __('Every rule must match. Each rule accepts any listed value. Hidden or sensitive learner fields are unavailable.') }}</p>
            @php($ruleOptions=[''=>__('No additional rule'),'phase'=>__('Grade phase')])
            @foreach($ruleFields as $field)@php($ruleOptions['profile:'.$field->id]=$field->label)@endforeach
            <div class="page-table__scroll" role="region" aria-label="{{ __('Fee-plan matching rules') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Fee-plan matching rules') }}</caption><thead><tr><th scope="col">{{ __('Eligible field') }}</th><th scope="col">{{ __('Matching values') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody data-rule-rows data-next-index="{{ max(1,count($plan?->applies_to??[])) }}">
            @for($i=0;$i<max(1,count($plan?->applies_to??[]));$i++)
                @include('money.fee-plans.rule-row',['index'=>$i,'condition'=>($plan?->applies_to??[])[$i]??null])
            @endfor
            </tbody></table></div>
            <x-m3.button type="button" variant="outlined" data-add-rule>{{ __('Add matching rule') }}</x-m3.button>
            <template data-rule-template>@include('money.fee-plans.rule-row',['index'=>'__row__','condition'=>null])</template>
            </section>
            <section class="workspace-section" data-payment-options><input type="hidden" name="options_present" value="1">
            <h2>{{ __('Payment schedules') }}</h2>
            <p>{{ __('Choose a default payment option. Each installment has a month, day and percentage. A schedule must total 100%; an empty schedule uses the bill-run due date.') }}</p>
            @php($paymentOptions=$plan?->payment_options??\App\Services\Money\FeePaymentSchedules::defaults())
            <div data-option-rows data-next-index="{{ count($paymentOptions) }}">
            @foreach($paymentOptions as $i=>$option)
                @include('money.fee-plans.option-row',['index'=>$i,'option'=>$option,'isDefault'=>($plan?->default_payment_option??$paymentOptions[0]['key'])===$option['key']])
            @endforeach
            </div>
            <x-m3.button type="button" variant="outlined" data-add-option>{{ __('Add payment option') }}</x-m3.button>
            <template data-option-template>@include('money.fee-plans.option-row',['index'=>'__option__','option'=>null,'isDefault'=>false])</template>
            </section>
            <x-m3.select name="state" :label="__('Status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'archived'=>__('Archived')]" :value="$plan?->state??'draft'" required />
            <p>{{ __('Only active plans match the preview. Overlapping plans are reported for review.') }}</p>
            <x-m3.button type="submit">{{ __('Save fee plan') }}</x-m3.button>
        </fieldset>
    </div></form>

@endif
    @if($section==='copy' && $plan && $canWrite)<form method="post" action="{{ route('fee-plans.copy',['plan'=>$plan->id]) }}" class="m3-card m3-card--outlined fee-plan-card"><div class="m3-card__content">
        <h2 class="m3-title-large">{{ __('Copy to a new school year') }}</h2>
        @include('money.fee-plans.hidden', ['revision'=>$plan->revision])
        <x-m3.text-field name="name" :label="__('Copied plan name')" :value="$plan->name" maxlength="160" required />
        <x-m3.select name="session_year_id" :label="__('Target school year')" :options="$years->where('id','!=',$plan->session_year_id)->pluck('name','id')" required />
        <x-m3.text-field name="uplift_percent" :label="__('Uplift percentage')" :hint="__('From 0 to 100, with up to two decimal places.')" value="0" inputmode="decimal" required />
        <p>{{ __('The copy is a draft. Review the amount and scope before activating it.') }}</p>
        <x-m3.button type="submit">{{ __('Create draft copy') }}</x-m3.button>
    </div></form>@endif
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/custom/fee-plan-editor.js') }}?v={{ filemtime(public_path('assets/js/custom/fee-plan-editor.js')) }}" defer></script>@endsection
