<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\FamilyForms\FamilyFormAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class PathwayAccess extends FamilyFormAccess
{
    public const MANAGE = 'pathways-manage';

    public function actor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $scope = app(CurrentSchool::class);
        $scope->school()?->refresh();
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating() && $scope->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('pathway') : app(ModuleGate::class)->allows('pathway'), 404);

        return $fresh;
    }

    public function manager(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== []
            && app(ProfilePolicy::class)->permission($fresh, self::MANAGE), 403);

        return $fresh;
    }

    public function familyLearner(User $actor, int $learnerId): Students
    {
        $fresh = $this->actor($actor);
        $learner = $this->learner($learnerId);
        if (in_array('Student', app(ProfilePolicy::class)->roles($fresh), true) && (int) $learner->user_id === (int) $fresh->id) {
            return $learner;
        }
        $this->guardian($fresh, $learnerId);

        return $learner;
    }
}
