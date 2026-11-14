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
