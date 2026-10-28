<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Internal projections; callers apply fresh Finance or own-parent authority. */
final class FeeRefundHistory
{
    public function held(): Builder
    {
        return DB::table('fee_refunds as rr')->join('fee_refund_actions as ra', fn ($j) => $j->on('ra.school_id', '=', 'rr.school_id')->on('ra.fee_refund_id', '=', 'rr.id')->where('ra.action', 'reserved'))
            ->leftJoin('fee_refund_actions as rp', fn ($j) => $j->on('rp.school_id', '=', 'rr.school_id')->on('rp.fee_refund_id', '=', 'rr.id')->where('rp.action', 'paid'))
            ->where('rr.school_id', app(CurrentSchool::class)->id())->whereNotExists(fn ($q) => $q->selectRaw('1')->from('fee_refund_actions as rc')->whereColumn('rc.school_id', 'rr.school_id')->whereColumn('rc.fee_refund_id', 'rr.id')->where('rc.action', 'cancelled'));
    }

    public function forPayments(?array $ids = null): array
    {
        $rows = $this->held()->when($ids !== null, fn ($q) => $q->whereIn('rr.payment_id', $ids))->selectRaw('rr.payment_id,SUM(CASE WHEN rp.id IS NULL THEN rr.amount_cents ELSE 0 END) AS pending,SUM(CASE WHEN rp.id IS NOT NULL THEN rr.amount_cents ELSE 0 END) AS paid')->groupBy('rr.payment_id')->get();
        $out = [];
        foreach ($rows as $row) {
            $pending = filter_var($row->pending, FILTER_VALIDATE_INT);
            $paid = filter_var($row->paid, FILTER_VALIDATE_INT);
            abort_unless($pending !== false && $paid !== false && $pending >= 0 && $paid >= 0 && $pending <= FeePlans::MAX_CENTS - $paid, 409, 'Refund history needs reconciliation.');
            $out[(int) $row->payment_id] = ['pending_refund_cents' => $pending, 'refunded_cents' => $paid, 'held_cents' => $pending + $paid];
        }

        return $out;
    }

    public function heldForPayment(int $payment): int
    {
        return $this->forPayments([$payment])[$payment]['held_cents'] ?? 0;
    }

    public function heldForCredit(int $account): int
    {
        return (int) $this->held()->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'rr.school_id')->on('p.id', '=', 'rr.payment_id'))->where('p.credit_account_id', $account)->sum('rr.amount_cents');
    }
}
