<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\SessionYear;
use App\Models\StudentSessions;
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

        $collected = round($this->collected($sessionYearId), 2);
        [$upcoming, $overdue] = $this->outstandingByDueDate($sessionYearId, $sessionYear);
        $upcoming = round($upcoming, 2);
        $overdue = round($overdue, 2);

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
     */
    private function collected(int $sessionYearId): float
    {
        $allPaid = (float) FeesPaid::where('session_year_id', $sessionYearId)->sum('total_amount');

        $optionalPaid = (float) FeesChoiceable::where('session_year_id', $sessionYearId)
            ->where('status', 1)
            ->where('is_due_charges', 0)
            ->sum('total_amount');

        return max(0.0, $allPaid);

        return max(0.0, $allPaid - $optionalPaid);
    }

    /**
     * Walk every enrolled student, derive their outstanding compulsory balance and
     * bucket it into [upcoming, overdue] by installment (or session) due date.
     *
     * @return array{0:float,1:float}
     */
    private function outstandingByDueDate(int $sessionYearId, ?SessionYear $sessionYear): array
    {
        // Compulsory fee catalog total per class.
        $baseByClass = FeesClass::where('session_year_id', $sessionYearId)
            ->where('choiceable', 0)
            ->groupBy('class_id')
            ->selectRaw('class_id, SUM(amount) as total')
            ->pluck('total', 'class_id');

        if ($baseByClass->isEmpty()) {
            return [0.0, 0.0];
        }

        // Compulsory paid per student = (all paid) - (optional paid). Mirrors collected().
        $allPaidByStudent = FeesPaid::where('session_year_id', $sessionYearId)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(total_amount) as total')
            ->pluck('total', 'student_id');

        $optionalPaidByStudent = FeesChoiceable::where('session_year_id', $sessionYearId)
            ->where('status', 1)
            ->where('is_due_charges', 0)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(total_amount) as total')
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
        $sessionDueChargesPct = (float) ($sessionYear->fee_due_charges ?? 0);
        $now = Carbon::now();

        $upcoming = 0.0;
        $overdue = 0.0;

        foreach ($students as $student) {
            $base = (float) ($baseByClass[$student->class_id] ?? 0);
            if ($base <= 0) {
                continue;
            }

            $paid = (float) ($allPaidByStudent[$student->student_id] ?? 0)
                - (float) ($optionalPaidByStudent[$student->student_id] ?? 0);

            $outstanding = max(0.0, $base - $paid);
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
                // Even split of the remaining balance across unpaid installments (see class doc).
                $share = $outstanding / count($unpaidInstallments);
                foreach ($unpaidInstallments as $installment) {
                    $dueDate = Carbon::parse($installment->due_date)->endOfDay();
                    if ($now->gt($dueDate)) {
                        $overdue += $share + $share * ((float) $installment->due_charges / 100);
                    } else {
                        $upcoming += $share;
                    }
                }
            } elseif ($sessionDueDate && $now->gt($sessionDueDate)) {
                // No installment schedule (or all settled): one due date governs the lot.
                $overdue += $outstanding + $outstanding * ($sessionDueChargesPct / 100);
            } else {
                $upcoming += $outstanding;
            }
        }

        return [$upcoming, $overdue];
    }
}
