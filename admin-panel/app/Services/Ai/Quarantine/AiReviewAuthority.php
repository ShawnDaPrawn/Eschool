<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiReviewAuthority
{
    public function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor(User::findOrFail($actor->id));
        abort_if($fresh->password_change_required, 403);
        abort_unless(in_array('Safeguarding', $policy->roles($fresh), true) && $policy->permission($fresh, 'ai-flags-review'), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
