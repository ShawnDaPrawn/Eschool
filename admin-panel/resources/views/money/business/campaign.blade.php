@extends('layouts.master')
@section('title', __('Giving campaign'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench">
<x-page.header :title="$record['name']" :subtitle="__($record['state']).' · '.$record['currency']" />
<x-m3.button variant="text" :href="route('giving-panel.index')">{{ __('Back to campaigns') }}</x-m3.button>
@php
$labels=['overview'=>__('Overview')];
if($canDonors) $labels['donors']=__('Donors and pledges');
if($canWrite && $record['state']==='open' && $canDonors) $labels+=['pledge'=>__('Record pledge'),'donation'=>__('Record verified receipt')];
if($canWrite && $record['state']==='open' && $canManage) $labels['close']=__('Close campaign');
$tabs=[];foreach($labels as $key=>$label) $tabs[$key]=['label'=>$label,'url'=>route('giving-panel.show',['campaign'=>$record['id'],'section'=>$key])];
@endphp
@include('money.billing.tabs')
<div class="workspace-layout"><div class="workspace-main">
@if($section==='overview')
<section class="workspace-section"><h2>{{ __('Campaign overview') }}</h2><p>{{ $record['description'] }}</p><dl>
<dt>{{ __('Campaign dates') }}</dt><dd>{{ $record['starts_on'] }} – {{ $record['ends_on'] }}</dd><dt>{{ __('Received and posted') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($record['raised_cents'],$record['currency'])->format() }}</dd><dt>{{ __('Pledged') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($record['pledged_cents'],$record['currency'])->format() }}</dd><dt>{{ __('Goal') }}</dt><dd>{{ $record['goal_cents']===null?'—':\App\Services\Platform\Ledger\Money::ofCents($record['goal_cents'],$record['currency'])->format() }}</dd><dt>{{ __('Totals visibility') }}</dt><dd>{{ $record['public_totals']?__('School community'):__('Finance team only') }}</dd></dl></section>
@elseif($section==='donors')
<section class="workspace-section"><h2>{{ __('Verified donation receipts') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Donation receipts') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Donation receipts') }}</caption><thead><tr><th scope="col">{{ __('Receipt') }}</th><th scope="col">{{ __('Donor') }}</th><th scope="col">{{ __('Amount') }}</th><th scope="col">{{ __('Received on') }}</th></tr></thead><tbody>
@forelse($donors['donations'] as $row)<tr><td><a href="{{ route('giving-panel.receipt',['donation'=>$row['id']]) }}">{{ $row['receipt'] }}</a></td><td>{{ $row['donor_name'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$row['currency'])->format() }}</td><td>{{ $row['received_on'] }}</td></tr>@empty<tr><td colspan="4">{{ __('No verified receipts yet.') }}</td></tr>@endforelse
</tbody></table></div><h2>{{ __('Pledges') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Pledges') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Pledges') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Donor') }}</th><th scope="col">{{ __('Promised') }}</th><th scope="col">{{ __('Received against pledge') }}</th></tr></thead><tbody>
@forelse($donors['pledges'] as $row)<tr><td>{{ $row['reference'] }}</td><td>{{ $row['donor_name'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['amount_cents'],$row['currency'])->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['received_cents'],$row['currency'])->format() }}</td></tr>@empty<tr><td colspan="4">{{ __('No pledges recorded.') }}</td></tr>@endforelse
</tbody></table></div></section>
@elseif(in_array($section,['pledge','donation'],true) && $canWrite && $record['state']==='open')
<x-page.form-card id="giving-receipt" :action="route($section==='pledge'?'giving-panel.pledge':'giving-panel.donate',['campaign'=>$record['id']])" :title="$section==='pledge'?__('Record a pledge'):__('Record verified bank receipt')" :submit-label="$section==='pledge'?__('Save pledge acknowledgement'):__('Post verified donation once')" :draft="false" :cancel-href="route('giving-panel.show',['campaign'=>$record['id']])">
@include('money.business.hidden')
<x-page.field name="donor_name" :label="__('Donor name')" minlength="2" maxlength="160" required />
<x-page.field name="donor_email" type="email" :label="__('Donor email address')" maxlength="191" />
<x-page.field name="amount" :label="__('Amount').' ('.$record['currency'].')'" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" required />
<x-page.field :name="$section==='pledge'?'pledged_on':'received_on'" type="date" :label="$section==='pledge'?__('Pledged on'):__('Received on')" required />
@if($section==='donation')
<input type="hidden" name="method" value="eft">
<x-page.field name="source_reference" :label="__('Unique bank transaction reference')" maxlength="96" required />
<x-page.field name="pledge_id" type="select" :label="__('Related pledge')" :options="collect($donors['pledges'])->mapWithKeys(fn($p)=>[$p['id']=>$p['reference'].' · '.$p['donor_name']])->all()" :placeholder="__('No linked pledge')" />
<x-m3.checkbox name="confirmed" value="1" :label="__('I verified this EFT in the school bank account and checked that it has not already been recorded.')" required />
@else<p>{{ __('This acknowledgement records a promise. It does not create a receipt or post funds.') }}</p>@endif
</x-page.form-card>
@elseif($section==='close' && $canWrite && $record['state']==='open')
<x-page.form-card id="giving-close" :action="route('giving-panel.close',['campaign'=>$record['id']])" :title="__('Close campaign')" :submit-label="__('Close this campaign')" :draft="false" :cancel-href="route('giving-panel.show',['campaign'=>$record['id']])">
@include('money.business.hidden')<input type="hidden" name="revision" value="{{ $record['revision'] }}"><p>{{ __('Closing stops further pledges and receipts. Original donor, receipt and ledger history remains available.') }}</p>
</x-page.form-card>
@else<x-page.empty-state :title="__('This action is no longer available')" :message="__('Review the campaign state before continuing.')" />@endif
</div><aside class="workspace-context"><h2>{{ __('Campaign context') }}</h2><dl><dt>{{ __('Campaign') }}</dt><dd>{{ $record['name'] }}</dd><dt>{{ __('Currency') }}</dt><dd>{{ $record['currency'] }}</dd><dt>{{ __('State') }}</dt><dd>{{ __($record['state']) }}</dd></dl><p>{{ __('Pledges are separate from received funds. Donor identities are private to authorised Finance staff.') }}</p></aside></div>
</div>
@endsection
