<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeesClass;
use App\Models\InstallmentFee;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\SessionYear;
use App\Models\StudentSessions;

class FeesReportService
{
    /**
     * Returns fee report statistics for a single student in a given session year.
     *
     * @return array{
     *   total_fee_payable: float,
     *   total_paid: float,
     *   total_pending: float,
     *   last_payment_date: string|null,
     *   next_due_date: string|null,
     *   instalments: array{total:int,completed:int,pending:int}|null
     * }
     */
    public function getStatistics(int $student_id, int $session_year_id): array
    {
        $studentSession = StudentSessions::where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)->first();

        if (! $studentSession) {
            return [];
        }

        $class_id = $studentSession->class_section->class_id;

        $total_fee_payable = (float) FeesClass::where([
            'class_id' => $class_id,
            'choiceable' => 0,
            'session_year_id' => $session_year_id,
        ])->sum('amount');

        // Compulsory (type_of_fee=0) and installment (type_of_fee=1) successful payments only.
        // Summing fees_paids would double-count since the webhook accumulates all payment types
        // (including optional) into a single fees_paids row.
        $total_paid = (float) PaymentTransaction::where([
            'student_id' => $student_id,
            'session_year_id' => $session_year_id,
            'payment_status' => 1,
        ])->whereIn('type_of_fee', [0, 1])->sum('total_amount');

        $total_pending = max(0.0, $total_fee_payable - $total_paid);

        $last = PaymentTransaction::where([
            'student_id' => $student_id,
            'session_year_id' => $session_year_id,
            'payment_status' => 1,
        ])->max('date');
        $last_payment_date = $last ? date('Y-m-d', strtotime($last)) : null;

        $installment_total = InstallmentFee::where('session_year_id', $session_year_id)->count();

        if ($installment_total) {
            $completed = PaidInstallmentFee::where([
                'student_id' => $student_id,
                'session_year_id' => $session_year_id,
                'status' => 1,
            ])->distinct('installment_fee_id')->count('installment_fee_id');

            $instalments = [
                'total' => $installment_total,
                'completed' => $completed,
                'pending' => $installment_total - $completed,
            ];
        } else {
            $instalments = null;
        }

        $next_due_date = $this->resolveNextDueDate(
            $student_id,
            $session_year_id,
            $total_pending,
            $installment_total
        );

        return [
            'total_fee_payable' => $total_fee_payable,
            'total_paid' => $total_paid,
            'total_pending' => $total_pending,
            'last_payment_date' => $last_payment_date,
            'next_due_date' => $next_due_date,
            'instalments' => $instalments,
        ];
    }

    private function resolveNextDueDate(
        int $student_id,
        int $session_year_id,
        float $total_pending,
        int $installment_total
    ): ?string {
        if ($total_pending <= 0) {
            return null;
        }

        if ($installment_total) {
            $paid_ids = PaidInstallmentFee::where([
                'student_id' => $student_id,
                'session_year_id' => $session_year_id,
                'status' => 1,
            ])->pluck('installment_fee_id');

            $next = InstallmentFee::where('session_year_id', $session_year_id)
                ->whereNotIn('id', $paid_ids)
                ->orderBy('due_date')
                ->value('due_date');

            return $next ? date('Y-m-d', strtotime($next)) : null;
        }

        $due = SessionYear::where('id', $session_year_id)->value('fee_due_date');

        return $due ? date('Y-m-d', strtotime($due)) : null;
    }
}
