<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Mail\Platform\StaffInvitationMail;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\MailService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Staff choose their own password from an emailed, one-use, expiring link.
 * Nobody else ever knows it: the platform no longer shows a one-time password
 * for a new school's first Super Admin. The link is bound to the school, the
 * person and their email address at the time it was sent, and only works while
 * they are current staff there. Accepting verifies the mailbox, sets the
 * password and signs out everything else.
 * Specification: engineering/specs/platform/staff-invitations.md.
 */
class StaffInvitations
{
    /** Roles that are not staff, so never receive a staff invitation. */
    private const NOT_STAFF = ['Student', 'Parent'];

    /**
     * Issues a fresh link for a current staff member of the current school,
     * replacing any unused one. The email is sent after the transaction commits.
     *
     * @return array{invitation: StaffInvitation, url: string}
     */
    public function issue(User $staff, ?User $actor, string $purpose = StaffInvitation::STAFF): array
    {
        abort_unless(in_array($purpose, [StaffInvitation::STAFF, StaffInvitation::SCHOOL_ADMIN], true), 422);
        abort_unless(filter_var($staff->email, FILTER_VALIDATE_EMAIL) !== false && (int) $staff->status === 1 && ! $staff->trashed(), 422, __('This person needs an active account with a valid email address.'));
        $school = app(CurrentSchool::class)->id();
        abort_unless($this->isStaff($school, (int) $staff->id), 404);

        return DB::transaction(function () use ($staff, $actor, $purpose, $school) {
            StaffInvitation::query()->where('user_id', $staff->id)->whereNull('used_at')->lockForUpdate()->get()
                ->each(fn (StaffInvitation $old) => $old->update(['used_at' => now()]));
            $token = Str::random(64);
            $invitation = StaffInvitation::query()->create([
                'user_id' => $staff->id, 'created_by' => $actor?->id, 'purpose' => $purpose,
                'token_hash' => hash('sha256', $token), 'encrypted_token' => $token,
                'email_hash' => $this->emailHash((string) $staff->email),
                'expires_at' => now()->addDays((int) config('identity.invitation_days', 7)),
            ]);
            Audit::record('staff_invitation_sent', $invitation, [], ['user_id' => (int) $staff->id, 'purpose' => $purpose, 'created_by' => $actor?->id], $school);
            // afterCommit avoids sending a link whose creation rolled back.
            DB::afterCommit(fn () => $this->deliver($invitation));

            return ['invitation' => $invitation, 'url' => $this->url($token)];
        });
    }

    public function url(string $token): string
    {
        return route('staff-invitation.show', ['token' => $token]);
    }

