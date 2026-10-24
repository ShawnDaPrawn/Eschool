<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;

class TimetablePolicy
{
    public function authorise(User $actor, string $operation, ?int $learner = null): ?int
    {
        if ($operation === 'manage') {
            app(AcademicAuthority::class)->manage($actor);

            return null;
        }
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        $roles = $policy->roles($fresh);
        if ($learner !== null) {
            return (int) app(ReleasedMarks::class)->learner($fresh, $learner)->id;
        }
        if (array_diff($roles, ['Student', 'Parent']) !== []) {
            app(AcademicAuthority::class)->staff($fresh);

            return null;
        }
        abort_unless(in_array('Student', $roles, true), 422, __('Choose your child’s timetable.'));
        $student = Students::where('user_id', $fresh->id)->firstOrFail();
        app(ReleasedMarks::class)->learner($fresh, (int) $student->id);

        return (int) $student->id;
    }
}
