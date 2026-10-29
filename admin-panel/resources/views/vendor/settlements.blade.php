@extends('vendor.layout')
@section('content')
<h1>{{ __('Your counter settlements') }}</h1>
@if($receipt)
<h2>{{ __('Settlement') }} {{ $receipt['id'] }} · {{ __($receipt['status']) }}</h2>
<p>{{ $receipt['period_from'] }}–{{ $receipt['period_to'] }}</p>
<dl>@foreach(['gross_cents'=>'Original gross','sales_net_cents'=>'VAT-exclusive sales net','vat_cents'=>'VAT included','collector_fee_cents'=>'Collector fee','payout_cents'=>'Vendor settlement amount'] as $key=>$label)<dt>{{ __($label) }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($receipt[$key],$receipt['currency'])->format() }}</dd>@endforeach</dl>
@if($receipt['paid_on'])<p>{{ __('Actual settlement date') }}: {{ $receipt['paid_on'] }}</p>@endif
<ul>@foreach($receipt['lines'] as $line)<li>{{ $line['order_number'] }} · {{ \App\Services\Platform\Ledger\Money::ofCents($line['gross_cents'],$receipt['currency'])->format() }}</li>@endforeach</ul>
@endif
<ul>@forelse($listing['items'] as $row)<li><a href="{{ route('vendor.settlement',['vendor'=>$vendor,'settlement'=>$row['id']]) }}">{{ __('Settlement') }} {{ $row['id'] }} · {{ $row['period_from'] }}–{{ $row['period_to'] }}</a> · {{ \App\Services\Platform\Ledger\Money::ofCents($row['payout_cents'],$row['currency'])->format() }} · {{ __($row['status']) }}</li>@empty<li>{{ __('No vendor settlement batches have been prepared.') }}</li>@endforelse</ul>
@if($listing['page']>1)<a href="{{ route('vendor.settlements',['vendor'=>$vendor,'page'=>$listing['page']-1]) }}">{{ __('Previous settlements') }}</a>@endif
@if($listing['page']<$listing['last_page'])<a href="{{ route('vendor.settlements',['vendor'=>$vendor,'page'=>$listing['page']+1]) }}">{{ __('More settlements') }}</a>@endif
<p><a href="{{ route('vendor.index',['vendor_id'=>$vendor]) }}">{{ __('Back to vendor counter') }}</a></p>
@endsection
