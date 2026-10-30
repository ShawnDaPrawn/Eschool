<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Mail\Platform\SchoolJoinInvitationMail;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolJoinInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A school gives a role to someone who already has a NavigateEd account
 * (a teacher at two schools, a principal moving schools) without creating a
 * second account. The school records an invitation; nothing is attached to
 * the account until its owner signs in as that account and accepts. Their
 * password is never touched.
 *
 * The school never learns whether the address already had an account, or
 * where: adding staff answers the same, writes the same school audit row
 * (staff_added) and does comparable work either way. An account that may not
 * join this way (a learner, platform staff, an inactive account, one that
 * cannot sign in to the panel) gets an invitation row that is never sent and
 * can never be accepted ("withheld"); the school sees it wait and expire like
 * any unanswered one. HQ, setting up a school's first administrator, is told.
 *
 * Specification: engineering/specs/platform/existing-account-invitations.md.
 */
class SchoolJoinInvitations
{
    /** No account has this address: the caller creates one, as before. */
    public const NEW = 'new';

    /** The account already belongs to this school, or an invitation for it is waiting here: refused as a duplicate, as before. */
    public const KNOWN = 'known';

    /** An account with no link to this school: invite it. */
    public const ELSEWHERE = 'elsewhere';

    /** Roles that are not staff. */
    private const NOT_STAFF = [SchoolRoles::STUDENT, SchoolRoles::PARENT];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * What adding staff with this address means at the current school.
     *
     * @return array{0: string, 1: ?User}
     */
    public function classify(string $email): array
    {
        $user = $this->account($email);
        if ($user === null) {
            return [self::NEW, null];
        }
        $school = $this->currentSchool->id();
        $known = SchoolMembership::query()->where('school_id', $school)->where('user_id', $user->id)->exists()
            || SchoolJoinInvitation::query()->waiting()->where('user_id', $user->id)->exists();

        return [$known ? self::KNOWN : self::ELSEWHERE, $user];
    }

