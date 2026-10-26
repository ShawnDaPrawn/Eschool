<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\AcademicExamLink;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSection;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use Illuminate\Database\Eloquent\Builder;

class ExamCoachDates
{
    private function query(User $actor, TeachingGroup $group): Builder
    {
        app(ExamCoachAccess::class)->learnerGroup($actor, (int) $group->id);
        $learner = app(ExamCoachAccess::class)->learner($actor);
        $today = app(AcademicAuthority::class)->today();
        $sections = Enrolment::where('learner_id', $learner->id)->where('session_year_id', $group->session_year_id)
            ->onDate($today)->pluck('class_section_id');
        $classes = ClassSection::whereIn('id', $sections)->whereHas('class', fn ($q) => $q->where('grade_level_id', $group->grade_level_id))->pluck('class_id');

        return ExamTimetable::where('subject_id', $group->subject_id)->where('session_year_id', $group->session_year_id)->whereIn('class_id', $classes)
            ->whereDate('date', '>', $today)->whereDate('date', '<=', $group->year->end_date)
            ->whereHas('exam', fn ($q) => $q->where('publish', 1)->where('session_year_id', $group->session_year_id))
            ->whereNotIn('exam_id', AcademicExamLink::select('exam_id'));
    }

    public function choices(User $actor, int $groupId): array
    {
        $group = app(ExamCoachAccess::class)->learnerGroup($actor, $groupId);

        return $this->query($actor, $group)->with('exam')->orderBy('date')->orderBy('id')->limit(100)->get()->map(fn ($row) => [
            'id' => (int) $row->id, 'name' => $row->exam->name, 'exam_on' => substr((string) $row->date, 0, 10),
            'schedule_signature' => $this->signature($row),
        ])->all();
    }

    public function row(User $actor, int $groupId, int $id): ExamTimetable
    {
        $group = app(ExamCoachAccess::class)->learnerGroup($actor, $groupId);

        return $this->query($actor, $group)->findOrFail($id);
    }

    public function signature(ExamTimetable $row): string
    {
        return hash('sha256', json_encode($row->only('id', 'school_id', 'exam_id', 'class_id', 'subject_id', 'session_year_id', 'date', 'start_time', 'end_time'), JSON_THROW_ON_ERROR));
    }
}
