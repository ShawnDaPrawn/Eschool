@extends('layouts.master')
@section('title', __('Vendors and stock'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vendor-catalogues.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench vendor-catalogues">
    <x-page.header :title="__('Vendors and stock')" :subtitle="__('Prepare vendors, explicit settlement terms, products and counted stock.')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if(!$canWrite)<p role="status">{{ __('Vendor history is read-only while this module is paused.') }}</p>@endif
    <p>{{ __('Drafts can be prepared before activation. Activation requires explicit VAT, settlement and refund responsibility choices.') }}</p>
    @if(!$walletCurrency)
    <aside role="status" class="m3-surface-container-low p-3 mb-3">
        <h2>{{ __('Set the school currency first') }}</h2>
        <p>{{ __('Finance must choose the wallet currency before vendors or products can be saved. You can prepare categories now.') }}</p>
        @if($canConfigureWallets)<a href="{{ route('wallet-finance.index') }}">{{ __('Open wallet settings') }}</a>
        @else<p>{{ __('Ask Finance to configure the school wallet currency.') }}</p>@endif
    </aside>
    @endif
    <p><a href="{{ route('vendor-catalogues.index') }}">{{ __('New vendor or category') }}</a></p>
    <h2>{{ __('Vendor categories') }}</h2>
    <ul>@foreach($categoryOptions as $categoryId=>$label)<li><a href="{{ route('vendor-catalogues.index',['category_id'=>$categoryId]) }}">{{ $label }}</a></li>@endforeach</ul>
    <form method="post" action="{{ route('vendor-catalogues.save',['kind'=>'category']) }}" aria-label="{{ __('Save vendor category') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$category?->revision??0])
        @if($category)<input type="hidden" name="id" value="{{ $category->id }}">@endif
        <fieldset @disabled(!$canWrite)><legend>{{ __('Category details') }}</legend>
            <x-page.field id="category-key" :restore-old-input="false" name="key" :label="__('Permanent category key')" :value="$category?->key" :readonly="(bool)$category" required maxlength="40" pattern="[a-z][a-z0-9_-]{0,39}" />
            <x-page.field id="category-name" :restore-old-input="false" name="name" :label="__('Category name')" :value="$category?->name" required maxlength="100" />
            <x-page.field id="category-status" :restore-old-input="false" type="select" name="status" :label="__('Category status')" :value="$category?->status" :options="['active'=>__('Active'),'archived'=>__('Archived')]" />
            <x-m3.button type="submit">{{ __('Save vendor category') }}</x-m3.button>
        </fieldset>
    </form>
    <h2>{{ __('Vendors') }}</h2>
    <ul>@foreach($vendors as $row)<li><a href="{{ route('vendor-catalogues.index',['vendor_id'=>$row->id]) }}">{{ $row->name }}</a> · {{ __($row->status) }}</li>@endforeach</ul>
    {{ $vendors->withQueryString()->links() }}
    <form method="post" action="{{ route('vendor-catalogues.save',['kind'=>'vendor']) }}" aria-label="{{ __('Save vendor') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$vendor?->revision??0])
        @if($vendor)<input type="hidden" name="id" value="{{ $vendor->id }}">@endif
        <fieldset @disabled(!$canWrite)><legend>{{ __('Vendor details and settlement choices') }}</legend>
            <x-page.field id="vendor-name" :restore-old-input="false" name="name" :label="__('Vendor name')" :value="$vendor?->name" required maxlength="160" />
            <x-page.field id="vendor-legal_name" :restore-old-input="false" name="legal_name" :label="__('Legal trading name')" :value="$vendor?->legal_name" maxlength="160" />
            <x-page.field id="vendor-legal_address" :restore-old-input="false" type="textarea" name="legal_address" :label="__('Legal trading address for VAT receipts')" :value="$vendor?->legal_address" maxlength="1000" />
            <x-page.field id="vendor-description" :restore-old-input="false" type="textarea" name="description" :label="__('Storefront description')" :value="$vendor?->description" maxlength="2000" />
            <x-page.field id="vendor-category_id" :restore-old-input="false" type="select" name="category_id" :label="__('Vendor category')" :value="$vendor?->category_id" :options="$categoryOptions" required />
            <x-page.field id="vendor-kind" :restore-old-input="false" type="select" name="kind" :label="__('Vendor ownership')" :value="$vendor?->kind" :options="['school'=>__('School run'),'independent'=>__('Independent vendor')]" required />
            <x-page.field id="vendor-status" :restore-old-input="false" type="select" name="status" :label="__('Vendor status')" :value="$vendor?->status??'draft'" :options="['draft'=>__('Draft'),'active'=>__('Active'),'archived'=>__('Archived')]" required />
            <x-page.field id="vendor-vat_registered" :restore-old-input="false" type="select" name="vat_registered" :label="__('Vendor VAT registration')" :value="$vendor?->vat_registered === null ? '' : (int)$vendor->vat_registered" :options="[''=>__('Choose before activation'),'0'=>__('Not VAT registered'),'1'=>__('VAT registered')]" />
            <x-page.field id="vendor-vat_number" :restore-old-input="false" name="vat_number" :label="__('VAT registration number')" :value="$vendor?->vat_number" maxlength="40" />
            <x-page.field id="vendor-refund_bearer" :restore-old-input="false" type="select" name="refund_bearer" :label="__('Who bears refunds')" :value="$vendor?->refund_bearer" :options="[''=>__('Choose before activation'),'school'=>__('School'),'vendor'=>__('Vendor')]" />
            <x-page.field id="vendor-chargeback_bearer" :restore-old-input="false" type="select" name="chargeback_bearer" :label="__('Who bears chargebacks')" :value="$vendor?->chargeback_bearer" :options="[''=>__('Choose before activation'),'school'=>__('School'),'vendor'=>__('Vendor')]" />
            <x-page.field id="vendor-settlement_method" :restore-old-input="false" type="select" name="settlement_method" :label="__('Settlement method')" :value="$vendor?->settlement_method" :options="[''=>__('Choose before activation'),'school_internal'=>__('Retain in school ledger'),'cash'=>__('Verified cash settlement'),'eft'=>__('Verified bank EFT settlement')]" />
            <x-page.field id="vendor-settlement_cycle" :restore-old-input="false" type="select" name="settlement_cycle" :label="__('Settlement frequency')" :value="$vendor?->settlement_cycle" :options="[''=>__('Choose before activation'),'daily'=>__('Daily'),'weekly'=>__('Weekly'),'monthly'=>__('Monthly'),'manual'=>__('Manual review')]" />
            <x-page.field id="vendor-settlement_delay_days" :restore-old-input="false" type="number" name="settlement_delay_days" :label="__('Settlement delay in days')" :value="$vendor?->settlement_delay_days" min="0" max="90" step="1" />
            <x-page.field id="vendor-settlement_terms" :restore-old-input="false" type="textarea" name="settlement_terms" :label="__('Agreed settlement terms')" :value="$vendor?->settlement_terms" minlength="10" maxlength="4000" />
            <x-page.field id="vendor-private_bank_instructions" :restore-old-input="false" type="textarea" name="private_bank_instructions" :label="__('Private vendor bank instructions')" autocomplete="off" minlength="10" maxlength="4000" />
            @if($vendor?->getRawOriginal('private_bank_instructions'))<p>{{ __('Private bank instructions are retained. Leave the field blank to keep them.') }}</p>@endif
            <x-m3.button type="submit">{{ __('Save vendor') }}</x-m3.button>
        </fieldset>
    </form>
    @if($vendor)
        <h2>{{ __('Products') }} · {{ $vendor->name }}</h2>
        <p><a href="{{ route('vendor-catalogues.index',['vendor_id'=>$vendor->id]) }}">{{ __('New product') }}</a></p>
        <ul>@foreach($products as $row)<li><a href="{{ route('vendor-catalogues.index',['vendor_id'=>$vendor->id,'product_id'=>$row->id]) }}">{{ $row->name }}</a> · {{ $row->sku }} · {{ \App\Services\Platform\Ledger\Money::ofCents($row->price_cents,$row->currency)->format() }} · {{ __($row->status) }}</li>@endforeach</ul>
        {{ $products->withQueryString()->links() }}
        <form method="post" action="{{ route('vendor-catalogues.save',['kind'=>'product']) }}" aria-label="{{ __('Save vendor product') }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>$product?->revision??0])
            <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">@if($product)<input type="hidden" name="id" value="{{ $product->id }}">@endif
            <fieldset @disabled(!$canWrite)><legend>{{ __('Product price, tax and stock mode') }}</legend>
                <x-page.field id="product-sku" :restore-old-input="false" name="sku" :label="__('Permanent product SKU')" :value="$product?->sku" required maxlength="64" :readonly="(bool)$product" />
                <x-page.field id="product-name" :restore-old-input="false" name="name" :label="__('Product name')" :value="$product?->name" required maxlength="160" />
                <x-page.field id="product-description" :restore-old-input="false" type="textarea" name="description" :label="__('Product description')" :value="$product?->description" maxlength="2000" />
                <x-page.field id="product-unit_label" :restore-old-input="false" name="unit_label" :label="__('Unit label')" :value="$product?->unit_label" required maxlength="40" />
                <x-page.field id="product-category_id" :restore-old-input="false" type="select" name="category_id" :label="__('Product category')" :value="$product?->category_id??$vendor->category_id" :options="$categoryOptions" required />
                <x-page.field id="product-price" :restore-old-input="false" name="price" :label="__('Price including VAT')" :value="$product ? \App\Services\Platform\Ledger\Money::ofCents($product->price_cents,$product->currency)->toDecimalString() : ''" inputmode="decimal" required pattern="[0-9]+([.][0-9]{1,2})?" />
                <x-page.field id="product-vat_kind" :restore-old-input="false" type="select" name="vat_kind" :label="__('Product VAT treatment')" :value="$product?->vat_kind" :options="['none'=>__('Vendor not VAT registered'),'standard'=>__('Standard rated'),'zero'=>__('Zero rated'),'exempt'=>__('Exempt')]" required />
                <x-page.field id="product-vat_rate_bps" :restore-old-input="false" type="number" name="vat_rate_bps" :label="__('VAT rate in basis points; zero unless standard rated')" :value="$product?->vat_rate_bps" min="0" max="10000" step="1" required />
                <x-page.field id="product-tracks_stock" :restore-old-input="false" type="select" name="tracks_stock" :label="__('Permanent stock mode')" :value="$product === null ? '' : (int)$product->tracks_stock" :options="[''=>__('Choose explicitly'),'1'=>__('Track counted units'),'0'=>__('Do not track stock')]" required />
                <x-page.field id="product-status" :restore-old-input="false" type="select" name="status" :label="__('Product status')" :value="$product?->status??'draft'" :options="['draft'=>__('Draft'),'active'=>__('Active'),'archived'=>__('Archived')]" required />
                <x-m3.button type="submit">{{ __('Save vendor product') }}</x-m3.button>
            </fieldset>
        </form>
    @endif
    @if($product && $stock)
        <h2>{{ __('Counted stock') }} · {{ $product->name }}</h2>
        <p>{{ __('Units on hand') }}: {{ $stock->on_hand }}</p>
        <form method="post" action="{{ route('vendor-catalogues.save',['kind'=>'stock']) }}" aria-label="{{ __('Record stock adjustment') }}" data-year-safe>
            @include('money.fee-plans.hidden',['revision'=>0])<input type="hidden" name="product_id" value="{{ $product->id }}">
            <fieldset @disabled(!$canWrite)><legend>{{ __('Record a counted stock change') }}</legend>
                <x-page.field id="stock-source_reference" :restore-old-input="false" name="source_reference" :label="__('Unique physical stock reference')" required maxlength="96" />
                <x-page.field id="stock-kind" :restore-old-input="false" type="select" name="kind" :label="__('Stock adjustment kind')" :options="['receipt'=>__('Received stock'),'correction'=>__('Count correction'),'waste'=>__('Waste')]" required />
                <x-page.field id="stock-units_delta" :restore-old-input="false" type="number" name="units_delta" :label="__('Unit change; negative to remove')" min="-1000000000" max="1000000000" step="1" required />
                <x-page.field id="stock-private_reason" :restore-old-input="false" type="textarea" name="private_reason" :label="__('Private stock adjustment reason')" required minlength="3" maxlength="2000" />
                <x-m3.button type="submit">{{ __('Record stock adjustment') }}</x-m3.button>
            </fieldset>
        </form>
        <ul>@foreach($movements as $movement)<li>{{ $movement->source_reference }} · {{ __($movement->kind) }} · {{ $movement->units_delta }} · {{ __('Units after change') }}: {{ $movement->after_units }}</li>@endforeach</ul>
        {{ $movements->withQueryString()->links() }}
    @endif
</div>
@endsection
