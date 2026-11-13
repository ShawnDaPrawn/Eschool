@extends('layouts.master')
@section('title', __('Request facilitator leave'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('Request facilitator leave')" :subtitle="$actor->full_name.' · '.$year->name"><x-slot:actions><a href="{{ route('facilitator-leave.index') }}" class="m3-button m3-button--text">{{ __('Back to requests') }}</a></x-slot:actions></x-page.header>
@if(!$canWrite)<p role="status">{{ __('This school year is read-only. Select the current school year to make changes.') }}</p>@endif
<form action="{{ route('facilitator-leave.create') }}" method="GET" class="page-filters">
<x-page.field name="from" type="date" :label="__('From date')" :value="$from" required /><x-page.field name="to" type="date" :label="__('To date')" :value="$to" required /><button class="m3-button m3-button--outlined" type="submit">{{ __('Review affected sessions') }}</button>
</form>
<fieldset @disabled(!$canWrite || $sessions->isEmpty())>
<x-page.form-card id="facilitator-request" :action="route('facilitator-leave.store')" :title="__('Request details')" :submit-label="__('Submit facilitator request')" :draft="false">
<input type="hidden" name="operation_id" value="{{ $operationId }}"><input type="hidden" name="session_year_id" value="{{ $year->id }}"><input type="hidden" name="from_date" value="{{ $from }}"><input type="hidden" name="to_date" value="{{ $to }}">
<p>{{ __('First half covers time before 12:00; second half covers time from 12:00. An untimed activity session is affected by either half.') }}</p>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('Leave portions and affected sessions') }}"><table class="page-table__table"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Leave portion') }}</th><th>{{ __('Affected sessions') }}</th></tr></thead><tbody>
@foreach($portions as $date=>$portion)<tr><th scope="row">{{ $date }}</th><td><x-page.field :id="'portion-'.$date" :name="'portions['.$date.']'" type="select" :label="__('Leave portion').' '.$date" :value="$portion" :options="['full'=>__('Full day'),'first_half'=>__('First half'),'second_half'=>__('Second half')]" required /></td><td>@forelse($sessions->filter(fn($session)=>$session->on_date->toDateString()===$date) as $session)<p>{{ $groups->get($session->activity_group_id)?->name }} · {{ $session->name }} · {{ $session->starts_at ? substr($session->starts_at,0,5) : __('Time not configured') }}</p>@empty{{ __('No allocated session on this date') }}@endforelse</td></tr>@endforeach
</tbody></table></div>
@if($sessions->isEmpty())<p role="status">{{ __('No dated facilitator activity sessions are affected. Review your dates and group appointments.') }}</p>@endif
<x-page.field name="reason" type="textarea" :label="__('Private reason')" maxlength="2000" required />
<x-page.field name="files[]" type="file" :label="__('Supporting attachments')" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.txt" />
<p>{{ __('Up to five files, 10 MB each. The original request and attachments are visible only to you and authorised reviewers. Submission remains pending; session cover is assigned separately after approval.') }}</p>
</x-page.form-card>
</fieldset>
</div>
@endsection
