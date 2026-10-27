@extends('layouts.master')
@section('title', __('Fee credit notes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
    <x-page.header :title="'FEE-CN-'.$note->id" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    <x-m3.button variant="text" :href="route('fee-credit-notes.index')">{{ __('Back to credit notes') }}</x-m3.button>
    @if(auth()->user()->can('fee-payments-manage'))<x-m3.button variant="outlined" :href="route('fee-payments.statement',['account'=>$note->fee_account_id])">{{ __('Account statement and receipts') }}</x-m3.button>@endif
    <p>{{ $note->snapshot['invoice_number'] }} · {{ $note->snapshot['learner_name'] }} · {{ $note->snapshot['holder_name'] }}</p>
    <dl><dt>{{ __('Issued on') }}</dt><dd>{{ $note->issued_on }}</dd>
        @foreach(['amount_cents'=>'Credit amount','unpaid_cents'=>'Debt reduction','released_cents'=>'Restored held credit'] as $field=>$label)<dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($note->$field,$note->currency)->format() }}</dd>@endforeach
        <dt>{{ __('Correction category') }}</dt><dd>{{ \App\Services\Money\FeeCreditNotes::reasonLabels()[$note->reason_code] }}</dd>
        <dt>{{ __('Private Finance explanation') }}</dt><dd>{{ $note->reason }}</dd>
    </dl>
    @include('money.credits.tax-breakdown',['snapshot'=>$note->snapshot,'currency'=>$note->currency])
    <p>{{ __('This correction is posted and cannot be edited. Original invoices and receipts remain in the audit history.') }}</p>
    @if($releases->isNotEmpty())<h2 class="m3-title-large">{{ __('Funds restored to original receipts') }}</h2><ul>@foreach($releases as $release)<li>FEE-PAY-{{ $release->payment_id }} · {{ \App\Services\Platform\Ledger\Money::ofCents($release->amount_cents,$note->currency)->format() }}</li>@endforeach</ul>@endif
</div>
@endsection
