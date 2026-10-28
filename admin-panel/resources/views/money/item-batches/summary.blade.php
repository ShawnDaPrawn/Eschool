<p>{{ $preview['item_name'] }} · {{ $preview['charged_on'] }} · {{ __('Due') }} {{ $preview['due_on'] }}</p>
<dl>
<dt>{{ __('New invoice total') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($preview['amount_cents'],$preview['currency'])->format() }}</dd>
<dt>{{ __('New charges') }}</dt><dd>{{ count($preview['charges']) }}</dd>
<dt>{{ __('Learners outside the item rule') }}</dt><dd>{{ $preview['excluded_by_rule'] }}</dd>
<dt>{{ __('Exceptions requiring attention') }}</dt><dd>{{ count($preview['exceptions']) }}</dd>
<dt>{{ __('Already charged occurrences') }}</dt><dd>{{ count($preview['skipped']) }}</dd>
</dl>
@if($preview['exceptions']!==[])<h2 class="m3-title-large">{{ __('Exceptions requiring attention') }}</h2><ul>@foreach($preview['exceptions'] as $issue)<li>{{ __('Learner') }} {{ $issue['learner_id'] }} · {{ __($issue['message']) }}</li>@endforeach</ul>@endif
@if($preview['skipped']!==[])<h2 class="m3-title-large">{{ __('Already charged occurrences') }}</h2><ul>@foreach($preview['skipped'] as $skip)<li><a href="{{ route('fee-item-charges.show',['charge'=>$skip['charge_id']]) }}">ITEM-{{ $skip['charge_id'] }}</a></li>@endforeach</ul>@endif
@if($preview['charges']!==[])
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Reviewed item targets') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Reviewed item targets') }}</caption><thead><tr><th scope="col">{{ __('Family account') }}</th><th scope="col">{{ __('Learners') }}</th><th scope="col">{{ __('Total charge including VAT') }}</th><th scope="col">{{ __('Included VAT') }}</th></tr></thead>
<tbody>@foreach($preview['charges'] as $row)<tr><td>{{ $row['preview']['account_name'] }}</td><td>{{ implode(', ',array_column($row['preview']['learners'],'name')) }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['preview']['amount_cents'],$preview['currency'])->format() }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($row['preview']['vat_cents'],$preview['currency'])->format() }}</td></tr>@endforeach</tbody>
</table></div></div>
@endif
