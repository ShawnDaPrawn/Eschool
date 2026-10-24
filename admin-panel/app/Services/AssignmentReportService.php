<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assignment;
use App\Models\ClassSessionConfig;
use App\Models\StudentSessions;
use App\Models\Term;
use Carbon\Carbon;

class AssignmentReportService
{
    /**
     * Returns assignment report statistics for a single student in a given
     * session year (optionally scoped to a subject).
     *
     * Submission status: 0 = Pending, 1 = Accepted, 2 = Rejected, 3 = Resubmitted.
     *
     * @return array{
     *   average_completion_rate: float,
     *   total_assignments: int,
     *   pass_count: int,
     *   pending_count: int,
     *   missed_count: int,
     *   on_time_submission: int,
     *   late_submission: int
     * }
     */
    public function getStatistics(int $student_id, int $session_year_id, ?int $subject_id = null): array
    {
        $studentSession = StudentSessions::with('student')
            ->where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)
            ->first();

        if (! $studentSession || ! $studentSession->student) {
            return [];
        }

        $student = $studentSession->student;
        $class_section_id = $studentSession->class_section_id;
        $class_id = $studentSession->class_section->class_id;

        // Resolve the student's enrolled subjects (core + elective), mirroring getAssignments.
        $student_subject = $student->subjects();
        $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
        $elective_subjects = $student_subject['elective_subject'] ?? [];
        if ($elective_subjects) {
            $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
        }
        $subject_ids = array_merge($core_subjects, $elective_subjects);

        $currentTerm = Term::currentForClass((int) $session_year_id, (int) $class_id);
        $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);

        $assignments = Assignment::where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->whereIn('subject_id', $subject_ids)
            ->when($isClassHaveTerm && $currentTerm, function ($query) use ($currentTerm) {
                $query->where('term_id', $currentTerm->id);
            })
            ->when($subject_id, function ($query) use ($subject_id) {
                $query->where('subject_id', $subject_id);
            })
            ->with(['submission' => function ($q) use ($student_id) {
                $q->where('student_id', $student_id);
            }])
            ->get();

        $total = $assignments->count();
        $submitted = 0;
        $pass = 0;
        $pending = 0;
        $missed = 0;
        $on_time = 0;
        $late = 0;
        $now = Carbon::now();

        foreach ($assignments as $assignment) {
            $submission = $assignment->submission->first();

            if ($submission) {
                $submitted++;

                if ((int) $submission->status === 1) {
                    $pass++;
                } elseif (in_array((int) $submission->status, [0, 3], true)) {
                    $pending++;
                }

                if (Carbon::parse($submission->created_at)->lte(Carbon::parse($assignment->due_date))) {
                    $on_time++;
                } else {
                    $late++;
                }
            } elseif (Carbon::parse($assignment->due_date)->lt($now)) {
                $missed++;
            }
        }

        return [
            'average_completion_rate' => $total ? round($submitted / $total * 100, 1) : 0,
            'total_assignments' => $total,
            'pass_count' => $pass,
            'pending_count' => $pending,
            'missed_count' => $missed,
            'on_time_submission' => $on_time,
            'late_submission' => $late,
        ];
    }
}