    /** What the invitation page may show before sign-in: the school's name and the address it was sent to. */
    public function preview(string $token): array
    {
        $invitation = $this->find($token);

        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation) {
            $school = School::query()->active()->findOrFail($invitation->school_id);
            $staff = User::query()->where('status', 1)->findOrFail($invitation->user_id);
            abort_unless($this->boundTo($invitation, $staff), 404);

            return ['school' => $school->name, 'email' => $staff->email, 'first_name' => $staff->first_name, 'expires_at' => $invitation->expires_at];
        });
    }

    /** Sets the chosen password, verifies the mailbox and closes every other session and app sign-in. */
    public function accept(string $token, array $input): User
    {
        $data = Validator::make($input, ['password' => ['required', 'string', 'max:191', 'confirmed', Password::defaults()]])->validate();

        return DB::transaction(function () use ($token, $data) {
            $invitation = $this->find($token, lock: true);

            return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation, $data) {
                School::query()->active()->findOrFail($invitation->school_id);
                $staff = User::query()->where('status', 1)->lockForUpdate()->findOrFail($invitation->user_id);
                abort_unless($this->boundTo($invitation, $staff), 404);
                if (Hash::check($data['password'], (string) $staff->password) || DateOfBirthPasswords::isDateOfBirth($staff, $data['password'])) {
                    throw ValidationException::withMessages(['password' => __('Choose a password other than the default one.')]);
                }
                $staff->forceFill(['password' => Hash::make($data['password']), 'password_change_required' => false, 'email_verified_at' => now()])->save();
                AppTokens::revokeAll($staff);
                DB::table('sessions')->where('user_id', $staff->id)->delete();
                $invitation->update(['used_at' => now(), 'accepted_at' => now()]);
                Audit::record('staff_invitation_accepted', $invitation, [], ['user_id' => (int) $staff->id, 'purpose' => $invitation->purpose], (int) $invitation->school_id);

                return $staff;
            });
        });
    }

    /**
     * Whether $actor may send $person a staff invitation in the current school:
     * teacher-edit for teachers, staff-edit for other staff, and only a Super
     * Admin for a Super Admin (a changed email must never hand over that account).
     */
    public function mayInvite(User $actor, User $person): bool
    {
        $school = app(CurrentSchool::class)->id();
        if ((int) $actor->id === (int) $person->id || ! $this->isStaff($school, (int) $person->id)) {
            return false;
        }
        $fresh = User::query()->where('status', 1)->find($actor->id);
        $roles = app(ProfilePolicy::class)->roles($person);
        $mine = $fresh === null ? [] : app(ProfilePolicy::class)->roles($fresh);
        if ($fresh === null || array_diff($mine, self::NOT_STAFF) === []) {
            return false;
        }
        foreach (array_diff($roles, self::NOT_STAFF) as $role) {
            $allowed = match ($role) {
                SchoolRoles::SUPER_ADMIN => in_array(SchoolRoles::SUPER_ADMIN, $mine, true),
                'Teacher' => $fresh->can('teacher-edit'),
                default => $fresh->can('staff-edit'),
            };
            if (! $allowed) {
                return false;
            }
        }

        return true;
    }

    /** Sends a staff member their invitation from the school panel. */
    public function inviteFromSchool(User $actor, int $personId): array
    {
        $person = User::query()->findOrFail($personId);
        abort_unless($this->isStaff(app(CurrentSchool::class)->id(), (int) $person->id), 404);
        abort_unless($this->mayInvite($actor, $person), 403);

        return $this->issue($person, $actor, StaffInvitation::STAFF);
    }

    /** The current school's first administrator invitation that nobody has accepted yet. */
    public function pendingAdministrator(int $schoolId): ?StaffInvitation
    {
        return app(CurrentSchool::class)->run($schoolId, function () {
            $latest = StaffInvitation::query()->where('purpose', StaffInvitation::SCHOOL_ADMIN)->latest('id')->first();
            if ($latest === null || StaffInvitation::query()->where('user_id', $latest->user_id)->whereNotNull('accepted_at')->exists()) {
                return null;
            }

            return $latest;
        });
    }

    /** Re-sends the first administrator's invitation from the platform area. */
    public function resendAdministrator(int $schoolId, User $actor): bool
    {
        return app(CurrentSchool::class)->run($schoolId, function () use ($schoolId, $actor) {
            $pending = $this->pendingAdministrator($schoolId);
            if ($pending === null) {
                return false;
            }
            $this->issue(User::query()->findOrFail($pending->user_id), $actor, StaffInvitation::SCHOOL_ADMIN);

            return true;
        });
    }

    public function canDeliver(StaffInvitation $invitation): bool
    {
        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation) {
            $current = StaffInvitation::query()->whereNull('used_at')->where('expires_at', '>', now())->find($invitation->id);
            $staff = User::query()->where('status', 1)->find($invitation->user_id);

            return $current !== null && $staff !== null && School::query()->active()->whereKey($invitation->school_id)->exists()
                && $this->boundTo($current, $staff);
        });
    }

    /** Queues the email for a still-valid invitation (issue() calls it once the transaction commits). */
    public function deliver(StaffInvitation $invitation): bool
    {
        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation) {
            if (! $this->canDeliver($invitation)) {
                return false;
            }
            $staff = User::query()->findOrFail($invitation->user_id);
            $school = School::query()->findOrFail($invitation->school_id);

            return MailService::queue(new StaffInvitationMail((string) $staff->email, $this->url((string) $invitation->encrypted_token), $school->name,
                $invitation->purpose === StaffInvitation::SCHOOL_ADMIN, (int) $invitation->id), (int) $staff->id);
        });
    }

    private function find(string $token, bool $lock = false): StaffInvitation
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D', $token) === 1, 404);

        // The secret identifies its school before authentication; no unscoped list.
        return StaffInvitation::acrossSchools()->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->where('expires_at', '>', now())
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
    }

    private function boundTo(StaffInvitation $invitation, User $staff): bool
    {
        return hash_equals($invitation->email_hash, $this->emailHash((string) $staff->email)) && $this->isStaff((int) $invitation->school_id, (int) $staff->id);
    }

    private function isStaff(int $school, int $user): bool
    {
        return SchoolMembership::query()->active()->where('school_id', $school)->where('user_id', $user)->whereNotIn('role', self::NOT_STAFF)->exists();
    }

    private function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }
}
