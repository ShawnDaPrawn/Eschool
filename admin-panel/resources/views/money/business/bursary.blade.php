@extends('layouts.master')
@section('title', __('Review bursary application'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench">
<x-page.header :title="$record['learner_name']" :subtitle="__('Bursary application').' · '.$record['session_year'].' · '.__($record['state'])" />
<x-m3.button variant="text" :href="route('bursary-panel.index')">{{ __('Back to applications') }}</x-m3.button>
@php
$tabs=[];
foreach(['overview'=>__('Application'),'history'=>__('Decision history')] as $key=>$label) $tabs[$key]=['label'=>$label,'url'=>route('bursary-panel.show',['application'=>$record['id'],'section'=>$key])];
if($record['state']==='submitted' && $canWrite) foreach(['note'=>__('Add committee note'),'award'=>__('Award bursary'),'decline'=>__('Decline application')] as $key=>$label) $tabs[$key]=['label'=>$label,'url'=>route('bursary-panel.show',['application'=>$record['id'],'section'=>$key])];
if($record['state']==='awarded' && !$record['fee_discount_id'] && $canWrite) $tabs['discount']=['label'=>__('Prepare fee discount'),'url'=>route('bursary-panel.show',['application'=>$record['id'],'section'=>'discount'])];
@endphp
@include('money.billing.tabs')
<div class="workspace-layout"><div class="workspace-main">
@if($section==='overview')
<section class="workspace-section"><h2>{{ __('Private application details') }}</h2>
<dl><dt>{{ __('Applying guardian') }}</dt><dd>{{ $record['guardian_name'] }}</dd><dt>{{ __('Submitted') }}</dt><dd>{{ $record['submitted_at'] ?? '—' }}</dd></dl>
@if($record['erased'])<p role="status">{{ __('Private household details and documents have reached their retention limit.') }}</p>
@else
<dl>@foreach(['household_size'=>__('Household size'),'dependants'=>__('Dependants'),'monthly_income_cents'=>__('Monthly household income'),'monthly_expenses_cents'=>__('Monthly household expenses'),'other_support'=>__('Other support'),'explanation'=>__('Family explanation')] as $key=>$label)
<dt>{{ $label }}</dt><dd>{{ str_ends_with($key,'_cents') ? \App\Services\Platform\Ledger\Money::ofCents((int)($record['household'][$key]??0))->format() : ($record['household'][$key]??'—') }}</dd>
@endforeach</dl>
<h3>{{ __('Supporting documents') }}</h3>
<ul>@forelse($record['documents'] as $document)<li><a href="{{ route('bursary-panel.document',['application'=>$record['id'],'document'=>$document['id']]) }}">{{ __($document['category']) }} · {{ $document['mime'] }}</a></li>@empty<li>{{ __('No retained supporting documents.') }}</li>@endforelse</ul>
@endif
</section>
@elseif($section==='history')
<section class="workspace-section"><h2>{{ __('Retained decision history') }}</h2>
@forelse($record['events'] as $event)<article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ __($event['action']) }}</h3><p>{{ $event['actor_name'] }} · {{ $event['at'] }}</p>@if($event['reasons'])<p>{{ $event['reasons'] }}</p>@endif
@if($event['award_value'])<p>{{ $event['award_kind']==='percent' ? (intdiv($event['award_value'],100).'.'.str_pad((string)($event['award_value']%100),2,'0',STR_PAD_LEFT).'%') : \App\Services\Platform\Ledger\Money::ofCents($event['award_value'],$record['currency'])->format() }}</p>@endif</div></article>@empty<p>{{ __('No committee decisions have been recorded.') }}</p>@endforelse
</section>
@elseif($section==='note' && $record['state']==='submitted' && $canWrite)
<x-page.form-card id="bursary-note" :action="route('bursary-panel.note',['application'=>$record['id']])" :title="__('Add committee note')" :submit-label="__('Save private committee note')" :draft="false" :cancel-href="route('bursary-panel.show',['application'=>$record['id']])">
@include('money.business.hidden')
<x-page.field name="reasons" type="textarea" :label="__('Private committee note')" minlength="5" maxlength="5000" required />
<p>{{ __('A guardian of this learner cannot add a committee note or make the decision.') }}</p>
</x-page.form-card>
@elseif(in_array($section,['award','decline'],true) && $record['state']==='submitted' && $canWrite)
<x-page.form-card id="bursary-decision" :action="route('bursary-panel.decide',['application'=>$record['id']])" :title="$section==='award'?__('Award bursary'):__('Decline application')" :submit-label="__('Record committee decision')" :draft="false" :cancel-href="route('bursary-panel.show',['application'=>$record['id']])">
@include('money.business.hidden')<input type="hidden" name="revision" value="{{ $record['revision'] }}"><input type="hidden" name="outcome" value="{{ $section }}">
@if($section==='award')
<x-page.field name="award_kind" type="select" :label="__('Award basis')" :options="['fixed'=>__('Fixed currency amount'),'percent'=>__('Percentage of eligible fees')]" required />
<x-page.field name="award_amount" :label="__('Award amount or percentage')" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,2})?" :help="__('For a fixed award enter the currency amount. For a percentage enter a value up to 100.')" required />
<x-page.field name="currency" :label="__('Award currency')" :value="\App\Services\Platform\Ledger\Money::defaultCurrency()" pattern="[A-Z]{3}" maxlength="3" required />
@endif
<x-page.field name="reasons" type="textarea" :label="__('Private decision reasons')" minlength="10" maxlength="5000" required />
<p>{{ __('An award is not a posted fee reduction. The fee discount needs independent Finance approval and application.') }}</p>
</x-page.form-card>
@elseif($section==='discount' && $record['state']==='awarded' && !$record['fee_discount_id'] && $canWrite)
<x-page.form-card id="bursary-discount" :action="route('bursary-panel.discount',['application'=>$record['id']])" :title="__('Prepare awarded fee discount')" :submit-label="__('Create draft fee discount')" :draft="false" :cancel-href="route('bursary-panel.show',['application'=>$record['id']])">
@include('money.business.hidden')<p>{{ __('The award year must now be the school’s current year. This creates a draft; an independent Finance operator must approve and apply it.') }}</p>
</x-page.form-card>
@else<x-page.empty-state :title="__('This action is no longer available')" :message="__('Review the current application state and retained history before continuing.')" />
@endif
</div><aside class="workspace-context"><h2>{{ __('Application context') }}</h2><dl><dt>{{ __('School year') }}</dt><dd>{{ $record['session_year'] }}</dd><dt>{{ __('State') }}</dt><dd>{{ __($record['state']) }}</dd><dt>{{ __('Revision') }}</dt><dd>{{ $record['revision'] }}</dd></dl>
@if($record['fee_discount_id'])<p>{{ __('A draft fee discount has been created. Approval and application remain separate Finance actions.') }}</p>@can('fee-credits-manage')<x-m3.button variant="outlined" :href="route('fee-discounts.edit',['rule'=>$record['fee_discount_id']])">{{ __('Review fee discount') }}</x-m3.button>@endcan
@endif
<p>{{ __('Household details and committee reasons are private to the authorised review team.') }}</p></aside></div>
</div>
@endsection
