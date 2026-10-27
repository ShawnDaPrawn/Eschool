@extends('layouts.master')
@section('title', __('Fee plans'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="$plan?__('Review fee plan'):__('New fee plan')" />
    <x-m3.button variant="text" :href="route('fee-plans.index', $plan?['year_id'=>$plan->session_year_id]:[])">{{ __('Back to fee plans') }}</x-m3.button>
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @php
        $targets = ['all:0'=>__('All learners')];
        foreach($grades as $grade) $targets['grade:'.$grade->id] = __('Grade').': '.$grade->name;
        foreach($classes as $class) $targets['class:'.$class->id] = __('Class').': '.$class->name;
        if($plan && !isset($targets[$plan->scope.':'.($plan->target_id??0)])) $targets[$plan->scope.':'.$plan->target_id] = __('Unavailable target');
    @endphp
    @if(!$canWrite)<p>{{ __('Fee plans are read-only while fees are disabled.') }}</p>@endif
    <form method="post" action="{{ $plan?route('fee-plans.save',['plan'=>$plan->id]):route('fee-plans.create') }}" class="m3-card m3-card--outlined fee-plan-card"><div class="m3-card__content">
        @include('money.fee-plans.hidden', ['revision'=>$plan?->revision??0])
        <fieldset @disabled(!$canWrite)>
            <x-m3.text-field name="name" :label="__('Plan name')" :value="$plan?->name" maxlength="160" required />
            <x-m3.select name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')" :value="$plan?->session_year_id" required />
            <x-m3.text-field name="amount" :label="__('Annual amount')" :hint="__('Use a decimal point, for example 12500.00.')" inputmode="decimal" :value="$plan?\App\Services\Platform\Ledger\Money::ofCents($plan->amount_cents,$plan->currency)->toDecimalString():'0.00'" required />
            <x-m3.text-field name="currency" :label="__('Currency code')" maxlength="3" pattern="[A-Z]{3}" :value="$plan?->currency??\App\Services\Platform\Ledger\Money::defaultCurrency()" required />
            <x-m3.select name="scope_choice" :label="__('Applies to')" :options="$targets" :value="($plan?->scope??'all').':'.($plan?->target_id??0)" required />
            <x-m3.select name="state" :label="__('Status')" :options="['draft'=>__('Draft'),'active'=>__('Active'),'archived'=>__('Archived')]" :value="$plan?->state??'draft'" required />
            <p>{{ __('Only active plans match the preview. Overlapping plans are reported for review.') }}</p>
            <x-m3.button type="submit">{{ __('Save fee plan') }}</x-m3.button>
        </fieldset>
    </div></form>
    @if($plan && $canWrite)<form method="post" action="{{ route('fee-plans.copy',['plan'=>$plan->id]) }}" class="m3-card m3-card--outlined fee-plan-card"><div class="m3-card__content">
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
