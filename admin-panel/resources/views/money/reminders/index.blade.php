@extends('layouts.master')
@section('title', __('Fee reminder schedule'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Fee reminder schedule')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-reporting.index')">{{ __('Arrears and accounting') }}</x-m3.button>
<p>{{ __('Reminders ask each eligible parent to open their private fee statement. They contain no balances, learner names or other holder details.') }}</p>
<p>{{ __('Only that parent’s visible overdue shares qualify. Paying or crediting the eligible debt stops queued delivery. Parent notification choices remain in effect.') }}</p>
@if(!$canWrite)<p>{{ __('Reminder settings are read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ route('fee-reminders.save') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$policy?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('School reminder schedule') }}</legend>
<input type="hidden" name="enabled" value="0"><x-m3.checkbox name="enabled" value="1" :checked="$policy?->enabled??false" :label="__('Enable scheduled fee reminders')" />
<x-m3.text-field type="number" name="min_days_overdue" :label="__('Minimum days overdue')" min="1" max="365" :value="$policy?->min_days_overdue??7" required />
<x-m3.text-field type="number" name="cadence_days" :label="__('Minimum days between family reminders')" min="1" max="365" :value="$policy?->cadence_days??7" required />
<x-m3.text-field type="number" name="local_hour" :label="__('Earliest sending hour in school time')" min="0" max="23" :value="$policy?->local_hour??9" required />
<x-m3.button type="submit">{{ __('Save reminder schedule') }}</x-m3.button>
</fieldset></form>
<h2>{{ __('Queued statement prompts') }}</h2>
<p>{{ __('A queued prompt is a delivery intent. Notification channel rules, preferences and current access determine delivery.') }}</p>
@if($reminders->isEmpty())<x-page.empty-state :title="__('No fee reminders queued')" :message="__('Enable a schedule to queue private prompts when eligible shares become overdue.')" />@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Fee reminder history') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Fee reminder history') }}</caption><thead><tr><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('School date') }}</th><th scope="col">{{ __('Schedule revision') }}</th><th scope="col">{{ __('Expires at UTC') }}</th></tr></thead><tbody>
@foreach($reminders as $reminder)<tr><td><a href="{{ route('fee-payments.statement',['account'=>$reminder->fee_account_id]) }}">{{ $reminder->account_name }}</a></td><td>{{ $reminder->issued_on->format('Y-m-d') }}</td><td>{{ $reminder->policy_revision }}</td><td>{{ $reminder->expires_at->format('Y-m-d H:i') }}</td></tr>@endforeach
</tbody></table></div></div>@include('money.billing.pagination',['paginator'=>$reminders])@endif
</div>
@endsection
