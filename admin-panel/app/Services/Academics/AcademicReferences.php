<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AcademicRatingLevel;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\User;
use App\Policies\Academics\ClassGradePolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
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
            $class = ClassSchool::query()->lockForUpdate()->findOrFail($classId);
            app(ClassGradePolicy::class)->authorise($actor, edit: true);
            $gradeId = $data['grade_level_id'] === null ? null : (int) $data['grade_level_id'];
            $before = $class->grade_level_id;
            if ($before !== $gradeId) {
                $class->grade_level_id = $gradeId;
                $class->save();
                Audit::record('academics.class_grade_mapped', $class,
                    ['grade_level_id' => $before], ['grade_level_id' => $gradeId]);
            }

            return $class->load('gradeLevel');
        });
    }
}
