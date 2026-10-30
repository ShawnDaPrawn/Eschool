<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class FamilyFormAccess
{
    public function actor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $school = app(CurrentSchool::class);
        $school->school()?->refresh();
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        if (! $revocation) {
            app(ModuleGate::class)->forget();
            abort_unless($write ? app(ModuleGate::class)->writable('family_forms') : app(ModuleGate::class)->allows('family_forms'), 404);
        }

        return $fresh;
    }

    public function learner(int $id, bool $approved = true): Students
    {
        $learner = Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->findOrFail($id);
        abort_unless(Enrolment::where('learner_id', $id)->where('session_year_id', schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->exists(), 404);
        if ($approved) {
            abort_unless(app(ApprovalService::class)->approved($learner), 403);
        }

        return $learner;
    }

    public function parentActor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $fresh = $this->actor($actor, $write, $revocation);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    public function guardian(User $actor, int $learnerId, bool $write = false, bool $revocation = false, bool $attest = false): User
    {
        $fresh = $this->parentActor($actor, $write, $revocation);
        $this->learner($learnerId, ! $revocation);
        $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
        abort_unless(app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->whereKey($learnerId)->exists(), 404);
        // Withdrawing consent stays open for a leaver's guardian; other answers do not (1.21).
        if (($write || $attest) && ! $revocation) {
            app(LeaverState::class)->guardFamilyWrite($learnerId, $fresh);
        }
        if ($write || $attest) {
            abort_unless($fresh->email_verified_at !== null, 403);
            $link = GuardianLink::where('learner_id', $learnerId)->where('guardian_user_id', $fresh->id)->first();
            abort_if($link !== null && ! $link->can_approve, 403);
        }

        return $fresh;
    }

    public function eventLearner(int $learnerId, int $group, int $year, string $from, string $to): Students
    {
        $learner = $this->learner($learnerId);
        abort_unless(ActivityGroup::whereKey($group)->where('active', true)->whereDate('starts_on', '<=', $from)->whereDate('ends_on', '>=', $to)->exists(), 404);
        abort_unless(Enrolment::where('learner_id', $learnerId)->where('session_year_id', $year)->onDate($from)->onDate($to)->exists(), 404);
        abort_unless(SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->where('status', 'active')
            ->whereDate('starts_on', '<=', $from)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $to))->exists(), 404);
        abort_unless(ActivityMembership::where('activity_group_id', $group)->where('learner_id', $learnerId)->where('active', true)->whereDate('starts_on', '<=', $from)->whereDate('ends_on', '>=', $to)->exists(), 404);

        return $learner;
    }
}
