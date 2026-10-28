@if(($snapshot['original_vat_cents']??0)>0)
<dl>
    <dt>{{ __('VAT included in this credit') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($snapshot['vat_cents'],$currency)->format() }}</dd>
    <dt>{{ __('Credit before VAT') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($snapshot['net_cents'],$currency)->format() }}</dd>
</dl>
<p>{{ __('VAT is reversed from the original invoice, even if the item rate or school registration has since changed.') }}</p>
@endif
