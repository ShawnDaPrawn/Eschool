@extends('layouts.master')
@section('title', __('Bank statement matching'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Bank statement matching')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('bank-statements.index')">{{ __('Back to bank matching') }}</x-m3.button>
<p>{{ __('Convert your bank export to the supplied columns. Use ISO dates, decimal amounts without grouping, and one credit or debit per row. Maximum 500 rows and 256 KiB.') }}</p>
<p>{{ __('Transaction references must be unique across this school bank workflow. Keep the original bank transaction reference when a manual EFT receipt already exists. Never use the reusable family reference as the transaction ID.') }}</p>
<x-m3.button variant="outlined" :href="route('bank-statements.template')">{{ __('Download bank CSV headings') }}</x-m3.button>
<form method="post" enctype="multipart/form-data" action="{{ route('bank-statements.upload') }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Bank export file') }}</legend>
<x-m3.file-field name="statement" :label="__('Bank CSV file')" accept=".csv,text/csv" required />
<x-m3.button type="submit">{{ __('Review bank import') }}</x-m3.button>
</fieldset></form>

</div>
@endsection
