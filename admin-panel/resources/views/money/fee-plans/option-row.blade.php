<section class="m3-card m3-card--outlined fee-plan-card" data-payment-option data-option-index="{{ $index }}"><div class="m3-card__content">
<div class="m3-grid m3-gap-4">
<x-m3.text-field :id="'fee-option-code-'.$index" :name="'option_rows['.$index.'][key]'" :label="__('Option code')" :hint="__('A permanent code such as annual, monthly or terms.')" :value="$option['key']??''" maxlength="32" class="m3-col-12 m3-col-medium-6" />
<x-m3.text-field :name="'option_rows['.$index.'][label]'" :label="__('Option name')" :value="$option['label']??''" maxlength="100" class="m3-col-12 m3-col-medium-6" />
</div>
<x-m3.radio name="payment_default_index" :value="$index" :checked="$isDefault" :label="__('Use this as the default payment option')" />
<div class="page-table__scroll" role="region" aria-label="{{ __('Installment schedule') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Installment schedule') }}</caption><thead><tr><th scope="col">{{ __('Month') }}</th><th scope="col">{{ __('Day') }}</th><th scope="col">{{ __('Percentage') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead>
<tbody data-installment-rows data-next-index="{{ max(1,count($option['installments']??[])) }}">
@for($j=0;$j<max(1,count($option['installments']??[]));$j++)
@include('money.fee-plans.installment-row',['optionIndex'=>$index,'index'=>$j,'part'=>$option['installments'][$j]??null])
@endfor
</tbody></table></div>
<div class="money-actions"><x-m3.button type="button" variant="outlined" data-add-installment>{{ __('Add installment') }}</x-m3.button><x-m3.button type="button" variant="text" data-remove-option>{{ __('Remove payment option') }}</x-m3.button></div>
<template data-installment-template>@include('money.fee-plans.installment-row',['optionIndex'=>$index,'index'=>'__row__','part'=>null])</template>
</div></section>
