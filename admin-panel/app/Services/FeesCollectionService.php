<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeesCollectionService
{
    /**
     * @return array{currency:string, collected:float, upcoming_dues:float, overdue:float, series:array<int,float>, labels:array<int,string>}
     */
    public function summary(int $sessionYearId): array
    {
        $sessionYear = SessionYear::find($sessionYearId);

        // Worked out in cents; the chart takes currency units.
        $collected = LegacyAmount::fromCentsOrZero($this->collected($sessionYearId));
        [$upcoming, $overdue] = array_map(LegacyAmount::fromCentsOrZero(...), $this->outstandingByDueDate($sessionYearId, $sessionYear));

        return [
            'currency' => getSettings('currency_symbol')['currency_symbol'] ?? '',
            'collected' => $collected,
            'upcoming_dues' => $upcoming,
            'overdue' => $overdue,
            'series' => [$collected, $upcoming, $overdue],
            'labels' => ['collected', 'upcoming_dues', 'overdue_amount'],
        ];
    }

    /**
     * Compulsory fees actually collected this session (principal + due charges),
     * isolated from the shared fees_paids ledger by removing the optional slice.
     * In cents.
     */
    private function collected(int $sessionYearId): int
    {
        $allPaid = (int) FeesPaid::where('session_year_id', $sessionYearId)->sum(DB::raw(CentsColumns::sql('total_amount', 'total_amount_cents')));

        $optionalPaid = (int) FeesChoiceable::where('session_year_id', $sessionYearId)
            ->where('status', 1)
            ->where('is_due_charges', 0)
            ->sum(DB::raw(CentsColumns::sql('total_amount', 'total_amount_cents')));

        return max(0, $allPaid);

        return max(0, $allPaid - $optionalPaid);
    }

    /**
     * Walk every enrolled student, derive their outstanding compulsory balance and
     * bucket it into [upcoming, overdue] by installment (or session) due date.
     * In cents; each student's shares and due charges are rounded once, half up.
     *
     * @return array{0:int,1:int}
     */
    private function outstandingByDueDate(int $sessionYearId, ?SessionYear $sessionYear): array
    {
        // Compulsory fee catalog total per class.
        $baseByClass = FeesClass::where('session_year_id', $sessionYearId)
            ->where('choiceable', 0)
            ->groupBy('class_id')
            ->selectRaw('class_id, SUM('.CentsColumns::sql('amount', 'amount_cents').') as total')
            ->pluck('total', 'class_id');

        if ($baseByClass->isEmpty()) {
            return [0, 0];
        }

        // Compulsory paid per student = (all paid) - (optional paid). Mirrors collected().
        $allPaidByStudent = FeesPaid::where('session_year_id', $sessionYearId)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM('.CentsColumns::sql('total_amount', 'total_amount_cents').') as total')
            ->pluck('total', 'student_id');

        $optionalPaidByStudent = FeesChoiceable::where('session_year_id', $sessionYearId)
            ->where('status', 1)
            ->where('is_due_charges', 0)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM('.CentsColumns::sql('total_amount', 'total_amount_cents').') as total')
            ->pluck('total', 'student_id');

        // Enrolled (active) students and the class they sit in this session.
        $students = StudentSessions::query()
            ->where('student_sessions.session_year_id', $sessionYearId)
            ->where('student_sessions.status', 1)
            ->join('class_sections', 'student_sessions.class_section_id', '=', 'class_sections.id')
            ->selectRaw('student_sessions.student_id as student_id, class_sections.class_id as class_id')
            ->distinct()
            ->get();

        // Raw installment schedule — the query builder bypasses InstallmentFee's
        // display-format due_date accessor so we keep a parseable Y-m-d value.
        $installments = DB::table('installment_fees')
            ->where('session_year_id', $sessionYearId)
            ->whereNull('deleted_at')
            ->orderBy('due_date')
            ->get(['id', 'due_date', 'due_charges']);

        $installmentsEnabled = $installments->isNotEmpty();

        // installment_fee_id set that each student has already paid.
        $paidInstallmentsByStudent = [];
        if ($installmentsEnabled && $installments->isNotEmpty()) {
            foreach (
                PaidInstallmentFee::where('session_year_id', $sessionYearId)
                    ->where('status', 1)
                    ->get(['student_id', 'installment_fee_id']) as $paid
            ) {
                $paidInstallmentsByStudent[$paid->student_id][$paid->installment_fee_id] = true;
            }
        }

        $sessionDueDate = $sessionYear && $sessionYear->fee_due_date
            ? Carbon::parse($sessionYear->fee_due_date)->endOfDay()
            : null;
        $sessionDueChargesPct = (int) ($sessionYear->fee_due_charges ?? 0);
        $now = Carbon::now();

        $upcoming = 0;
        $overdue = 0;

        foreach ($students as $student) {
            $base = (int) ($baseByClass[$student->class_id] ?? 0);
            if ($base <= 0) {
                continue;
            }

            $paid = (int) ($allPaidByStudent[$student->student_id] ?? 0)
                - (int) ($optionalPaidByStudent[$student->student_id] ?? 0);

            $outstanding = max(0, $base - $paid);
            if ($outstanding <= 0) {
                continue;
            }

            // Installments the student has not settled yet.
            $unpaidInstallments = [];
            if ($installmentsEnabled && $installments->isNotEmpty()) {
                $paidSet = $paidInstallmentsByStudent[$student->student_id] ?? [];
                foreach ($installments as $installment) {
                    if (! isset($paidSet[$installment->id])) {
                        $unpaidInstallments[] = $installment;
                    }
                }
            }

            if (! empty($unpaidInstallments)) {
                // Even split of the remaining balance across unpaid installments (see
                // class doc): each share is outstanding / n, kept as a fraction over
                // n * 100 so its due charges (a percentage) stay exact until rounded.
                $denominator = count($unpaidInstallments) * 100;
                $upcomingShare = 0;
                $overdueShare = 0;
                foreach ($unpaidInstallments as $installment) {
                    $dueDate = Carbon::parse($installment->due_date)->endOfDay();
                    if ($now->gt($dueDate)) {
                        $overdueShare += $outstanding * (100 + (int) $installment->due_charges);
                    } else {
                        $upcomingShare += $outstanding * 100;
                    }
                }
                $overdue += Money::ofFraction($overdueShare, $denominator)->cents;
                $upcoming += Money::ofFraction($upcomingShare, $denominator)->cents;
            } elseif ($sessionDueDate && $now->gt($sessionDueDate)) {
                // No installment schedule (or all settled): one due date governs the lot.
                $overdue += Money::ofFraction($outstanding * (100 + $sessionDueChargesPct), 100)->cents;
            } else {
                $upcoming += $outstanding;
            }
        }

        return [$upcoming, $overdue];
    }
}
