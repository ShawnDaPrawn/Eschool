<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\CashHandover;
use App\Models\Money\CashHandoverOutcome;
use App\Models\Money\CashupCount;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\CashupPolicy;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Custody moves between people; it never changes the school's cash asset. */
final class CashCustody
{
    public static function proposalRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'recipient_id' => ['required', 'integer', 'min:1'], 'currency' => Cashups::rules()['currency'], 'amount' => ['required', 'string', 'regex:/\\A\\d{1,10}(?:\\.\\d{1,2})?\\z/D'], 'private_evidence' => ['required', 'string', 'min:10', 'max:4000'], 'confirmed' => ['required', 'accepted']];
    }

    public static function outcomeRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'private_evidence' => ['required', 'string', 'min:10', 'max:4000'], 'confirmed' => ['required', 'accepted']];
    }

    /** Internal only: caller holds the school lock and authorizes the affected domain. */
    public function available(int $operator, string $currency, ?string $date = null): array
    {
        $date ??= now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $flows = app(CashFlows::class)->inspect($currency);
        $book = 0;
        foreach ($flows['events'] as $e) {
            if ($e['operator_id'] === $operator && $e['effective_on'] <= $date) {
                $book += $e['amount_cents'];
            }
        }
        $count = CashupCount::where('operator_id', $operator)->where('currency', $currency)->where('counted_on', '<=', $date)->orderByDesc('counted_on')->orderByDesc('revision')->first();
        // A recorded surplus does not create money; an observed shortage reduces usable custody.
        $shortage = min(0, $count?->getAttribute('variance_cents') ?? 0);
        $reserved = (int) CashHandover::where('sender_id', $operator)->where('currency', $currency)->whereNotIn('id', CashHandoverOutcome::select('handover_id'))->sum('amount_cents');

        return ['book_cents' => $book, 'shortage_cents' => $shortage, 'reserved_cents' => $reserved, 'available_cents' => max(0, $book + $shortage - $reserved), 'healthy' => CashFlows::healthy($flows)];
    }

    public function requirePayout(User $actor, string $currency, int $cents, ?string $paidOn = null): void
    {
        School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $paidOn ??= $today;
        Validator::make(['paid_on' => $paidOn], ['paid_on' => ['required', 'date_format:Y-m-d']])->validate();
        abort_if($paidOn > $today, 422, 'Record an actual completed cash payout date.');
        $current = $this->available((int) $actor->id, $currency);
        abort_unless($current['healthy'], 409, 'Reconcile original cash receipts, payouts and projections before paying cash.');
        $minimum = $current['available_cents'];
        if ($paidOn < $today) {
            $daily = [];
            foreach (app(CashFlows::class)->inspect($currency)['events'] as $event) {
                if ($event['operator_id'] === (int) $actor->id && $event['effective_on'] <= $today) {
                    $date = $event['effective_on'];
                    $daily[$date] = ($daily[$date] ?? 0) + $event['amount_cents'];
                }
            }
            $counts = [];
            foreach (CashupCount::where('operator_id', $actor->id)->where('currency', $currency)->where('counted_on', '<=', $today)->orderBy('counted_on')->orderBy('revision')->get() as $count) {
                $counts[$count->counted_on] = min(0, $count->getAttribute('variance_cents'));
            }
            $dates = array_unique([...array_keys($daily), ...array_keys($counts), $paidOn, $today]);
            sort($dates);
            $book = $shortage = 0;
            foreach ($dates as $date) {
                $book += $daily[$date] ?? 0;
                $shortage = $counts[$date] ?? $shortage;
                if ($date >= $paidOn) {
                    $minimum = min($minimum, max(0, $book + $shortage));
                }
            }
        }
        abort_unless($cents > 0 && $minimum >= $cents, 422, 'Your recorded available cash does not cover this payout throughout its actual date interval. Receive a confirmed handover or review your actual cash count first.');
    }

    public function propose(User $actor, array $input): CashHandover
    {
        $data = Validator::make($input, self::proposalRules())->validate();
        $data['recipient_id'] = (int) $data['recipient_id'];
        $data['confirmed'] = true;
        $amount = Money::fromDecimalString($data['amount'], $data['currency'])->cents;
        abort_unless($amount > 0 && $amount <= 100000000000, 422);
        $hash = $this->hash(['proposal', $data]);

        return DB::transaction(function () use ($actor, $data, $amount, $hash): CashHandover {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(CashupPolicy::class)->finance($actor);
            $prior = CashHandover::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless($prior->sender_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This request already records another original handover.');

                return $prior;
            }
            abort_if(CashHandoverOutcome::where('operation_id', $data['operation_id'])->exists(), 409);
            abort_if($data['recipient_id'] === (int) $actor->id, 422, 'Choose another cash operator to receive this handover.');
            $recipient = User::findOrFail($data['recipient_id']);
            app(CashupPolicy::class)->finance($recipient);
            $this->requirePayout($actor, $data['currency'], $amount);
            $available = $this->available((int) $actor->id, $data['currency']);

            return CashHandover::create(['sender_id' => $actor->id, 'recipient_id' => $recipient->id, 'currency' => $data['currency'], 'amount_cents' => $amount, 'available_before_cents' => $available['available_cents'], 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'private_evidence' => $data['private_evidence'], 'created_at' => now('UTC')]);
        }, 3);
    }

    public function finish(User $actor, int $id, string $status, array $input): CashHandoverOutcome
    {
        abort_unless(in_array($status, ['accepted', 'cancelled', 'rejected'], true), 422);
        $data = Validator::make($input, self::outcomeRules())->validate();
        $data['confirmed'] = true;
        $hash = $this->hash([$id, $status, $data]);

        return DB::transaction(function () use ($actor, $id, $status, $data, $hash): CashHandoverOutcome {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(CashupPolicy::class)->finance($actor);
            $row = CashHandover::lockForUpdate()->findOrFail($id);
            abort_unless(($status === 'cancelled' ? $row->sender_id : $row->recipient_id) === (int) $actor->id, 404);
            $prior = CashHandoverOutcome::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless($prior->actor_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This request already records another original handover outcome.');

                return $prior;
            }
            abort_if(CashHandover::where('operation_id', $data['operation_id'])->exists(), 409);
            abort_if(CashHandoverOutcome::where('handover_id', $id)->exists(), 409, 'This handover already has a final outcome.');
            if ($status === 'accepted') {
                $available = $this->available($row->sender_id, $row->currency);
                abort_unless($available['healthy'], 409, 'Reconcile original cash records before confirming this handover.');
                abort_unless($row->amount_cents <= $available['book_cents'] + $available['shortage_cents'] - $available['reserved_cents'] + $row->amount_cents, 409, 'The sender recorded a cash shortage. Reject or cancel this proposal and review the actual cash first.');
            }

            return CashHandoverOutcome::create(['handover_id' => $id, 'actor_id' => $actor->id, 'status' => $status, 'effective_on' => now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString(), 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'private_evidence' => $data['private_evidence'], 'created_at' => now('UTC')]);
        }, 3);
    }

    private function hash(array $data): string
    {
        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
