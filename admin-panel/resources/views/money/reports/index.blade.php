@extends('layouts.master')
@section('title', __('Arrears and accounting'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}?v={{ filemtime(public_path('assets/css/fee-plans.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Arrears and accounting')" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<p>{{ __('Current balances as at') }} {{ $report['as_of'] }}. {{ __('Held credits remain separate until they are allocated to eligible invoice shares.') }}</p>
<x-m3.button variant="outlined" :href="route('fee-reporting.settings')">{{ __('Accounting export mapping') }}</x-m3.button>
<x-m3.button variant="text" :href="route('fee-payments.index')">{{ __('Fee payments') }}</x-m3.button>
<form method="get"><x-m3.select name="currency" :label="__('Report currency')" :options="$currencies" :value="$currency" required /><x-m3.select name="status" :label="__('Families shown')" :value="$status" :options="['all'=>__('All family accounts'),'overdue'=>__('Overdue balances'),'credit'=>__('Held credit balances')]" /><x-m3.button type="submit" variant="outlined">{{ __('Show balances') }}</x-m3.button></form>
<h2>{{ __('Totals for all family accounts in this currency') }}</h2>
<dl>@foreach(['current'=>__('Current'),'days_1_29'=>__('1–29 days overdue'),'days_30_59'=>__('30–59 days overdue'),'days_60_89'=>__('60–89 days overdue'),'days_90_plus'=>__('90+ days overdue')] as $key=>$label)<dt>{{ $label }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($report['buckets'][$key],$currency)->format() }}</dd>@endforeach
<dt>{{ __('Total outstanding') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($report['outstanding_cents'],$currency)->format() }}</dd><dt>{{ __('Held credit') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($report['credit_cents'],$currency)->format() }}</dd></dl>
@if($accounts->isEmpty())<x-page.empty-state :title="__('No families in this report filter')" :message="__('Choose another balance filter or currency.')" />@else
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Aged family balances') }}" tabindex="0"><table class="page-table__table"><caption>{{ __('Aged family balances') }}</caption><thead><tr><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Family payment reference') }}</th><th scope="col">{{ __('Current') }}</th><th scope="col">{{ __('1–29 days overdue') }}</th><th scope="col">{{ __('30–59 days overdue') }}</th><th scope="col">{{ __('60–89 days overdue') }}</th><th scope="col">{{ __('90+ days overdue') }}</th><th scope="col">{{ __('Outstanding') }}</th><th scope="col">{{ __('Held credit') }}</th></tr></thead><tbody>
@foreach($accounts as $account)<tr><td><a href="{{ route('fee-payments.statement',['account'=>$account['id']]) }}">{{ $account['name'] }}</a></td><td>{{ $account['reference'] }}</td>@foreach($account['buckets'] as $cents)<td>{{ \App\Services\Platform\Ledger\Money::ofCents($cents,$currency)->format() }}</td>@endforeach<td>{{ \App\Services\Platform\Ledger\Money::ofCents($account['outstanding_cents'],$currency)->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($account['credit_cents'],$currency)->format() }}</td></tr>@endforeach
</tbody></table></div></div>@include('money.billing.pagination',['paginator'=>$accounts])@endif
<h2>{{ __('Download accounting CSV') }}</h2><p>{{ __('Ageing and charge detail exports show current remaining balances. Journal dates apply only to posted journal exports.') }}</p>
<p>{{ __('Journal references are stable. Use non-overlapping periods and import each journal once into your accounting system.') }}</p>
<form method="post" action="{{ route('fee-reporting.export') }}" data-year-safe>@include('money.fee-plans.hidden',['revision'=>0])
<input type="hidden" name="currency" value="{{ $currency }}">
<x-m3.select name="layout" :label="__('Export layout')" :options="['ageing'=>__('Aged debtors CSV'),'charges'=>__('Invoice share detail CSV'),'journal'=>__('Posted fee journals CSV'),'xero'=>__('Xero journal columns'),'quickbooks'=>__('QuickBooks journal columns')]" required />
<x-m3.text-field name="from_on" type="date" :label="__('Journal start date')" :value="$monthStart" :max="$today" required />
<x-m3.text-field name="through_on" type="date" :label="__('Journal end date')" :value="$today" :max="$today" required />
<x-m3.button type="submit">{{ __('Download private CSV') }}</x-m3.button></form>

</div>
@endsection
