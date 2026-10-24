<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;

class MarksPolicy
{
    public function group(User $actor, int $groupId, string $operation): TeachingGroup
    {
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        $group = TeachingGroup::query()->findOrFail($groupId);
        if ($operation === 'capture') {
            return $authority->group($fresh, $groupId, true);
        }
        $head = $authority->leads($fresh, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id);
        if (in_array($operation, ['moderate', 'correct'], true)) {
            abort_unless($head, 403);

            return $group;
        }
        $principal = $authority->leads($fresh, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id);
        $release = app(ProfilePolicy::class)->permission($fresh, 'marks-release');
        if ($operation === 'release') {
            abort_unless($release || $head || $principal, 403);

            return $group;
        }
        if ($release || $head || $principal || app(ProfilePolicy::class)->permission($fresh, 'marks-view-all')) {
            return $group;
        }
        $allocated = TeachingAllocation::query()->where('teaching_group_id', $groupId)->whereIn('teacher_id', $authority->teachers($fresh))->whereDate('ends_on', '>=', $authority->today())->exists();
        abort_unless($group->active && $allocated, 403);

        return $group;
    }
}
