@extends('layouts.master')
@section('title', __('Vendors and stock'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench vendor-catalogues">
@php
    $section=request('section', $product?'product':($category?'category':($vendor?'products':'vendors')));
    if(!in_array($section,['vendors','vendor','categories','category','products','product','stock'],true)) $section='vendors';
    $tabs=['vendors'=>['label'=>__('Vendors'),'url'=>route('vendor-catalogues.index')], 'categories'=>['label'=>__('Category configuration'),'url'=>route('vendor-catalogues.index',['section'=>'categories'])]];
    if($vendor) $tabs+=['products'=>['label'=>__('Products'),'url'=>route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'section'=>'products'])], 'vendor'=>['label'=>__('Vendor configuration'),'url'=>route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'section'=>'vendor'])]];
    if($product) $tabs+=['product'=>['label'=>__('Product details'),'url'=>route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'product_id'=>$product->id,'section'=>'product'])]];
    if($product && $stock) $tabs['stock']=['label'=>__('Counted stock'),'url'=>route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'product_id'=>$product->id,'section'=>'stock'])];
@endphp
<x-page.header :title="$vendor?->name??__('Vendors and stock')" :subtitle="__('Manage vendor records, products and counted stock. Open configuration when a policy or trading detail needs to change.')" />
@include('money.billing.tabs')
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if(!$canWrite)<p role="status">{{ __('Vendor history is read-only while this module is paused.') }}</p>@endif
@if(!$walletCurrency)<aside class="workspace-section"><h2>{{ __('Set the school currency first') }}</h2><p>{{ __('Finance must choose the wallet currency before vendors or products can be saved. Categories can be prepared now.') }}</p>@if($canConfigureWallets)<a href="{{ route('wallet-finance.index',['section'=>'settings']) }}">{{ __('Open wallet settings') }}</a>@endif</aside>@endif
@if($section==='vendors')
@if($canWrite)<x-m3.button :href="route('vendor-catalogues.index',['section'=>'vendor'])">{{ __('Create vendor') }}</x-m3.button>@endif
<div class="page-table__scroll" role="region" aria-label="{{ __('Vendors') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Vendors') }}</caption><thead><tr><th scope="col">{{ __('Vendor') }}</th><th scope="col">{{ __('Ownership') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Action') }}</th></tr></thead><tbody>
@forelse($vendors as $row)<tr><td>{{ $row->name }}</td><td>{{ __($row->kind) }}</td><td>{{ __($row->status) }}</td><td><x-m3.button variant="text" :href="route('vendor-catalogues.index',['vendor_id'=>$row->id,'section'=>'products'])">{{ __('Open vendor') }}</x-m3.button></td></tr>@empty<tr><td colspan="4">{{ __('No vendors configured yet.') }}</td></tr>@endforelse
</tbody></table></div>{{ $vendors->withQueryString()->links() }}
@elseif($section==='categories')
@if($canWrite)<x-m3.button :href="route('vendor-catalogues.index',['section'=>'category'])">{{ __('Create category') }}</x-m3.button>@endif
<ul class="money-record-list">@forelse($categoryOptions as $categoryId=>$label)<li><a href="{{ route('vendor-catalogues.index',['section'=>'category','category_id'=>$categoryId]) }}">{{ $label }}</a></li>@empty<li>{{ __('No vendor categories configured yet.') }}</li>@endforelse</ul>
@elseif($section==='category')
<h2>{{ $category?__('Edit vendor category'):__('Create vendor category') }}</h2>@include('money.vendors.partials.category-form')
@elseif($section==='vendor')
<h2>{{ $vendor?__('Vendor trading and settlement configuration'):__('Create vendor') }}</h2><p>{{ __('Drafts may be saved before activation. Activation requires explicit VAT, settlement and liability choices.') }}</p>@include('money.vendors.partials.vendor-form')
@elseif($section==='products' && $vendor)
@if($canWrite)<x-m3.button :href="route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'section'=>'product'])">{{ __('Create product') }}</x-m3.button>@endif
<div class="page-table__scroll" role="region" aria-label="{{ __('Vendor products') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Vendor products') }}</caption><thead><tr><th scope="col">{{ __('Product') }}</th><th scope="col">{{ __('SKU') }}</th><th scope="col">{{ __('Price') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead><tbody>
@forelse($products as $row)<tr><td><a href="{{ route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'product_id'=>$row->id,'section'=>'product']) }}">{{ $row->name }}</a></td><td>{{ $row->sku }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row->price_cents,$row->currency)->format() }}</td><td>{{ __($row->status) }}</td></tr>@empty<tr><td colspan="4">{{ __('No products for this vendor yet.') }}</td></tr>@endforelse
</tbody></table></div>{{ $products->withQueryString()->links() }}
@elseif($section==='product' && $vendor)
<h2>{{ $product?__('Edit product'):__('Create product') }}</h2>@include('money.vendors.partials.product-form')
@elseif($section==='stock' && $product && $stock)
<h2>{{ __('Counted stock').' · '.$product->name }}</h2><p>{{ __('Units on hand') }}: {{ $stock->on_hand }}</p>@include('money.vendors.partials.stock-form')
<h2>{{ __('Stock movement history') }}</h2><div class="page-table__scroll" role="region" aria-label="{{ __('Stock movements') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Stock movements') }}</caption><thead><tr><th scope="col">{{ __('Reference') }}</th><th scope="col">{{ __('Kind') }}</th><th scope="col">{{ __('Unit change') }}</th><th scope="col">{{ __('Units after change') }}</th></tr></thead><tbody>
@forelse($movements as $movement)<tr><td>{{ $movement->source_reference }}</td><td>{{ __($movement->kind) }}</td><td>{{ $movement->units_delta }}</td><td>{{ $movement->after_units }}</td></tr>@empty<tr><td colspan="4">{{ __('No stock movements recorded.') }}</td></tr>@endforelse
</tbody></table></div>{{ $movements->withQueryString()->links() }}
@else<x-page.empty-state :title="__('Select the vendor or product first')" :message="__('Open a record from the vendor directory to continue.')" />@endif
</div>
@endsection
