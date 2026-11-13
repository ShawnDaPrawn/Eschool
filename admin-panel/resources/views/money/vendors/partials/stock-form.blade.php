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
