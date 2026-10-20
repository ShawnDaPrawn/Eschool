<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\StudentOnlineExamStatus;
use App\Models\StudentSessions;

class ExamReportService
{
    /**
     * Summarised offline-exam statistics for a single student in a session year.
     *
     * An offline exam is counted as PASS only when every subject's
     * exam_marks.passing_status is 1; any failed subject makes the exam a FAIL.
     * "missed" exams are published exams timetabled for the class that the
     * student has no ExamResult for. Returns [] when the student has no session.
     *
     * @return array{
     *   average_percentage: float, avg_grade: string, total_exams: int,
     *   pass_percentage: float, pass_count: int, fail_count: int, missed_count: int,
     *   best_exam_name: string|null, best_exam_percentage: float|null,
     *   worst_exam_name: string|null, worst_exam_percentage: float|null
     * }
     */
    public function getOfflineStatistics(int $student_id, int $session_year_id): array
    {
        $studentSession = StudentSessions::where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)
            ->with('class_section')
            ->first();

        if (! $studentSession || ! $studentSession->class_section) {
            return [];
        }

        $class_id = $studentSession->class_section->class_id;

        // Published offline exams scheduled for this class in the session year.
        $timetabled_exam_ids = ExamTimetable::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->distinct()
            ->pluck('exam_id');

        $assigned_exam_ids = Exam::whereIn('id', $timetabled_exam_ids)
            ->where('session_year_id', $session_year_id)
            ->where('publish', 1)
            ->pluck('id');

        $total_exams = $assigned_exam_ids->count();

        if ($total_exams === 0) {
            return $this->emptyOfflineStats();
        }

