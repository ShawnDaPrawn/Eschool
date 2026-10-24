<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Term;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Existing exam writes share exact arithmetic, scope, lock and audit rules. */
class LegacyMarks
{
    public function capture(User $actor, array $rows, bool $correction = false): bool
    {
        $fresh = app(AcademicAuthority::class)->staff($actor);
        abort_if(count($rows) < 1 || count($rows) > 1000, 422);

        return DB::transaction(function () use ($fresh, $rows, $correction) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $prepared = [];
            $exams = [];
            foreach ($rows as $row) {
                $timetable = ExamTimetable::with('exam')->lockForUpdate()->findOrFail($row['timetable_id']);
                $exam = $timetable->exam;
                $exams[(int) $exam->id] = $exam;
                app(MarkExamProjection::class)->assertManual((int) $exam->id);
                $this->unlocked($exam);
                abort_if(! $correction && (int) $exam->publish === 1, 409, __('This exam is published.'));
                $learner = Students::findOrFail($row['learner_id']);
                $section = StudentSessions::where('student_id', $learner->id)->where('session_year_id', $exam->session_year_id)
                    ->whereHas('class_section', fn ($q) => $q->where('class_id', $timetable->class_id))->value('class_section_id');
                abort_if($section === null, 403);
                $policy = app(ProfilePolicy::class);
                $teachers = app(AcademicAuthority::class)->teachers($fresh);
                $assigned = ClassTeacher::where('class_section_id', $section)->where('session_year_id', $exam->session_year_id)
                    ->forTermOrDated($exam->term_id)->whereIn('class_teacher_id', $teachers)->exists()
                    || SubjectTeacher::where('class_section_id', $section)->where('session_year_id', $exam->session_year_id)
                        ->forTermOrDated($exam->term_id)->where('subject_id', $timetable->subject_id)->whereIn('teacher_id', $teachers)->exists();
                $teacherProfile = in_array('Teacher', $policy->roles($fresh), true);
                abort_unless($assigned || (! $teacherProfile && $policy->permission($fresh, 'exam-upload-marks')), 403);
                $units = AcademicNumbers::units((string) $row['mark'], 'obtained_marks', (int) $timetable->total_marks * 100);
                $bp = app(MarkCalculator::class)->percentage($units, (int) $timetable->total_marks * 100, 'floor');
                $grade = findExamGrade($timetable->session_year_id, intdiv($bp, 100));
                if ($grade == null) {
                    throw ValidationException::withMessages(['grade' => __('grades_data_does_not_exists')]);
                }
                $prepared[] = [$timetable, $learner, $units, $grade, $row['teacher_review'] ?? null];
            }
            $updated = false;
            foreach ($prepared as [$timetable, $learner, $units, $grade, $review]) {
                $mark = ExamMarks::where('exam_timetable_id', $timetable->id)->where('student_id', $learner->id)->where('subject_id', $timetable->subject_id)->lockForUpdate()->first() ?? new ExamMarks;
                $updated = $updated || $mark->exists;
                $before = $mark->exists ? $mark->toArray() + ['exact_mark_units' => (int) ($mark->exact_obtained_units ?? (int) $mark->obtained_marks * 100)] : [];
                $mark->forceFill(['exam_timetable_id' => $timetable->id, 'student_id' => $learner->id, 'subject_id' => $timetable->subject_id,
                    'obtained_marks' => intdiv($units + 50, 100), 'exact_obtained_units' => $units, 'passing_status' => $units >= (int) $timetable->passing_marks * 100 ? 1 : 0,
                    'grade' => $grade, 'session_year_id' => $timetable->session_year_id])->save();
                Audit::record('academics.legacy_mark_captured', $mark, $before, $mark->toArray() + ['exact_mark_units' => $units]);
            }
            foreach ($exams as $exam) {
                if ((int) $exam->publish === 1) {
                    $this->results($exam);
                }
            }

            return $updated;
        }, 3);
    }

    public function publish(User $actor, int $examId): void
    {
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_unless($policy->permission($fresh, 'exam-create'), 403);
        DB::transaction(function () use ($examId) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $exam = Exam::lockForUpdate()->findOrFail($examId);
            app(MarkExamProjection::class)->assertManual($examId);
            $timetables = ExamTimetable::where('exam_id', $examId)->get();
            abort_if($timetables->isEmpty() || $timetables->max('date') >= app(AcademicAuthority::class)->today(), 409, __('exam_not_completed_yet'));
            abort_if((int) $exam->publish === 0 && ExamTimetable::where('exam_id', $examId)->whereDoesntHave('exam_marks')->exists(), 409, __('marks_are_not_submitted'));
            if ((int) $exam->publish === 1) {
                ExamResult::where('exam_id', $examId)->delete();
                $exam->publish = 0;
            } else {
                $this->results($exam);
                $exam->publish = 1;
            }
            $exam->save();
            Audit::record('academics.legacy_results_published', $exam, [], ['published' => (bool) $exam->publish]);
        }, 3);
    }

    private function unlocked(Exam $exam): void
    {
        if ($exam->term_id === null) {
            return;
        }
        $term = Term::lockForUpdate()->findOrFail($exam->term_id);
        abort_if($term->marks_lock_on !== null && app(AcademicAuthority::class)->today() >= $term->marks_lock_on->toDateString(), 409, __('This term is locked.'));
    }

    private function results(Exam $exam): void
    {
        $marks = ExamMarks::whereHas('timetable', fn ($q) => $q->where('exam_id', $exam->id))->with('timetable')->get()->groupBy('student_id');
        abort_if($marks->isEmpty(), 409, __('marks_are_not_submitted'));
        foreach ($marks as $learnerId => $rows) {
            $total = $rows->sum(fn ($m) => (int) $m->timetable->total_marks * 100);
            $obtained = $rows->sum(fn ($m) => (int) ($m->exact_obtained_units ?? (int) $m->getRawOriginal('obtained_marks') * 100));
            $bp = app(MarkCalculator::class)->percentage($obtained, $total);
            $floor = app(MarkCalculator::class)->percentage($obtained, $total, 'floor');
            $grade = findExamGrade($exam->session_year_id, intdiv($floor, 100));
            if ($grade == null) {
                throw ValidationException::withMessages(['grade' => __('grades_data_does_not_exists')]);
            }
            $section = StudentSessions::where('student_id', $learnerId)->where('session_year_id', $exam->session_year_id)->value('class_section_id');
            abort_if($section === null, 409, __('Student session not found.'));
            $result = ExamResult::where('exam_id', $exam->id)->where('student_id', $learnerId)->lockForUpdate()->first() ?? new ExamResult;
            $result->forceFill(['exam_id' => $exam->id, 'student_id' => $learnerId, 'class_section_id' => $section, 'session_year_id' => $exam->session_year_id,
                'total_marks' => intdiv($total, 100), 'obtained_marks' => intdiv($obtained + 50, 100), 'exact_total_units' => $total, 'exact_obtained_units' => $obtained,
                'percentage' => AcademicNumbers::decimal($bp), 'grade' => $grade])->save();
        }
    }
}
