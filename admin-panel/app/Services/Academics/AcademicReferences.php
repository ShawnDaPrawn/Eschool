<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AcademicRatingLevel;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\ClassGradePolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AcademicReferences
{
    public static function mappingRules(): array
    {
        return ['grade_level_id' => ['present', 'nullable', 'integer', 'exists:grade_levels,id']];
    }

    public function catalogue(User $actor): array
    {
        app(ProfilePolicy::class)->actor($actor);

        return ['grades' => GradeLevel::query()->orderBy('sort_order')->get(),
            'rating_scale' => AcademicRatingLevel::query()->orderBy('level')->get()];
    }

    public function mapping(User $actor, int $classId): ClassSchool
    {
        app(ClassGradePolicy::class)->authorise($actor);

        return ClassSchool::query()->with('gradeLevel')->findOrFail($classId);
    }

    public function map(User $actor, int $classId, array $input): ClassSchool
    {
        app(ClassGradePolicy::class)->authorise($actor, edit: true);
        $data = Validator::make($input, self::mappingRules())->validate();

        return DB::transaction(function () use ($actor, $classId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $class = ClassSchool::query()->lockForUpdate()->findOrFail($classId);
            app(ClassGradePolicy::class)->authorise($actor, edit: true);
            $gradeId = $data['grade_level_id'] === null ? null : (int) $data['grade_level_id'];
            $before = $class->grade_level_id;
            if ($before !== $gradeId) {
                $rooms = ClassSection::withTrashed()->where('class_id', $classId)->select('id');
                $bound = TeachingGroup::withTrashed()->whereIn('class_section_id', $rooms)->exists();
                $memberRooms = Enrolment::query()->whereIn('class_section_id', $rooms)->select('learner_id');
                $bound = $bound || TeachingGroupMembership::withTrashed()->whereIn('learner_id', $memberRooms)->exists();
                abort_if($bound, 409, __('This class grade is used by dated teaching groups. Keep its history and create a new class for a different grade.'));
                $class->grade_level_id = $gradeId;
                $class->save();
                Audit::record('academics.class_grade_mapped', $class,
                    ['grade_level_id' => $before], ['grade_level_id' => $gradeId]);
            }

            return $class->load('gradeLevel');
        });
    }
}
