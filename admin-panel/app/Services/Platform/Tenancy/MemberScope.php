<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Users are one row per human across schools (no school_id), so a school's
 * code sees only the people who belong to it: once a school is chosen for
 * the request or job, User queries are limited to users with a membership
 * there (of any status, so a deactivated teacher is still listed), and to
 * accounts that belong to no school at all. Another school's people are
 * never found. Without a chosen school (the console, across schools) every
 * user is visible, as identity needs.
 */
class MemberScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $currentSchool = app(CurrentSchool::class);
        if (! $currentSchool->isSet() || $currentSchool->isMigrating()) {
            return;
        }
        $schoolId = $currentSchool->id();

        $builder->where(fn (Builder $users) => $users
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('school_memberships')
                ->whereColumn('school_memberships.user_id', $model->qualifyColumn('id'))
                ->where('school_memberships.school_id', $schoolId))
            // An account that belongs to no school yet (created a moment before
            // its first role, or platform staff) holds no school's data.
            ->orWhereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('school_memberships')
                ->whereColumn('school_memberships.user_id', $model->qualifyColumn('id'))));
    }
}
