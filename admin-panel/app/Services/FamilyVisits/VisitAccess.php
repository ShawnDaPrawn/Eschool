<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\FamilyForms\FamilyFormAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

/** Reuse approved learner/guardian policy without requiring Health module access. */
class VisitAccess extends FamilyFormAccess
{
    public const MANAGE = 'family-visits-manage';

    public function actor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $scope = app(CurrentSchool::class);
        $scope->school()?->refresh();
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating() && $scope->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        app(ModuleGate::class)->forget();
        if (! $revocation) {
            abort_unless($write ? app(ModuleGate::class)->writable('family_visits') : app(ModuleGate::class)->allows('family_visits'), 404);
        }

        return $fresh;
    }

    public function manager(User $actor, bool $write = false, bool $revocation = false): User
    {
        $fresh = $this->actor($actor, $write, $revocation);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, self::MANAGE), 403);

        return $fresh;
    }

    public function host(int $id): User
    {
        $fresh = $this->actor(User::findOrFail($id));
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [], 404);

        return $fresh;
    }
}
