<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Fresh school authority and allocated subject/grade/year access for academic work. */
class AcademicAccess
{
    public function actor(User $actor, string $permission): User
    {
        $authority = app(ProfilePolicy::class);
        $fresh = $authority->actor($actor);
        abort_if(array_diff($authority->roles($fresh), ['Parent', 'Student']) === [], 403);
        abort_unless($authority->permission($fresh, $permission), 403);

        return $fresh;
    }

    public function schoolPublisher(User $fresh): bool
    {
        return app(ProfilePolicy::class)->permission($fresh, 'curriculum-publish');
    }

    public function context(User $actor, string $permission, int $subjectId, int $gradeId, int $yearId): User
    {
        $fresh = $this->actor($actor, $permission);
        Subject::query()->findOrFail($subjectId);
        GradeLevel::query()->findOrFail($gradeId);
        SessionYear::query()->findOrFail($yearId);
        if (! $this->schoolPublisher($fresh)) {
            $teacherIds = Teacher::query()->where('user_id', $fresh->id)->pluck('id');
            abort_unless($teacherIds->isNotEmpty() && $this->allocation($teacherIds->all(), $subjectId, $gradeId, $yearId)->exists(), 403);
        }

        return $fresh;
    }

    public function constrain(User $actor, Builder $query): Builder
    {
        $fresh = $this->actor($actor, 'curriculum-read');
        if ($this->schoolPublisher($fresh)) {
            return $query;
        }
        $ids = Teacher::query()->where('user_id', $fresh->id)->pluck('id')->all();

        return $query->whereExists(function ($q) use ($ids) {
            $school = app(CurrentSchool::class)->id();
            $q->select(DB::raw('1'))->from('subject_teachers as academic_allocation')
                ->join('class_sections as academic_homeroom', 'academic_homeroom.id', '=', 'academic_allocation.class_section_id')
                ->join('classes as academic_class', 'academic_class.id', '=', 'academic_homeroom.class_id')
                ->where('academic_allocation.school_id', $school)->where('academic_homeroom.school_id', $school)->where('academic_class.school_id', $school)
                ->whereNull('academic_allocation.deleted_at')->whereNull('academic_homeroom.deleted_at')->whereNull('academic_class.deleted_at')
                ->whereIn('academic_allocation.teacher_id', $ids)
                ->whereColumn('academic_allocation.subject_id', 'curriculum_plans.subject_id')
                ->whereColumn('academic_allocation.session_year_id', 'curriculum_plans.session_year_id')
                ->whereColumn('academic_class.grade_level_id', 'curriculum_plans.grade_level_id');
        });
    }

    private function allocation(array $teacherIds, int $subjectId, int $gradeId, int $yearId): \Illuminate\Database\Query\Builder
    {
        $school = app(CurrentSchool::class)->id();

        return DB::table('subject_teachers as academic_allocation')
            ->join('class_sections as academic_homeroom', 'academic_homeroom.id', '=', 'academic_allocation.class_section_id')
            ->join('classes as academic_class', 'academic_class.id', '=', 'academic_homeroom.class_id')
            ->where('academic_allocation.school_id', $school)->where('academic_homeroom.school_id', $school)->where('academic_class.school_id', $school)
            ->whereNull('academic_allocation.deleted_at')->whereNull('academic_homeroom.deleted_at')->whereNull('academic_class.deleted_at')
            ->whereIn('academic_allocation.teacher_id', $teacherIds)->where('academic_allocation.subject_id', $subjectId)
            ->where('academic_allocation.session_year_id', $yearId)->where('academic_class.grade_level_id', $gradeId);
    }
}
