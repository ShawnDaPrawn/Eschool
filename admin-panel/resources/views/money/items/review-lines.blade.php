
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Item charge learner shares') }}" tabindex="0"><table class="page-table__table">
<caption>{{ __('Item charge learner shares') }}</caption><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Holder') }}</th><th scope="col">{{ __('Amount') }}</th></tr></thead><tbody>
@foreach($snapshot['lines'] as $line)@foreach($line['holder_shares'] as $holder)<tr><td>{{ $line['learner_name'] }}</td><td>{{ $holder['name'] }}</td><td>{{ \App\Services\Platform\Ledger\Money::ofCents($holder['amount_cents'],$snapshot['currency'])->format() }}</td></tr>@endforeach @endforeach
</tbody></table></div></div>
