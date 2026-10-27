@extends('layouts.master')
@section('title', __('Discounts and bursaries'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Discounts and bursaries')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p>{{ __('Discount history is read-only while fees are disabled.') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-discounts.index')">{{ __('Back to discounts') }}</x-m3.button>
<h2>FEE-RED-{{ $run->id }} · {{ $run->state }}</h2>
<p>{{ $run->snapshot['rule']['name'] }} · {{ __('Rule revision') }} {{ $run->snapshot['rule']['revision'] }}</p>
<dl><dt>{{ __('Remaining source charge before reduction') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->snapshot['basis_cents'],$run->currency)->format() }}</dd><dt>{{ __('Reduction') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->amount_cents,$run->currency)->format() }}</dd><dt>{{ __('Charge after reduction') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($run->snapshot['remaining_cents'],$run->currency)->format() }}</dd></dl>
<p>{{ __('Private Finance explanation') }}: {{ $run->private_explanation }}</p>
<p>{{ __('A paid reduction restores held credit to original receipts and keeps learner restrictions. This operation makes no cash refund.') }}</p>
<div class="page-table__scroll" role="region" aria-label="{{ __('Original-holder credits') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Original-holder credits') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Invoice') }}</th><th scope="col">{{ __('Reduction') }}</th><th scope="col">{{ __('VAT reversed') }}</th></tr></thead><tbody>
@foreach($run->snapshot['credits'] as $credit)<tr><td>{{ $credit['preview']['learner_name'] }}</td><td>{{ $credit['preview']['holder_name'] }}</td><td>{{ $credit['preview']['invoice_number'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($credit['preview']['amount_cents'],$run->currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($credit['preview']['vat_cents'],$run->currency)->format() }}</td></tr>@endforeach
</tbody></table></div>
@if($canWrite && $run->state!=='posted')
<form method="post" action="{{ $run->state==='preview' ? route('fee-discounts.approve',['run'=>$run->id]) : route('fee-discounts.post',['run'=>$run->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$run->revision])
<x-m3.checkbox name="confirmed" value="1" :label="__('I reviewed these exact original-holder credits and confirm the next step.')" required />
<x-m3.button type="submit">{{ $run->state==='preview' ? __('Approve fee reduction') : __('Post approved reduction') }}</x-m3.button></form>
@endif
@if($run->state==='posted')<h3>{{ __('Retained credit notes') }}</h3>@foreach($run->credit_note_ids as $note)<p><a href="{{ route('fee-credit-notes.show',['note'=>$note]) }}">FEE-CN-{{ $note }}</a></p>@endforeach @endif
</div>
@endsection