    /** A validation rule for the legacy staff forms: refuses only an address this school already knows. */
    public function notKnownHereRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && $this->classify($value)[0] === self::KNOWN) {
                $fail(trans('validation.unique', ['attribute' => str_replace('_', ' ', $attribute)]));
            }
        };
    }

    /** Whether $actor may give $role to a member of staff at the current school (as the legacy forms allow). */
    public function mayAdd(User $actor, Role $role): bool
    {
        $school = $this->currentSchool->id();
        $fresh = User::query()->where('status', 1)->find($actor->id);
        if ($fresh === null || (int) $role->school_id !== $school || in_array($role->name, [...self::NOT_STAFF, SchoolRoles::SUPER_ADMIN], true)) {
            return false;
        }
        if ($role->name !== SchoolRoles::TEACHER && (int) $role->custom_role !== 1) {
            return false;
        }
        $isStaff = SchoolMembership::query()->active()->where('school_id', $school)->where('user_id', $fresh->id)->whereNotIn('role', self::NOT_STAFF)->exists();

        return $isStaff && $fresh->can($role->name === SchoolRoles::TEACHER ? 'teacher-create' : 'staff-create');
    }

    /**
     * The school adds staff with an address that already has an account
     * elsewhere (classify() said ELSEWHERE). Answers nothing about the account.
     *
     * @param  array{name?: string, qualification?: ?string}  $details  what the school typed
     */
    public function inviteFromSchool(User $actor, User $person, Role $role, array $details = []): void
    {
        abort_unless($this->mayAdd($actor, $role), 403);
        $school = $this->currentSchool->id();
        // About the work of creating an account (its password hash), so the time taken does not tell.
        Hash::make(Str::random(40));

        DB::transaction(function () use ($actor, $person, $role, $details, $school) {
            $person = User::acrossSchools()->withTrashed()->lockForUpdate()->findOrFail($person->id);
            abort_if(SchoolMembership::query()->where('school_id', $school)->where('user_id', $person->id)->exists(), 409);
            $reason = $this->ineligible($person);
            $invitation = $this->create($person, $role, $actor, SchoolJoinInvitation::STAFF, $details, $reason !== null);
            $this->auditIssue($invitation, $actor, $reason);
            $this->recordAdded($actor, $role);
        });
    }

    /**
     * The one school audit row for adding a member of staff, the same whether
     * the address was new, already had an account, or may not join.
     */
    public function recordAdded(User $actor, Role|string $role): void
    {
        Audit::record('staff_added', null, [], ['role' => $role instanceof Role ? $role->name : $role, 'added_by' => (int) $actor->id], $this->currentSchool->id());
    }

    /**
     * Why this account may not be invited to join a school, or null. Never told
     * to the school; HQ gets one message for all of them.
     */
    public function ineligible(User $person): ?string
    {
        if ($person->trashed() || (int) $person->status !== 1 || filter_var($person->email, FILTER_VALIDATE_EMAIL) === false) {
            return 'inactive';
        }
        if (PlatformStaffIdentity::query()->where('user_id', $person->id)->exists()) {
            return 'platform_staff';
        }
        if (SchoolMembership::query()->where('user_id', $person->id)->where('role', SchoolRoles::STUDENT)->exists()) {
            return 'learner';
        }
        // They must be able to sign in to the panel to accept: the sign-in
        // form works in their first school and refuses app-only roles there.
        $memberships = app(MembershipService::class)->active($person);
        $first = $memberships->first()?->school_id;
        $roles = $memberships->where('school_id', $first)->pluck('role');
        if ($first === null || $roles->isEmpty() || $roles->intersect(self::NOT_STAFF)->isNotEmpty()) {
            return 'cannot_sign_in';
        }

        return null;
    }

    /**
     * HQ sets up a school whose first administrator already has an account.
     * Runs inside the new school. HQ is told when the account may not.
     *
     * @return array{invitation: SchoolJoinInvitation, url: string}
     */
    public function inviteFirstAdministrator(User $person, User $actor, string $typedName): array
    {
        $this->assertMayBeFirstAdministrator($person);
        $role = Role::query()->where('name', SchoolRoles::SUPER_ADMIN)->firstOrFail();

        return DB::transaction(function () use ($person, $actor, $typedName, $role) {
            $invitation = $this->create($person, $role, $actor, SchoolJoinInvitation::SCHOOL_ADMIN, ['name' => $typedName], false);
            $this->auditIssue($invitation, $actor, null);

            return ['invitation' => $invitation, 'url' => $this->url((string) $invitation->encrypted_token)];
        });
    }

    /** HQ is told (one message per kind) when an existing account may not be invited as a first administrator. */
    public function assertMayBeFirstAdministrator(User $person): void
    {
        $reason = $this->ineligible($person);
        if ($reason !== null) {
            throw ValidationException::withMessages(['admin_email' => $reason === 'cannot_sign_in'
                ? 'This address has a NavigateEd account that cannot sign in to a school panel at the moment, so its owner could not accept. Use another address, or ask support.'
                : 'This address belongs to an account that cannot be made a school administrator. Use another address.']);
        }
    }

    /** The school's first administrator invitation that nobody has accepted yet. */
    public function pendingAdministrator(int $schoolId): ?SchoolJoinInvitation
    {
        return $this->currentSchool->run($schoolId, function () {
            $latest = SchoolJoinInvitation::query()->where('purpose', SchoolJoinInvitation::SCHOOL_ADMIN)->latest('id')->first();
            if ($latest === null || SchoolJoinInvitation::query()->where('purpose', SchoolJoinInvitation::SCHOOL_ADMIN)->whereNotNull('accepted_at')->exists()) {
                return null;
            }

            return $latest;
        });
    }

    /** HQ sends the first administrator's invitation again; the old link stops working. */
    public function resendAdministrator(int $schoolId, User $actor): bool
    {
        return $this->currentSchool->run($schoolId, function () use ($schoolId, $actor) {
            $pending = $this->pendingAdministrator($schoolId);
            if ($pending === null) {
                return false;
            }
            $person = User::acrossSchools()->withTrashed()->findOrFail($pending->user_id);
            $this->inviteFirstAdministrator($person, $actor, (string) $pending->encrypted_name);

            return true;
        });
    }

    public function url(string $token): string
    {
        return route('join-invitation.show', ['token' => $token]);
    }

    /**
     * What the invitee sees once signed in as the invited account.
     *
     * @return array{school: string, role: string, first_administrator: bool, expires_at: Carbon}
     */
    public function preview(string $token, User $signedIn): array
    {
        $invitation = $this->find($token);

        return $this->currentSchool->run((int) $invitation->school_id, function () use ($invitation, $signedIn) {
            [$school, , $role] = $this->bound($invitation, $signedIn);

            return ['school' => $school->name, 'role' => $role->name, 'first_administrator' => $invitation->purpose === SchoolJoinInvitation::SCHOOL_ADMIN, 'expires_at' => $invitation->expires_at];
        });
    }

    /**
     * The invitee accepts: a membership and the invited role at the school,
     * through MembershipService and the school's roles, and a teacher or staff
     * record there. Nothing else about the account changes.
     */
    public function accept(string $token, User $signedIn): School
    {
        return DB::transaction(function () use ($token, $signedIn) {
            $invitation = $this->find($token, lock: true);

            return $this->currentSchool->run((int) $invitation->school_id, function () use ($invitation, $signedIn) {
                [$school, $person, $role] = $this->bound($invitation, $signedIn, lock: true);
                app(MembershipService::class)->grant($person, $school, $role->name);
                $person->unsetRelation('roles')->unsetRelation('permissions')->assignRole($role);
                app(MembershipService::class)->forget($person);
                if ($role->name === SchoolRoles::TEACHER) {
                    if (! Teacher::query()->where('user_id', $person->id)->exists()) {
                        SchoolTable::query('teachers')->insert(['user_id' => $person->id, 'qualification' => $invitation->qualification ?: 'Not recorded', 'created_at' => now(), 'updated_at' => now()]);
                    }
                } elseif ($role->name !== SchoolRoles::SUPER_ADMIN && ! Staff::query()->where('user_id', $person->id)->exists()) {
                    SchoolTable::query('staffs')->insert(['user_id' => $person->id, 'created_at' => now(), 'updated_at' => now()]);
                }
                $invitation->forceFill(['used_at' => now(), 'accepted_at' => now()])->save();
                Audit::record('school_join_invitation_accepted', $invitation, [], ['user_id' => (int) $person->id, 'role' => $role->name, 'purpose' => $invitation->purpose], (int) $school->id);

                return $school;
            });
        });
    }

    /** The invitee says no. The school sees the invitation stop waiting. */
    public function decline(string $token, User $signedIn): void
    {
        DB::transaction(function () use ($token, $signedIn) {
            $invitation = $this->find($token, lock: true);
            $this->currentSchool->run((int) $invitation->school_id, function () use ($invitation, $signedIn) {
                [$school, $person] = $this->bound($invitation, $signedIn, lock: true);
                $invitation->forceFill(['used_at' => now(), 'declined_at' => now()])->save();
                Audit::record('school_join_invitation_declined', $invitation, [], ['user_id' => (int) $person->id, 'purpose' => $invitation->purpose], (int) $school->id);
            });
        });
    }

    /**
     * The current school's waiting invitations, with only what the school typed.
     *
     * @return Collection<int, array{id: int, name: string, email: string, role: string, sent: Carbon|null, expires: Carbon, may_withdraw: bool}>
     */
    public function waiting(User $actor): Collection
    {
        abort_unless($actor->can('staff-create') || $actor->can('teacher-create'), 403);
        $roles = Role::query()->pluck('name', 'id');

        return SchoolJoinInvitation::query()->waiting()->latest('id')->get()->map(fn (SchoolJoinInvitation $invitation) => [
            'id' => (int) $invitation->id, 'name' => (string) $invitation->encrypted_name, 'email' => (string) $invitation->encrypted_email,
            'role' => (string) ($roles[$invitation->role_id] ?? ''), 'sent' => $invitation->created_at, 'expires' => $invitation->expires_at,
            'may_withdraw' => $this->mayWithdraw($actor, $invitation),
        ]);
    }

    /** The school withdraws a waiting invitation; its link then opens nothing. */
    public function withdraw(User $actor, int $invitationId): void
    {
        DB::transaction(function () use ($actor, $invitationId) {
            $invitation = SchoolJoinInvitation::query()->waiting()->lockForUpdate()->findOrFail($invitationId);
            abort_unless($this->mayWithdraw($actor, $invitation), 403);
            $invitation->forceFill(['used_at' => now(), 'withdrawn_at' => now(), 'withdrawn_by' => $actor->id])->save();
            Audit::record('school_join_invitation_withdrawn', $invitation, [], ['withdrawn_by' => (int) $actor->id], (int) $invitation->school_id);
        });
    }

    public function canDeliver(SchoolJoinInvitation $invitation): bool
    {
        return $this->currentSchool->run((int) $invitation->school_id, function () use ($invitation) {
            $current = SchoolJoinInvitation::query()->waiting()->whereNull('withheld_at')->find($invitation->id);
            $person = User::acrossSchools()->where('status', 1)->find($invitation->user_id);

            return $current !== null && $person !== null && School::query()->active()->whereKey($invitation->school_id)->exists()
                && hash_equals($current->email_hash, $this->emailHash((string) $person->email)) && $this->ineligible($person) === null
                && Role::query()->whereKey($current->role_id)->exists();
        });
    }

    /** Queues the email for a still-valid invitation (sent once the transaction commits). */
    public function deliver(SchoolJoinInvitation $invitation): bool
    {
        return $this->currentSchool->run((int) $invitation->school_id, function () use ($invitation) {
            if (! $this->canDeliver($invitation)) {
                return false;
            }
            $person = User::acrossSchools()->findOrFail($invitation->user_id);
            $school = School::query()->findOrFail($invitation->school_id);
            $role = Role::query()->findOrFail($invitation->role_id);

            return MailService::queue(new SchoolJoinInvitationMail((string) $person->email, $this->url((string) $invitation->encrypted_token), $school->name,
                $role->name, $invitation->purpose === SchoolJoinInvitation::SCHOOL_ADMIN, (int) $invitation->id), (int) $person->id);
        });
    }

    /** @param  array{name?: string, qualification?: ?string}  $details */
    private function create(User $person, Role $role, ?User $actor, string $purpose, array $details, bool $withheld): SchoolJoinInvitation
    {
        // One live invitation per person and school: a newer one replaces it.
        SchoolJoinInvitation::query()->where('user_id', $person->id)->whereNull('used_at')->lockForUpdate()->get()
            ->each(fn (SchoolJoinInvitation $old) => $old->forceFill(['used_at' => now()])->save());
        $token = Str::random(64);
        $invitation = SchoolJoinInvitation::query()->create([
            'user_id' => $person->id, 'role_id' => $role->id, 'purpose' => $purpose, 'created_by' => $actor?->id,
            'encrypted_name' => Str::limit(trim((string) ($details['name'] ?? '')), 191, ''), 'encrypted_email' => strtolower(trim((string) $person->email)),
            'qualification' => isset($details['qualification']) && $details['qualification'] !== '' ? Str::limit((string) $details['qualification'], 191, '') : null,
            'token_hash' => hash('sha256', $token), 'encrypted_token' => $token, 'email_hash' => $this->emailHash((string) $person->email),
            'expires_at' => now()->addDays((int) config('identity.invitation_days', 7)),
            'withheld_at' => $withheld ? now() : null,
        ]);
        if (! $withheld) {
            // afterCommit: a link whose creation rolled back is never sent.
            DB::afterCommit(fn () => $this->deliver($invitation));
        }

        return $invitation;
    }

    private function auditIssue(SchoolJoinInvitation $invitation, ?User $actor, ?string $reason): void
    {
        Audit::record($reason === null ? 'school_join_invitation_sent' : 'school_join_invitation_withheld', $invitation, [],
            array_filter(['user_id' => (int) $invitation->user_id, 'role_id' => $invitation->role_id, 'purpose' => $invitation->purpose, 'created_by' => $actor?->id, 'reason' => $reason], fn ($value) => $value !== null),
            (int) $invitation->school_id);
    }

    private function mayWithdraw(User $actor, SchoolJoinInvitation $invitation): bool
    {
        $role = $invitation->role_id === null ? null : Role::query()->find($invitation->role_id);
        if ($invitation->purpose === SchoolJoinInvitation::SCHOOL_ADMIN || $role?->name === SchoolRoles::SUPER_ADMIN) {
            return SchoolMembership::query()->active()->where('school_id', $this->currentSchool->id())->where('user_id', $actor->id)->where('role', SchoolRoles::SUPER_ADMIN)->exists();
        }

        return $role === null ? $this->staffWith($actor, 'staff-create') : $this->mayAdd($actor, $role);
    }

    private function staffWith(User $actor, string $permission): bool
    {
        $fresh = User::query()->where('status', 1)->find($actor->id);

        return $fresh !== null && $fresh->can($permission)
            && SchoolMembership::query()->active()->where('school_id', $this->currentSchool->id())->where('user_id', $fresh->id)->whereNotIn('role', self::NOT_STAFF)->exists();
    }

    /**
     * The invitation, its school and role, checked against the signed-in
     * account. Not valid any more: 404. Valid, but another account: 403.
     *
     * @return array{0: School, 1: User, 2: Role}
     */
    private function bound(SchoolJoinInvitation $invitation, User $signedIn, bool $lock = false): array
    {
        $school = School::query()->active()->find($invitation->school_id);
        $role = $invitation->role_id === null ? null : Role::query()->find($invitation->role_id);
        abort_if($school === null || $role === null || in_array($role->name, self::NOT_STAFF, true), 404);
        abort_if($role->name === SchoolRoles::SUPER_ADMIN && $invitation->purpose !== SchoolJoinInvitation::SCHOOL_ADMIN, 404);
        abort_unless((int) $signedIn->id === (int) $invitation->user_id, 403, __('This invitation is for another account. Sign out, then open the link again and sign in as the person it was sent to.'));
        $person = User::acrossSchools()->where('status', 1)->when($lock, fn ($query) => $query->lockForUpdate())->find($invitation->user_id);
        abort_if($person === null || ! hash_equals($invitation->email_hash, $this->emailHash((string) $person->email)), 404);
        // Platform staff and learner accounts never join a school this way, whenever they became one.
        abort_if(PlatformStaffIdentity::query()->where('user_id', $person->id)->exists()
            || SchoolMembership::query()->where('user_id', $person->id)->where('role', SchoolRoles::STUDENT)->exists(), 404);

        return [$school, $person, $role];
    }

    private function find(string $token, bool $lock = false): SchoolJoinInvitation
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D', $token) === 1, 404);

        // The secret identifies its school before the school is known; no unscoped list.
        return SchoolJoinInvitation::acrossSchools()->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->whereNull('withheld_at')->where('expires_at', '>', now())
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
    }

    private function account(string $email): ?User
    {
        $email = strtolower(trim($email));

        return $email === '' ? null : User::acrossSchools()->where('email', $email)->first();
    }

    private function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }
}
