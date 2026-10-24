<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AcademicExamLink;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Term;

/** Official released snapshots project into the existing app's exam contracts. */
class MarkExamProjection
{
    public static bool $writing = false;

    public function project(TeachingGroup $group, MarkRelease $release): void
    {
        $previous = self::$writing;
        self::$writing = true;
        try {
            $this->write($group, $release);
        } finally {
            self::$writing = $previous;
        }
    }

    public function assertManual(int $examId): void
    {
        abort_if(AcademicExamLink::where('exam_id', $examId)->exists(), 409, __('These results come from assessment marks. Use mark capture and release.'));
    }

    private function write(TeachingGroup $group, MarkRelease $release): void
    {
        if ($release->mode !== 'official') {
            return;
        }
        $link = AcademicExamLink::where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->where('session_year_id', $group->session_year_id)->where('period_key', $release->period_key)->first();
        $term = $release->term_id === null ? null : Term::findOrFail($release->term_id);
        $year = SessionYear::findOrFail($group->session_year_id);
        $end = $term?->end_date->toDateString() ?? $year->end_date;
        $date = substr((string) $end, 0, 10);
        $exam = $link === null ? new Exam : Exam::findOrFail($link->exam_id);
        $exam->forceFill(['name' => mb_substr(Subject::findOrFail($group->subject_id)->name.' — '.($term?->name ?? __('Year mark')), 0, 128),
            'description' => __('Released assessment results'), 'session_year_id' => $group->session_year_id, 'term_id' => $release->term_id, 'publish' => 1])->save();
        $link ??= new AcademicExamLink;
        $link->fill(['subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id, 'session_year_id' => $group->session_year_id,
            'term_id' => $release->term_id, 'period_key' => $release->period_key, 'exam_id' => $exam->id, 'mark_release_id' => $release->id])->save();
        foreach ($release->entries()->with('snapshot')->get() as $entry) {
            $snapshot = $entry->snapshot;
            // Last dated placement in this grade by the period end, including leavers.
            $enrolment = Enrolment::where('learner_id', $entry->learner_id)->where('session_year_id', $group->session_year_id)
                ->whereDate('starts_on', '<=', $date)->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $group->grade_level_id))
                ->orderByDesc('starts_on')->orderByDesc('id')->firstOrFail();
            $section = ClassSection::findOrFail($enrolment->class_section_id);
            $classId = (int) $section->class_id;
            if (! ExamClass::where('exam_id', $exam->id)->where('class_id', $classId)->exists()) {
                (new ExamClass)->forceFill(['exam_id' => $exam->id, 'class_id' => $classId])->save();
            }
            $timetable = ExamTimetable::where('exam_id', $exam->id)->where('class_id', $classId)->where('subject_id', $group->subject_id)->first() ?? (new ExamTimetable)->forceFill(['exam_id' => $exam->id, 'class_id' => $classId, 'subject_id' => $group->subject_id]);
            $timetable->forceFill(['total_marks' => 100, 'passing_marks' => null, 'date' => $date, 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'session_year_id' => $group->session_year_id])->save();
            // Remove only this learner's previous placement projection, never other released learners.
            $oldTimetables = ExamTimetable::where('exam_id', $exam->id)->whereKeyNot($timetable->id)->pluck('id');
            ExamMarks::whereIn('exam_timetable_id', $oldTimetables)->where('student_id', $entry->learner_id)->delete();
            $bp = (int) $snapshot->percentage_bp;
            $mark = ExamMarks::where('exam_timetable_id', $timetable->id)->where('subject_id', $group->subject_id)->where('student_id', $entry->learner_id)->first() ?? (new ExamMarks)->forceFill(['exam_timetable_id' => $timetable->id, 'subject_id' => $group->subject_id, 'student_id' => $entry->learner_id]);
            $mark->forceFill(['obtained_marks' => intdiv($bp, 100), 'exact_obtained_units' => $bp, 'passing_status' => null,
                'grade' => (string) $snapshot->rating_level, 'session_year_id' => $group->session_year_id, 'teacher_review' => null])->save();
            $result = ExamResult::where('exam_id', $exam->id)->where('student_id', $entry->learner_id)->first() ?? (new ExamResult)->forceFill(['exam_id' => $exam->id, 'student_id' => $entry->learner_id]);
            $result->forceFill(['class_section_id' => $section->id, 'total_marks' => 100, 'obtained_marks' => intdiv($bp, 100), 'exact_total_units' => 10000,
                'exact_obtained_units' => $bp, 'percentage' => AcademicNumbers::decimal($bp), 'grade' => (string) $snapshot->rating_level, 'session_year_id' => $group->session_year_id])->save();
        }
    }
}