        $results = ExamResult::with('exam:id,name')
            ->where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)
            ->whereIn('exam_id', $assigned_exam_ids)
            ->get();

        $pass_count = 0;
        $percentages = [];
        $best = null;
        $worst = null;

        foreach ($results as $result) {
            $percentage = (float) $result->percentage;
            $percentages[] = $percentage;

            if ($this->offlineExamPassed($student_id, (int) $result->exam_id, $class_id, $session_year_id)) {
                $pass_count++;
            }

            $name = $result->exam->name ?? null;
            if ($best === null || $percentage > $best['percentage']) {
                $best = ['name' => $name, 'percentage' => $percentage];
            }
            if ($worst === null || $percentage < $worst['percentage']) {
                $worst = ['name' => $name, 'percentage' => $percentage];
            }
        }

        $attempted_count = $results->count();
        $average_percentage = $percentages
            ? round(array_sum($percentages) / count($percentages), 1)
            : 0.0;

        return [
            'average_percentage' => $average_percentage,
            'avg_grade' => $percentages ? (findExamGrade($session_year_id, $average_percentage) ?? '') : '',
            'total_exams' => $total_exams,
            'pass_percentage' => round($pass_count / $total_exams * 100, 1),
            'pass_count' => $pass_count,
            'fail_count' => $attempted_count - $pass_count,
            'missed_count' => $total_exams - $attempted_count,
            'best_exam_name' => $best['name'] ?? null,
            'best_exam_percentage' => $best['percentage'] ?? null,
            'worst_exam_name' => $worst['name'] ?? null,
            'worst_exam_percentage' => $worst['percentage'] ?? null,
        ];
    }

    /**
     * Summarised online-exam statistics for a single student in a session year.
     *
     * Online exams have no pass/fail and no grade: pass and fail are consolidated
     * into attempted_count, and "missed" exams are assigned exams with no attempt.
     * Returns [] when the student has no session.
     *
     * @return array{
     *   average_percentage: float, total_exams: int, attempted_percentage: float,
     *   attempted_count: int, missed_count: int,
     *   best_exam_name: string|null, best_exam_percentage: float|null,
     *   worst_exam_name: string|null, worst_exam_percentage: float|null
     * }
     */
    public function getOnlineStatistics(int $student_id, int $session_year_id, ?int $subject_id = null): array
    {
        $studentSession = StudentSessions::where('student_id', $student_id)
            ->where('session_year_id', $session_year_id)
            ->with('class_section')
            ->first();

        if (! $studentSession || ! $studentSession->class_section) {
            return [];
        }

        $class_section_id = $studentSession->class_section_id;
        $class_id = $studentSession->class_section->class_id;

        // Online exams assigned to the class-section or the whole class in this session year.
        $assigned_exams = OnlineExam::where('session_year_id', $session_year_id)
            ->where(function ($q) use ($class_section_id, $class_id) {
                $q->where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id])
                    ->orWhere(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id]);
            })
            ->when(! empty($subject_id), fn ($q) => $q->where('subject_id', $subject_id))
            ->has('question_choice')
            ->get(['id', 'title']);

        $total_exams = $assigned_exams->count();

        if ($total_exams === 0) {
            return $this->emptyOnlineStats();
        }

        $attempted_exam_ids = StudentOnlineExamStatus::where('student_id', $student_id)
            ->whereIn('online_exam_id', $assigned_exams->pluck('id'))
            ->pluck('online_exam_id')
            ->unique();

        $percentages = [];
        $best = null;
        $worst = null;

        foreach ($assigned_exams as $exam) {
            if (! $attempted_exam_ids->contains($exam->id)) {
                continue;
            }

            $percentage = $this->onlineExamPercentage($student_id, (int) $exam->id);
            $percentages[] = $percentage;

            if ($best === null || $percentage > $best['percentage']) {
                $best = ['name' => $exam->title, 'percentage' => $percentage];
            }
            if ($worst === null || $percentage < $worst['percentage']) {
                $worst = ['name' => $exam->title, 'percentage' => $percentage];
            }
        }

        $attempted_count = count($percentages);
        $average_percentage = $percentages
            ? round(array_sum($percentages) / count($percentages), 1)
            : 0.0;

        return [
            'average_percentage' => $average_percentage,
            'total_exams' => $total_exams,
            'attempted_percentage' => round($attempted_count / $total_exams * 100, 1),
            'attempted_count' => $attempted_count,
            'missed_count' => $total_exams - $attempted_count,
            'best_exam_name' => $best['name'] ?? null,
            'best_exam_percentage' => $best['percentage'] ?? null,
            'worst_exam_name' => $worst['name'] ?? null,
            'worst_exam_percentage' => $worst['percentage'] ?? null,
        ];
    }

    /**
     * An offline exam is passed only when the student passed every subject.
     */
    private function offlineExamPassed(int $student_id, int $exam_id, int $class_id, int $session_year_id): bool
    {
        $timetable_ids = ExamTimetable::where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('id');

        $marks = ExamMarks::whereIn('exam_timetable_id', $timetable_ids)
            ->where('student_id', $student_id)
            ->get();

        if ($marks->isEmpty()) {
            return false;
        }

        foreach ($marks as $mark) {
            if ((int) $mark->passing_status !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Percentage scored by the student in an online exam: a question counts
     * only when exactly its correct options were picked (OnlineExamScoringService).
     */
    private function onlineExamPercentage(int $student_id, int $online_exam_id): float
    {
        $score = app(OnlineExamScoringService::class)->score($student_id, $online_exam_id);

        $obtained_marks = (float) $score['obtained_marks'];
        $total_marks = (float) $score['total_marks'];

        if ($total_marks <= 0) {
            return 0.0;
        }

        return round($obtained_marks / $total_marks * 100, 1);
    }

    private function emptyOfflineStats(): array
    {
        return [
            'average_percentage' => 0.0,
            'avg_grade' => '',
            'total_exams' => 0,
            'pass_percentage' => 0.0,
            'pass_count' => 0,
            'fail_count' => 0,
            'missed_count' => 0,
            'best_exam_name' => null,
            'best_exam_percentage' => null,
            'worst_exam_name' => null,
            'worst_exam_percentage' => null,
        ];
    }

    private function emptyOnlineStats(): array
    {
        return [
            'average_percentage' => 0.0,
            'total_exams' => 0,
            'attempted_percentage' => 0.0,
            'attempted_count' => 0,
            'missed_count' => 0,
            'best_exam_name' => null,
            'best_exam_percentage' => null,
            'worst_exam_name' => null,
            'worst_exam_percentage' => null,
        ];
    }
}
