@php($vat=(int)($snapshot['vat_cents']??0))
@php($mode=$snapshot['tax_treatment']??'no_vat')
@if($mode==='no_vat')<p>{{ __('No VAT charged on this invoice.') }}</p>@endif
<p>{{ __(\App\Services\Money\FeeTaxAmounts::labels()[$mode]??'No VAT charged') }}@if(in_array($mode,['vat_inclusive','vat_exclusive'],true)) · {{ intdiv($snapshot['vat_rate_bp'],100) }}.{{ str_pad((string)($snapshot['vat_rate_bp']%100),2,'0',STR_PAD_LEFT) }}%@endif</p>
<dl><dt>{{ __('Amount before VAT') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($snapshot['net_cents']??$snapshot['amount_cents'],$snapshot['currency'])->format() }}</dd><dt>{{ __('VAT amount') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($vat,$snapshot['currency'])->format() }}</dd><dt>{{ __('Total charge including VAT') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($snapshot['amount_cents'],$snapshot['currency'])->format() }}</dd></dl>
@if($details=$snapshot['tax_details']??null)
<section aria-label="{{ __('Recorded legal seller') }}"><h2>{{ __('Recorded legal seller') }}</h2><p>{{ $details['seller_name'] }}</p>
@foreach(preg_split('/\R/u',$details['seller_address']) as $addressLine)<p>{{ $addressLine }}</p>@endforeach
<p>{{ __('VAT registration number') }}: {{ $details['tax_registration'] }} · {{ $details['country_code'] }}</p></section>
@endif
