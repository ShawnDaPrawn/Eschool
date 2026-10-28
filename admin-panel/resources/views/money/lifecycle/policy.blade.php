@extends('layouts.master')
@section('title', __('Enrolment fee policy'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Enrolment fee policy')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Choose the school rule explicitly before billing learners who join or leave during the year. Existing invoices retain their original amounts and dates.') }}</p>
<p>{{ __('Daily charging counts inclusive enrolled days. Calendar-month charging includes every touched month. Each charge rounds once to exact cents; original payer shares and installments preserve that total.') }}</p>
@if(!$canWrite)<p>{{ __('Fee policies are read-only while fees are disabled or the current school year is unavailable.') }}</p>@endif
@if($year)
<h2>{{ $year->name }}</h2>
<form method="post" action="{{ route('fee-lifecycle.save') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<fieldset @disabled(!$canWrite)><legend>{{ __('School fee calculation rules') }}</legend>
<x-m3.select name="join_basis" :label="__('Mid-year join charging')" :options="['daily'=>__('Daily proportion'),'calendar_month'=>__('Calendar-month proportion'),'full_year'=>__('Full annual charge')]" :value="$policy?->join_basis" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="leaver_basis" :label="__('Known leaver charging')" :options="['daily'=>__('Daily proportion'),'calendar_month'=>__('Calendar-month proportion'),'no_credit'=>__('Charge through the end of the school year')]" :value="$policy?->leaver_basis" :placeholder="__('Choose explicitly')" required />
<x-m3.select name="installments" :label="__('Installments for a newly posted bill')" :options="['preserve_dates'=>__('Keep configured dates, including past due dates'),'remaining_dates'=>__('Redistribute over remaining dates or the reviewed due date')]" :value="$policy?->installments" :placeholder="__('Choose explicitly')" required />
<p>{{ __('The bill review shows the actual amount and dates before approval. If every configured installment date is past, redistribution uses the due date entered for that review.') }}</p>
<x-m3.button type="submit">{{ __('Save enrolment fee policy') }}</x-m3.button>
</fieldset></form>@endif
</div>
@endsection
