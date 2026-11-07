<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who reaches the reading library (Action Plan 4.4), checked by every service
 * method after the route and the FormRequest:
 *
 * - HQ: platform staff holding reading.manage (Content, Owner), never from
 *   inside a school. Only they change the catalogue.
 * - A school leader (Super Admin, or office staff who manage school settings)
 *   of a school with the reading module on: hides books and shelves for the
 *   school's learners and sets the reading badges. Never the catalogue.
 * - A learner: a current, guardian-approved Student of the school, in a stage
 *   the module allows. Reads books and their own progress only.
 */
class ReadingAccess
{
    public const MANAGE = 'reading.manage';

    public const MODULE = 'reading';

    public function hq(?User $user): User
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'The reading library catalogue is managed only from HQ.');
        abort_unless(app(PlatformStaffAccess::class)->can($user, self::MANAGE), 403);

        return app(PlatformStaffAccess::class)->actor($user);
    }

    public function canHq(?User $user): bool
    {
        return ! app(CurrentSchool::class)->isSet() && app(PlatformStaffAccess::class)->can($user, self::MANAGE);
    }

    public function leader(?User $user, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($user !== null && $school->isSet() && ! $school->isAcrossSchools() && $school->school()?->isActive(), 403);
        abort_if($write && $school->isImpersonating(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $user->id)->where('active', true)->exists(), 403);
        abort_unless(SchoolStages::mayManage($user), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable(self::MODULE) : $gate->allows(self::MODULE), 404);

        return $user;
    }

    public function mayLead(?User $user): bool
    {
        $school = app(CurrentSchool::class);

        return $user !== null && $school->isSet() && ! $school->isAcrossSchools() && SchoolStages::mayManage($user) && app(ModuleGate::class)->allows(self::MODULE);
    }

    public function learner(User $actor, bool $write = false): Students
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable(self::MODULE) : $gate->allows(self::MODULE), 403);
        abort_unless($policy->roles($fresh) === ['Student'], 403);
        abort_unless(app(CurrentLearnerMembership::class)->users()->whereKey($fresh->id)->exists(), 404);
        $learner = Students::where('user_id', $fresh->id)->firstOrFail();
        abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');

        return $learner;
    }
}
