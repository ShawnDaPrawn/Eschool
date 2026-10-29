<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\CashupCount;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\CashupPolicy;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class Cashups
{
    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'currency' => ['required', 'string', 'regex:/\\A[A-Z]{3}\\z/D'], 'counted_on' => ['required', 'date_format:Y-m-d'], 'revision' => ['required', 'integer', 'min:0'], 'source_hash' => ['required', 'string', 'regex:/\\A[a-f0-9]{64}\\z/D'], 'counted_amount' => ['required', 'string', 'regex:/\\A\\d{1,10}(?:\\.\\d{1,2})?\\z/D'], 'private_evidence' => ['required', 'string', 'min:10', 'max:4000'], 'private_reason' => ['nullable', 'string', 'min:10', 'max:4000'], 'confirmed_actual_count' => ['required', 'accepted']];
    }

    public function currencies(): array
    {
        return DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->distinct()->orderBy('currency')->pluck('currency')->all();
    }

    public function report(User $actor, int $operator, string $currency, string $date): array
    {
        return DB::transaction(function () use ($actor, $operator, $currency, $date): array {
            app(CashupPolicy::class)->finance($actor);
            $data = Validator::make(['currency' => $currency, 'counted_on' => $date], ['currency' => self::rules()['currency'], 'counted_on' => self::rules()['counted_on']])->validate();
            abort_unless(in_array($data['currency'], $this->currencies(), true), 422, 'Choose a currency already recorded for this school.');
            abort_unless($date <= now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString(), 422, 'Choose an actual cash count date, no later than today.');
            $current = CashupCount::where('operator_id', $operator)->where('currency', $currency)->where('counted_on', $date)->orderByDesc('revision')->first();
            $flows = app(CashFlows::class)->inspect($currency);
            $opening = $receipts = $payouts = 0;
            $events = [];
            foreach ($flows['events'] as $e) {
                if ($e['operator_id'] !== $operator || $e['effective_on'] > $date) {
                    continue;
                }$events[] = $e;
                if ($e['effective_on'] < $date) {
                    $opening += $e['amount_cents'];
                } elseif ($e['amount_cents'] > 0) {
                    $receipts += $e['amount_cents'];
                } else {
                    $payouts -= $e['amount_cents'];
                }
            }
            // Finance reviews actual custodians; ordinary school membership is not a cash record.
            $holder = $operator === (int) $actor->id || CashupCount::where('operator_id', $operator)->exists();
            foreach ($flows['events'] as $event) {
                if ($event['operator_id'] === $operator) {
                    $holder = true;
                }
            }
            abort_unless($holder, 404);
            $hash = hash_hmac('sha256', json_encode([app(CurrentSchool::class)->id(), $operator, $currency, $date, $events], JSON_THROW_ON_ERROR), (string) config('app.key'));

            return ['school_id' => app(CurrentSchool::class)->id(), 'operator_id' => $operator, 'currency' => $currency, 'counted_on' => $date, 'opening_cents' => $opening, 'receipts_cents' => $receipts, 'payouts_cents' => $payouts, 'closing_cents' => $opening + $receipts - $payouts, 'events' => array_values(array_filter($events, fn ($e) => $e['effective_on'] === $date)), 'source_hash' => $hash, 'cash_asset_cents' => $flows['cash_asset_cents'], 'unattributed_cash_postings' => $flows['unattributed_cash_postings'], 'cash_record_mismatches' => $flows['cash_record_mismatches'], 'cash_projection_differences' => $flows['cash_projection_differences'], 'healthy' => CashFlows::healthy($flows), 'latest' => $current, 'needs_review' => $current !== null && ! hash_equals($current->source_hash, $hash)];
        });
    }

    public function record(User $actor, array $input): CashupCount
    {
        $data = Validator::make($input, self::rules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['confirmed_actual_count'] = true;
        $cents = Money::fromDecimalString($data['counted_amount'], $data['currency'])->cents;
        abort_if($cents > 100000000000, 422, 'Use a supported physical cash count.');
        $hash = hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $data, $hash, $cents): CashupCount {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(CashupPolicy::class)->finance($actor);
            $prior = CashupCount::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless($prior->operator_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This count request already records another original count.');

                return $prior;
            }
            $report = $this->report($actor, (int) $actor->id, $data['currency'], $data['counted_on']);
            abort_unless($report['healthy'], 409, 'Reconcile actual cash postings and projections before recording this cash-up.');
            abort_unless(hash_equals($report['source_hash'], $data['source_hash']), 409, 'Cash receipts or payouts changed. Refresh the report and review the physical count again.');
            abort_unless(($report['latest'] === null ? 0 : $report['latest']->revision) === $data['revision'], 409, 'Another count revision was recorded. Review it before correcting this count.');
            $variance = $cents - $report['closing_cents'];
            $reason = $data['private_reason'] ?? null;
            abort_if(($variance !== 0 || $data['revision'] > 0) && trim((string) $reason) === '', 422, 'Explain this difference or correction. The original ledger will not be adjusted.');

            $count = CashupCount::create(['operator_id' => $actor->id, 'currency' => $data['currency'], 'counted_on' => $data['counted_on'], 'revision' => $data['revision'] + 1, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'source_hash' => $data['source_hash'], 'opening_cents' => $report['opening_cents'], 'receipts_cents' => $report['receipts_cents'], 'payouts_cents' => $report['payouts_cents'], 'closing_cents' => $report['closing_cents'], 'counted_cents' => $cents, 'variance_cents' => $variance, 'private_evidence' => $data['private_evidence'], 'private_reason' => $reason, 'created_at' => now('UTC')]);
            abort_unless(app(CashCountSources::class)->known($count, app(CashFlows::class)->inspect($data['currency']), true) !== null, 409);

            return $count;
        }, 3);
    }
}
