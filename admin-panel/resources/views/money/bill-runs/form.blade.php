@extends('layouts.master')
@section('title', __('Bill runs'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('New bill preview')" />
    <x-m3.button variant="text" :href="route('fee-bill-runs.index')">{{ __('Back to bill runs') }}</x-m3.button>
    <p>{{ __('This annual tuition run uses dated enrolments and explicit family payer assignments. It creates no invoices until approved and posted.') }}</p>
    <form method="post" action="{{ route('fee-bill-runs.prepare') }}" class="m3-card m3-card--outlined fee-plan-card" data-year-safe><div class="m3-card__content">
        @include('money.fee-plans.hidden',['revision'=>0])
        <fieldset @disabled(!$canWrite)>
            <x-m3.select name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')" required />
            <x-m3.text-field name="as_of" type="date" :label="__('Enrolment date for this bill run')" :value="now()->toDateString()" required />
            <x-m3.text-field name="due_on" type="date" :label="__('Invoice due date')" :value="now()->addDays(30)->toDateString()" required />
            <x-m3.checkbox name="tax_treatment" value="no_vat" :label="__('I confirm no VAT is charged on these annual tuition fees.')" required />
            <p>{{ __('Missing payers, overlapping plans and unresolved pro-rata or leaver rules appear as exceptions. Previously billed annual tuition is excluded.') }}</p>
            <x-m3.button type="submit">{{ __('Prepare bill preview') }}</x-m3.button>
        </fieldset>
    </div></form>
</div>
@endsection
