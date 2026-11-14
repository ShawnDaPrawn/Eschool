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
