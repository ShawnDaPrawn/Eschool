<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\CashupCount;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class CashControlReconciliation
{
    /** Internal school-locked snapshot. Physical differences are retained for human review. */
    public function inspect(): array
    {
        $currencies = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('type', 'cash_desk')->distinct()->orderBy('currency')->pluck('currency');
        $reports = [];
        foreach ($currencies as $currency) {
            $flow = app(CashFlows::class)->inspect($currency);
            $custody = [];
            foreach ($flow['events'] as $event) {
                $custody[$event['operator_id']] = ($custody[$event['operator_id']] ?? 0) + $event['amount_cents'];
            }
            $stale = $missing = $shortages = 0;
            foreach ($custody as $operator => $cents) {
                $count = CashupCount::where('operator_id', $operator)->where('currency', $currency)->orderByDesc('counted_on')->orderByDesc('revision')->first();
                if ($count === null) {
                    $missing++;

                    continue;
                }
                $throughCount = array_values(array_filter($flow['events'], fn ($e) => $e['operator_id'] === $operator && $e['effective_on'] <= $count->counted_on));
                $hash = hash_hmac('sha256', json_encode([app(CurrentSchool::class)->id(), $operator, $currency, $count->counted_on, $throughCount], JSON_THROW_ON_ERROR), (string) config('app.key'));
                $stale += hash_equals($count->source_hash, $hash) ? 0 : 1;
                $shortages += $count->getAttribute('variance_cents') < 0 ? 1 : 0;
            }
            $negative = count(array_filter($custody, fn ($cents) => $cents < 0));
            $difference = array_sum($custody) - $flow['cash_asset_cents'];
            $reports[] = ['currency' => $currency, 'cash_asset_cents' => $flow['cash_asset_cents'], 'custody_difference_cents' => $difference, 'negative_custodians' => $negative, 'unattributed_cash_postings' => $flow['unattributed_cash_postings'], 'cash_record_mismatches' => $flow['cash_record_mismatches'], 'cash_projection_differences' => $flow['cash_projection_differences'], 'missing_physical_counts' => $missing, 'stale_physical_counts' => $stale, 'recorded_shortages' => $shortages, 'healthy' => CashFlows::healthy($flow) && $difference === 0 && $negative === 0];
        }

        return ['cash_currencies' => $reports, 'cash_control_healthy' => ! in_array(false, array_column($reports, 'healthy'), true)];
    }
}
