<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\CurriculumPlan;
use App\Models\User;
use App\Services\Academics\AcademicAccess;

class CurriculumPolicy
{
    public function authorise(User $actor, string $operation, ?CurriculumPlan $plan = null): void
    {
        $permission = match ($operation) {
            'read' => 'curriculum-read', 'publish' => 'curriculum-publish', default => 'curriculum-manage'
        };
        $access = app(AcademicAccess::class);
        if ($plan === null) {
            $access->actor($actor, $permission);
        } else {
            $access->context($actor, $permission, (int) $plan->subject_id, (int) $plan->grade_level_id, (int) $plan->session_year_id);
        }
    }
}
