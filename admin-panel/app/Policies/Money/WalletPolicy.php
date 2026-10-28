<?php

declare(strict_types=1);

namespace App\Policies\Money;

use App\Models\Money\WalletAccount;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletPolicy
{
    public const FINANCE = 'wallet-finance-manage';

    public const DESK_POLICY = 'wallet-desk-policy-manage';

    public const DESK_PROCESS = 'wallet-desk-process';

    public const PERSONAL_ROLES = ['Parent', 'Student', 'Teacher', 'Facilitator', 'Super Admin'];

    public function actor(User $actor, bool $write = false): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_unless(DB::table('schools')->where('id', $context->id())->lockForUpdate()->first()?->status === 'active', 403);
        $fresh = User::whereKey($actor->id)->where('status', 1)->lockForUpdate()->first();
        abort_unless($fresh !== null && ! $fresh->password_change_required, 403);
        abort_if(DB::table('platform_staff_identities')->where('user_id', $actor->id)->where('active', true)->lockForUpdate()->exists(), 403);
        abort_if($this->roles($fresh) === [], 403);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('wallet', locked: true) : app(ModuleGate::class)->state('wallet', locked: true)['enabled'], 403);

        return $fresh;
    }

    public function finance(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless($this->permission($fresh, self::FINANCE), 403);

        return $fresh;
    }

    public function desk(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless($this->permission($fresh, self::DESK_PROCESS) || $this->permission($fresh, self::FINANCE), 403);

        return $fresh;
    }

    public function deskPolicy(User $actor, bool $write = true): User
    {
        $fresh = $this->finance($actor, $write);
        abort_unless($this->permission($fresh, self::DESK_POLICY), 403);

        return $fresh;
    }

    private function permission(User $fresh, string $permission): bool
    {
        $school = app(CurrentSchool::class)->id();
        $roles = $this->roles($fresh);
        $grants = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')->join('role_has_permissions as rp', 'rp.role_id', '=', 'r.id')->join('permissions as p', 'p.id', '=', 'rp.permission_id')->where('mr.school_id', $school)->where('r.school_id', $school)->where('mr.model_id', $fresh->id)->where('mr.model_type', $fresh->getMorphClass())->whereIn('r.name', $roles)->where('p.name', $permission)->where('p.guard_name', 'web')->lockForUpdate();
        $direct = DB::table('model_has_permissions as mp')->join('permissions as p', 'p.id', '=', 'mp.permission_id')->where('mp.school_id', $school)->where('mp.model_id', $fresh->id)->where('mp.model_type', $fresh->getMorphClass())->where('p.name', $permission)->where('p.guard_name', 'web')->lockForUpdate();

        return $grants->first() !== null || $direct->first() !== null;
    }

    /** Fresh owner authority. Inactive learners retain guardian-readable existing history. */
    public function owner(User $actor, int $owner, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $subject = User::withTrashed()->lockForUpdate()->findOrFail($owner);
        if ((int) $fresh->id === $owner) {
            abort_unless(array_intersect($this->roles($fresh), self::PERSONAL_ROLES) !== [], 403);
            $this->activePerson($fresh);

            return $fresh;
        }
        abort_unless(in_array('Parent', $this->roles($fresh), true) && $fresh->email_verified_at !== null, 403);
        $student = Students::where('user_id', $owner)->lockForUpdate()->first();
        abort_unless($student !== null, 404);
        abort_unless(GuardianLink::where('learner_id', $student->id)->where('guardian_user_id', $fresh->id)->where('status', 'active')->where('can_approve', true)->lockForUpdate()->exists(), 404);
        if ($write) {
            $this->activePerson($subject);
            abort_unless(in_array('Student', $this->roles($subject), true), 403);
        }

        return $subject;
    }

    public function limits(User $actor, WalletAccount $wallet, bool $write = false): User
    {
        $subject = $this->owner($actor, (int) $wallet->user_id, $write);
        if ($write && (int) $actor->id === (int) $wallet->user_id) {
            abort_if(Students::where('user_id', $actor->id)->exists(), 403, 'Learner spending controls are set by an authorised guardian.');
        }

        return $subject;
    }

    public function roles(User $user): array
    {
        $school = app(CurrentSchool::class)->id();
        $memberships = SchoolMembership::active()->where('school_id', $school)->where('user_id', $user->id)->lockForUpdate()->pluck('role');

        return DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')->where('mr.school_id', $school)->where('r.school_id', $school)->where('mr.model_id', $user->id)->where('mr.model_type', $user->getMorphClass())->whereIn('r.name', $memberships)->lockForUpdate()->pluck('r.name')->all();
    }

    public function activePerson(User $subject): void
    {
        abort_if(DB::table('platform_staff_identities')->where('user_id', $subject->id)->where('active', true)->lockForUpdate()->exists(), 403);
        abort_unless((int) $subject->status === 1 && $subject->deleted_at === null && array_intersect($this->roles($subject), self::PERSONAL_ROLES) !== [], 403);
        if (in_array('Student', $this->roles($subject), true)) {
            $student = Students::where('user_id', $subject->id)->lockForUpdate()->first();
            abort_unless($student !== null && app(ApprovalService::class)->approved($student), 403, 'Verified guardian approval is required for a learner wallet.');
        }
    }
}
