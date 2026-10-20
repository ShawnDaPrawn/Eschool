<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Leave;
use App\Models\StudentSessions;

class LeaveReportService
{
    /**
     * Summarised leave statistics for a single student in a session year.
     *
     * Counts are leave requests (records), not leave days. Leave status:
     * 0 = Pending, 1 = Approved/Completed, 2 = Rejected.
     * Returns [] when the student has no session in the given session year.
     *
     * @return array{total_leaves: int, completed: int, pending: int, rejected: int}
     */
    public function getStatistics(int $student_id, int $session_year_id): array
    {
        $studentSession = StudentSessions::where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)
            ->with('student')
            ->first();

        if (! $studentSession || ! $studentSession->student) {
            return [];
        }

        $leaves = Leave::where('user_id', $studentSession->student->user_id)
            ->where('session_year_id', $session_year_id)
            ->get(['id', 'status']);

        return [
            'total_leaves' => $leaves->count(),
            'completed' => $leaves->where('status', 1)->count(),
            'pending' => $leaves->where('status', 0)->count(),
            'rejected' => $leaves->where('status', 2)->count(),
        ];
    }
}
