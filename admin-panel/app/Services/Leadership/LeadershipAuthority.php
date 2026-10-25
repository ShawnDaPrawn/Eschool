<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LeadershipAuthority
{
    public function principal(User $actor, int $year): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $a = app(AcademicAuthority::class);
        $fresh = $a->staff($actor);
        abort_if($fresh->password_change_required, 403);
        SessionYear::findOrFail($year);
        // School administrators and current principals may examine retained years.
        // A platform identity never inherits this institutional authority.
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $admin = array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $a->manages($fresh);
        abort_unless($admin || $a->currentLeadership($fresh, 'principal', 0), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('leadership_dashboard'), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->principal($actor, (int) schoolSetting('session_year'));

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
