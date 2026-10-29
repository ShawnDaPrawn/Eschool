<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\CashupCount;
use App\Models\Money\CashupCountSource;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Retains which immutable source rows existed when the operator counted cash. */
final class CashCountSources
{
    public static function key(array $event): string
    {
        return $event['kind'].':'.$event['record_id'];
    }

    public function known(CashupCount $count, array $flows, bool $retain = false): ?array
    {
        $prior = CashupCountSource::where('count_id', $count->id)->first();
        if ($prior !== null) {
            abort_unless(hash_equals($count->getAttribute('source_hash'), $prior->source_hash), 409);

            return $prior->watermarks;
        }
        $events = array_values(array_filter($flows['events'], fn ($event) => $event['operator_id'] === $count->getAttribute('operator_id') && $event['effective_on'] <= $count->counted_on));
        $hash = hash_hmac('sha256', json_encode([app(CurrentSchool::class)->id(), $count->getAttribute('operator_id'), $count->currency, $count->counted_on, $events], JSON_THROW_ON_ERROR), (string) config('app.key'));
        // Old counts can be adopted only when their exact original source set is
        // still provable. Otherwise Finance reviews a new physical count revision.
        if (! hash_equals($count->getAttribute('source_hash'), $hash)) {
            return null;
        }
        $marks = array_fill_keys(['fee_receipt', 'school_wallet_funding', 'parent_wallet_receipt', 'fee_refund', 'adult_wallet_payout', 'vendor_payout', 'handover_out', 'handover_in'], 0);
        foreach ($events as $event) {
            $marks[$event['kind']] = max($marks[$event['kind']], $flows['event_sequence'][self::key($event)]);
        }
        if ($retain) {
            abort_unless(DB::transactionLevel() > 0, 500);
            CashupCountSource::create(['count_id' => $count->id, 'source_hash' => $hash, 'watermarks' => $marks, 'created_at' => now('UTC')]);
        }

        return $marks;
    }

    public function shortage(CashupCount $count, array $flows, bool $retain = false): ?int
    {
        $marks = $this->known($count, $flows, $retain);
        if ($marks === null) {
            return null;
        }
        $basis = $count->getAttribute('closing_cents');
        foreach ($flows['events'] as $event) {
            $key = self::key($event);
            if ($event['operator_id'] !== $count->getAttribute('operator_id') || $event['effective_on'] > $count->counted_on || $flows['event_sequence'][$key] <= $marks[$event['kind']]) {
                continue;
            }
            // A prior-date source is before the count. A same-day receipt has
            // no actual receipt time, so a fresh physical revision resolves
            // whether it was already counted. Confirmed handovers have their
            // own actual acceptance sequence; later payouts never erase loss.
            if ($event['effective_on'] < $count->counted_on || ($event['amount_cents'] > 0 && $event['kind'] !== 'handover_in')) {
                $basis += $event['amount_cents'];
            }
        }

        return min(0, $count->getAttribute('counted_cents') - $basis);
    }
}
