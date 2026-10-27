@extends('layouts.master')
@section('title', __('Fee credit notes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="__('Fee credit notes')" />
    <p>{{ __('Credit notes correct recorded invoice shares. Original invoices and receipts remain unchanged.') }}</p>
    @if($canWrite)<x-m3.button :href="route('fee-credit-notes.new')">{{ __('Prepare credit note') }}</x-m3.button>@else<p>{{ __('Credit history is read-only while fees are disabled.') }}</p>@endif
    @if($notes->isEmpty())<x-page.empty-state :title="__('No posted credit notes')" :message="__('Choose a recorded invoice share to prepare a reasoned correction.')" />@else
    <div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Posted credit notes') }}" tabindex="0"><table class="page-table__table">
        <caption>{{ __('Posted credit notes') }}</caption><thead><tr><th scope="col">{{ __('Credit note') }}</th><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Issued on') }}</th><th scope="col">{{ __('Credit amount') }}</th></tr></thead>
        <tbody>@foreach($notes as $note)<tr><td><a href="{{ route('fee-credit-notes.show',['note'=>$note->id]) }}">FEE-CN-{{ $note->id }}</a></td><td>{{ $note->snapshot['learner_name'] }}</td><td>{{ $note->snapshot['holder_name'] }}</td><td>{{ $note->issued_on }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($note->amount_cents,$note->currency)->format() }}</td></tr>@endforeach</tbody>
    </table></div></div>@include('money.billing.pagination',['paginator'=>$notes])@endif
</div>
@endsection
