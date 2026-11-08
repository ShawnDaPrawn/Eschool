<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * Who may use the independent school business tools (Action Plan 5.10).
 *
 * Staff need a current membership in a staff role that holds the named
 * permission in this school (or the permission directly). Guardians need a
 * current Parent membership, a verified email address and a current right
 * to act for the child (an approving link, or a legacy parent with no link).
 * Every check reads fresh rows; with $write they are locking reads, taken
 * after the school lock, so a stale REPEATABLE READ snapshot cannot decide.
 */
class BusinessAuthority
{
    public function context(): int
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);

        return $school->id();
    }

    public function base(User $actor, string $module, bool $write = false): User
    {
        $id = $this->context();
        $school = School::query()->whereKey($id);
        $users = User::query()->whereKey($actor->id)->where('status', 1);
        if ($write) {
            $school->lockForUpdate();
            $users->lockForUpdate();
        }
        abort_unless($school->first()?->status === School::ACTIVE, 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = $users->first();
        abort_unless($fresh !== null, 403);
        abort_if((bool) $fresh->password_change_required, 403, 'Complete your password change first.');
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable($module, locked: true) : $gate->allows($module), 404);

        return $fresh;
    }

    public function staff(User $actor, string $module, string $permission, bool $write = false): User
    {
        $fresh = $this->base($actor, $module, $write);
        abort_unless($this->holds($fresh, $permission, $write), 403);

        return $fresh;
    }

    /** Whether a current staff membership grants the permission here. */
    public function holds(User $person, string $permission, bool $lock = false): bool
    {
        $school = $this->context();
        $memberships = SchoolMembership::active()->where('school_id', $school)->where('user_id', $person->id)->whereNotIn('role', ['Parent', 'Student']);
        if ($lock) {
            $memberships->lockForUpdate();
        }
        $roles = $memberships->pluck('role')->all();
        $grants = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->join('role_has_permissions as rp', 'rp.role_id', '=', 'r.id')->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('mr.school_id', $school)->where('r.school_id', $school)->where('mr.model_id', $person->id)
            ->where('mr.model_type', $person->getMorphClass())->whereIn('r.name', $roles)->where('p.name', $permission)->where('p.guard_name', 'web');
        $direct = DB::table('model_has_permissions as mp')->join('permissions as p', 'p.id', '=', 'mp.permission_id')
            ->where('mp.school_id', $school)->where('mp.model_id', $person->id)->where('mp.model_type', $person->getMorphClass())
            ->where('p.name', $permission)->where('p.guard_name', 'web');
        if ($lock) {
            $grants->lockForUpdate();
            $direct->lockForUpdate();
        }

        return ($roles !== [] && $grants->exists()) || ($this->hasStaffMembership($person, $lock) && $direct->exists());
    }

    private function hasStaffMembership(User $person, bool $lock): bool
    {
        $query = SchoolMembership::active()->where('school_id', $this->context())->where('user_id', $person->id)->whereNotIn('role', ['Parent', 'Student']);

        return ($lock ? $query->lockForUpdate() : $query)->exists();
    }

    public function parent(User $actor, string $module, bool $write = false): User
    {
        $fresh = $this->base($actor, $module, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh, $write ? null : false);
        abort_unless(in_array('Parent', $roles, true), 403);
        if ($write) {
            abort_unless($fresh->email_verified_at !== null, 403, 'Verify your email address first.');
        }

        return $fresh;
    }

    /** A current learner of this school, enrolled in the current school year today. */
    public function learner(int $id, bool $lock = false): Students
    {
        $query = Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'));
        $learner = ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
        abort_unless(Enrolment::where('learner_id', $id)->where('session_year_id', (int) schoolSetting('session_year'))
            ->onDate(app(AcademicAuthority::class)->today())->exists(), 404);

        return $learner;
    }

    /** The guardian may act for this child now: a current, approving relationship. */
    public function guardian(User $actor, string $module, int $learnerId, bool $write = false): User
    {
        $fresh = $this->parent($actor, $module, $write);
        $learner = $this->learner($learnerId, $write);
        abort_unless($this->approving($fresh, $learner, $write), 404);

        return $fresh;
    }

    public function approving(User $person, Students $learner, bool $lock = false): bool
    {
        $parent = (int) (Parents::where('user_id', $person->id)->value('id') ?? 0);
        if (! app(GuardianService::class)->childrenFor((int) $person->id, $parent)->whereKey($learner->id)->exists()) {
            return false;
        }
        $link = GuardianLink::where('learner_id', $learner->id)->where('guardian_user_id', $person->id);
        $link = ($lock ? $link->lockForUpdate() : $link)->first();
        if ($link !== null && (! $link->can_approve || $link->status !== 'active')) {
            return false;
        }
        $member = SchoolMembership::active()->where('school_id', $this->context())->where('user_id', $person->id)->where('role', 'Parent');

        return User::whereKey($person->id)->where('status', 1)->exists() && ($lock ? $member->lockForUpdate() : $member)->exists();
    }

    public static function personName(int $userId): string
    {
        return (string) User::query()->find($userId)?->full_name;
    }

    public static function learnerName(int $learnerId): string
    {
        return self::personName((int) Students::query()->whereKey($learnerId)->value('user_id'));
    }
}
