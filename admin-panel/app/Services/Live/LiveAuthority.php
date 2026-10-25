<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Models\Activities\ActivityGroup;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LiveAuthority
{
    public function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        $school->school()?->refresh();
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        app(ModuleGate::class)->forget();
        abort_unless(app(ModuleGate::class)->allows('live_updates'), 404);
        app(ApprovalService::class)->assertAccess($fresh);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        if (! app(LiveConfiguration::class)->ready()) {
            return false;
        }
        try {
            $this->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function group(User $actor, int $id): ActivityGroup
    {
        $fresh = $this->actor($actor);
        $group = app(CommunityAuthority::class)->read($fresh, $id);
        $today = app(CommunityAuthority::class)->today();
        abort_unless($group->active && (int) $group->session_year_id === (int) schoolSetting('session_year')
            && $group->starts_on->toDateString() <= $today && $group->ends_on->toDateString() >= $today, 404);

        return $group;
    }
}
