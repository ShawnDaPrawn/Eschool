@extends('layouts.master')
@section('title', __('Automatic item batch'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="'ITEM-BATCH-'.$batch->id" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-item-batches.index')">{{ __('Back to automatic item batches') }}</x-m3.button>
<p>{{ __('This batch is posted and immutable. Original invoices and any later credits remain in their recorded history.') }}</p>
@include('money.item-batches.summary')
<h2 class="m3-title-large">{{ __('Posted item invoices') }}</h2>
<ul>@foreach($batch->charge_ids as $charge)<li><a href="{{ route('fee-item-charges.show',['charge'=>$charge]) }}">ITEM-{{ $charge }}</a></li>@endforeach</ul>
</div>
@endsection
