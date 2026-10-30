<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Academics\TeachingGroup;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TeachingGroups;
use Illuminate\Support\Facades\DB;

/**
 * Copies the year's teaching groups (subject, grade, name and homeroom) into
 * the new year, with nobody in them: learners and teachers are allocated
 * afresh for the new year. A group the new year already has (same subject,
 * grade and name) is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferTeachingGroups
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(AcademicAuthority::class)->manage($actor);

        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId) {
            $groups = TeachingGroup::query()->where('session_year_id', $fromSessionId)->where('active', true)->orderBy('id')->get();
            foreach ($groups as $group) {
                $exists = TeachingGroup::query()->where('session_year_id', $toSessionId)->where('subject_id', $group->subject_id)
                    ->where('grade_level_id', $group->grade_level_id)->where('name', $group->name)->exists();
                if ($exists) {
                    continue;
                }
                app(TeachingGroups::class)->save($actor, [
                    'name' => $group->name, 'subject_id' => (int) $group->subject_id, 'grade_level_id' => (int) $group->grade_level_id,
                    'session_year_id' => $toSessionId, 'class_section_id' => $group->class_section_id === null ? null : (int) $group->class_section_id,
                    'active' => true,
                ]);
            }
        });
    }
}
