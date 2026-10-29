<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeePayment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;

/** Gross verified receipts, grouped by currency; held credit is not unpaid debt. */
final class FeeReceiptOverview
{
    public function read(User $actor, int $year): array
    {
        app(MoneyPolicy::class)->authorize($actor, 'fee-payments-manage');
        $session = SessionYear::findOrFail($year);
        $school = app(CurrentSchool::class)->id();
        $totals = FeePayment::query()->whereBetween('received_on', [$session->start_date, $session->end_date])
            ->whereExists(function ($query) use ($school): void {
                $query->selectRaw('1')->from('ledger_entries as e')->where('e.school_id', $school)
                    ->whereColumn('e.id', 'payments.ledger_entry_id')->whereNotNull('e.posted_at');
            })
            ->select('currency')->selectRaw('COUNT(*) AS receipt_count, SUM(amount_cents) AS collected_cents')
            ->groupBy('currency')->orderBy('currency')->get();
        $rows = [];
        foreach ($totals as $total) {
            $cents = filter_var($total->getAttribute('collected_cents'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => LedgerService::MAX_CENTS]]);
            abort_unless($cents !== false && $cents >= 0, 409, 'Receipt totals require reconciliation.');
            $rows[] = ['currency' => $total->currency, 'receipt_count' => (int) $total->getAttribute('receipt_count'),
                'collected_cents' => $cents, 'formatted' => Money::ofCents($cents, $total->currency)->format()];
        }

        return ['rows' => $rows];
    }
}
