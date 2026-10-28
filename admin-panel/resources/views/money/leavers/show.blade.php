@extends('layouts.master')
@section('title', __('Leaver credits'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Leaver credits')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Leaver credit history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-leavers.index')">{{ __('Back to leaver credits') }}</x-m3.button>
<h2>FEE-LEAVE-{{ $run->id }} · {{ $run->state }}</h2><p>{{ __('Original invoice') }}: {{ $run->snapshot['invoice_number'] }}</p>
<p>{{ __('Confirmed school departure') }}: {{ $run->snapshot['attendance']['ends_on'] }}</p>
<p>{{ __('Calculation contract') }}: {{ $run->snapshot['policy_origin']==='original_bill_policy' ? __('Original saved billing policy') : __('Explicit rule for prior policyless invoices') }}</p>
<dl><dt>{{ __('Original tuition charged') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->snapshot['original_gross_cents'],$run->currency)->format() }}</dd><dt>{{ __('Tuition for the served period') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->snapshot['served_cents'],$run->currency)->format() }}</dd><dt>{{ __('Additional credit now') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->amount_cents,$run->currency)->format() }}</dd></dl>
<p>{{ __('Private Finance explanation') }}: {{ $run->private_explanation }}</p>
<div class="page-table__scroll" role="region" aria-label="{{ __('Original-holder leaver credits') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Original-holder leaver credits') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Credit') }}</th><th scope="col">{{ __('Restored receipt credit') }}</th></tr></thead><tbody>
@foreach($run->snapshot['credits'] as $credit)<tr><td>{{ $credit['preview']['learner_name'] }}</td><td>{{ $credit['preview']['holder_name'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($credit['preview']['amount_cents'],$run->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($credit['preview']['released_cents'],$run->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>
@if($canWrite && $run->state!=='posted')
<form method="post" action="{{ $run->state==='preview' ? route('fee-leavers.approve',['run'=>$run->id]) : route('fee-leavers.post',['run'=>$run->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$run->revision])
<x-m3.checkbox name="confirmed" :label="__('I reviewed the departure, original policy and each payer credit and confirm the next step.')" required />
<x-m3.button type="submit">{{ $run->state==='preview' ? __('Approve leaver credit') : __('Post approved leaver credit') }}</x-m3.button></form>@endif
@if($run->state==='posted')<h3>{{ __('Retained credit notes') }}</h3>@foreach($run->credit_note_ids as $note)<p><a href="{{ route('fee-credit-notes.show',['note'=>$note]) }}">FEE-CN-{{ $note }}</a></p>@endforeach @endif
</div>
@endsection
