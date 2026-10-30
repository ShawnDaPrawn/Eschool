<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Mail\Platform\PlatformStaffInvitationMail;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\PlatformStaffInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Platform staff and their roles (Action Plan SA.1). Owners invite staff by
 * email from HQ; the invited person chooses their own password from a
 * one-use link. Each grant carries one role and an expiry of at most a year,
 * and every change is audited with its reason. The install always keeps at
 * least one active Owner. engineering/specs/platform/platform-roles.md.
 */
class PlatformStaff
{
    public const MAX_DAYS = 365;

    public const INVITATION_DAYS = 3;

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $grants = PlatformAdminGrant::query()->get()->keyBy('user_id');
            $pending = PlatformStaffInvitation::query()->whereNull('used_at')->where('expires_at', '>', now()->utc())->pluck('expires_at', 'user_id');
            $accepted = PlatformStaffInvitation::query()->whereNotNull('accepted_at')->pluck('user_id')->flip();
            $identities = PlatformStaffIdentity::query()->get()->keyBy('user_id');

            return User::query()->whereIn('id', $identities->keys())->orderBy('first_name')->orderBy('last_name')->get()
                ->map(function (User $user) use ($grants, $pending, $identities, $accepted): array {
                    $grant = $grants->get($user->id);
                    $current = $grant !== null && $grant->active && $grant->expires_at->isFuture() && $identities[$user->id]->active && (int) $user->status === 1;

                    return [
                        'id' => (int) $user->id,
                        'name' => trim($user->first_name.' '.$user->last_name),
                        'email' => $user->email,
                        'role' => $grant?->role,
                        'role_label' => $grant !== null ? __((string) config('platform_roles.'.$grant->role.'.label', $grant->role)) : null,
                        'expires_at' => $grant?->expires_at,
                        'current' => $current,
                        'invitation_pending' => $pending->has($user->id),
                        'invited' => $pending->has($user->id) || $accepted->has($user->id),
                    ];
                })->all();
        });
    }

    /** @return array<string, string> role key => label */
    public static function roles(): array
    {
        return collect((array) config('platform_roles'))->map(fn (array $role) => __($role['label']))->all();
    }

    /** @return array<string, mixed> */
    public static function rules(bool $new): array
    {
        return array_filter([
            'first_name' => $new ? ['required', 'string', 'max:128'] : null,
            'last_name' => $new ? ['required', 'string', 'max:128'] : null,
            'email' => $new ? ['required', 'email:rfc', 'max:191'] : null,
            'role' => ['required', Rule::in(array_keys((array) config('platform_roles')))],
            'days' => ['required', 'integer', 'min:1', 'max:'.self::MAX_DAYS],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
    }

    /**
     * Invites a new platform staff member by email. The address must not
     * belong to anyone at a school: platform staff hold no school role.
     *
     * The link goes out by email once the transaction commits; it is also
     * returned (never shown or stored) so the caller can deliver it itself.
     *
     * @param  array{first_name: string, last_name: string, email: string, role: string, days: int, reason: string}  $data
     * @return array{user: User, url: string}
     */
    public function invite(User $actor, array $data): array
    {
        $email = strtolower(trim($data['email']));

        return $this->currentSchool->acrossSchools(function () use ($actor, $data, $email): array {
            $existing = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($existing !== null && (! PlatformStaffIdentity::query()->where('user_id', $existing->id)->exists() || SchoolMembership::query()->where('user_id', $existing->id)->exists())) {
                throw ValidationException::withMessages(['email' => __('This email address already has a school account. Platform staff need their own address.')]);
            }
            if ($existing !== null) {
                throw ValidationException::withMessages(['email' => __('This person is already platform staff. Change their role below.')]);
            }

            return DB::transaction(function () use ($actor, $data, $email): array {
                $user = User::query()->create([
                    'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'mobile' => '',
                    // Nobody knows this password: the invited person chooses their own.
                    'password' => Hash::make(Str::random(48)), 'status' => 1,
                ]);
                PlatformStaffIdentity::query()->create(['user_id' => $user->id, 'active' => true]);
                $this->grant($actor, $user, $data);

                return ['user' => $user, 'url' => $this->issue($actor, $user)];
            });
        });
    }

    /** @param  array{role: string, days: int, reason: string}  $data */
    public function change(User $actor, int $userId, array $data): void
    {
        $this->currentSchool->acrossSchools(function () use ($actor, $userId, $data): void {
            DB::transaction(function () use ($actor, $userId, $data): void {
                $user = $this->staffMember($userId);
                if ($data['role'] !== 'owner') {
                    $this->keepAnOwner($user);
                }
                $this->grant($actor, $user, $data);
            });
        });
    }

    public function revoke(User $actor, int $userId, string $reason): void
    {
        $this->currentSchool->acrossSchools(function () use ($actor, $userId, $reason): void {
            DB::transaction(function () use ($actor, $userId, $reason): void {
                $user = $this->staffMember($userId);
                $this->keepAnOwner($user);
                $grant = PlatformAdminGrant::query()->where('user_id', $user->id)->lockForUpdate()->first();
                $grant?->update(['active' => false, 'granted_by' => $actor->id, 'reason' => $reason]);
                PlatformStaffInvitation::query()->where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()->utc()]);
                Audit::record('platform_staff_revoked', $user, ['role' => $grant?->role], ['reason' => $reason, 'changed_by' => $actor->id]);
            });
        });
    }

    /** Sends a fresh link to someone who has not chosen a password yet; returns it as invite() does. */
    public function resend(User $actor, int $userId): string
    {
        return $this->currentSchool->acrossSchools(function () use ($actor, $userId): string {
            $user = $this->staffMember($userId);
            abort_if(PlatformStaffInvitation::query()->where('user_id', $user->id)->whereNotNull('accepted_at')->exists(), 409, __('This person has already chosen a password.'));
            abort_unless($this->bound(new PlatformStaffInvitation, $user), 409, __('Give this person a current role before inviting them.'));

            return DB::transaction(fn (): string => $this->issue($actor, $user));
        });
    }

    /** What the invitation page shows before the password is chosen. */
    public function preview(string $token): array
    {
        return $this->currentSchool->acrossSchools(function () use ($token): array {
            [$invitation, $user] = $this->open($token);

            return ['email' => $user->email, 'first_name' => $user->first_name, 'expires_at' => $invitation->expires_at];
        });
    }

    /** Sets the chosen password, verifies the mailbox and ends every other sign-in. */
    public function accept(string $token, array $input): User
    {
        $data = Validator::make($input, ['password' => ['required', 'string', 'max:191', 'confirmed', Password::defaults()]])->validate();

        return $this->currentSchool->acrossSchools(fn (): User => DB::transaction(function () use ($token, $data): User {
            [$invitation, $user] = $this->open($token, lock: true);
            $user->forceFill(['password' => Hash::make($data['password']), 'password_change_required' => false, 'email_verified_at' => now()])->save();
            AppTokens::revokeAll($user);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $invitation->update(['used_at' => now()->utc(), 'accepted_at' => now()->utc()]);
            Audit::record('platform_staff_invitation_accepted', $user, [], ['invitation_id' => $invitation->id]);

            return $user;
        }));
    }

    /** Whether a queued invitation email may still go out (PlatformStaffInvitationMail). */
    public function canDeliver(int $invitationId, string $address): bool
    {
        return $this->currentSchool->acrossSchools(function () use ($invitationId, $address): bool {
            $invitation = PlatformStaffInvitation::query()->whereNull('used_at')->where('expires_at', '>', now()->utc())->find($invitationId);
            $user = $invitation === null ? null : User::query()->where('status', 1)->find($invitation->user_id);

            return $user !== null && hash_equals($invitation->email_hash, $this->emailHash($address)) && $this->bound($invitation, $user);
        });
    }

    /** @param  array{role: string, days: int, reason: string}  $data */
    private function grant(User $actor, User $user, array $data): void
    {
        $grant = PlatformAdminGrant::query()->where('user_id', $user->id)->lockForUpdate()->first();
        $before = $grant === null ? [] : ['role' => $grant->role, 'active' => $grant->active, 'expires_at' => $grant->expires_at->toIso8601String()];
        $expires = now()->utc()->addDays((int) $data['days']);
        $grant ??= new PlatformAdminGrant(['user_id' => $user->id]);
        $grant->fill(['role' => $data['role'], 'active' => true, 'expires_at' => $expires, 'granted_by' => $actor->id, 'reason' => $data['reason']])->save();
        Audit::record('platform_staff_role_granted', $user, $before, ['role' => $data['role'], 'expires_at' => $expires->toIso8601String(), 'reason' => $data['reason'], 'changed_by' => $actor->id]);
    }

    private function issue(User $actor, User $user): string
    {
        PlatformStaffInvitation::query()->where('user_id', $user->id)->whereNull('used_at')->lockForUpdate()->update(['used_at' => now()->utc()]);
        $token = Str::random(64);
        $invitation = PlatformStaffInvitation::query()->create([
            'user_id' => $user->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $this->emailHash((string) $user->email),
            'expires_at' => now()->utc()->addDays(self::INVITATION_DAYS), 'created_by' => $actor->id,
        ]);
        Audit::record('platform_staff_invited', $user, [], ['invitation_id' => $invitation->id, 'changed_by' => $actor->id]);
        $url = route('platform-invitation.show', ['token' => $token]);
        // Only once the invitation is really stored.
        DB::afterCommit(fn () => $this->deliver((int) $invitation->id, (string) $user->email, $url, (int) $user->id));

        return $url;
    }

    public function deliver(int $invitationId, string $email, string $url, int $userId): bool
    {
        if (! $this->canDeliver($invitationId, $email)) {
            return false;
        }

        return MailService::queue(new PlatformStaffInvitationMail($email, $url, $invitationId), $userId);
    }

    /** @return array{0: PlatformStaffInvitation, 1: User} */
    private function open(string $token, bool $lock = false): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D', $token) === 1, 404);
        $invitation = PlatformStaffInvitation::query()->where('token_hash', hash('sha256', $token))->whereNull('used_at')->where('expires_at', '>', now()->utc())
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
        $user = User::query()->where('status', 1)->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($invitation->user_id);
        abort_unless(hash_equals($invitation->email_hash, $this->emailHash((string) $user->email)) && $this->bound($invitation, $user), 404);

        return [$invitation, $user];
    }

    /** The link works only while the person is still active platform staff with a current grant. */
    private function bound(PlatformStaffInvitation $invitation, User $user): bool
    {
        return PlatformStaffIdentity::query()->where('user_id', $user->id)->where('active', true)->exists()
            && PlatformAdminGrant::query()->where('user_id', $user->id)->where('active', true)->where('expires_at', '>', now()->utc())->exists()
            && ! SchoolMembership::query()->where('user_id', $user->id)->exists();
    }

    private function staffMember(int $userId): User
    {
        abort_unless(PlatformStaffIdentity::query()->where('user_id', $userId)->exists(), 404);

        return User::query()->findOrFail($userId);
    }

    /**
     * Refuses a change that would leave the install without an active Owner.
     * Only Owners whose account can sign in count (PlatformStaffAccess::allowed):
     * not a disabled or deleted user.
     */
    private function keepAnOwner(User $user): void
    {
        $owners = PlatformAdminGrant::query()->where('role', 'owner')->where('active', true)->where('expires_at', '>', now()->utc())
            ->whereIn('user_id', PlatformStaffIdentity::query()->where('active', true)->select('user_id'))
            ->whereIn('user_id', User::query()->where('status', 1)->select('id'))->lockForUpdate()->pluck('user_id')->map(fn ($id) => (int) $id);
        if ($owners->count() === 1 && $owners->first() === (int) $user->id) {
            throw ValidationException::withMessages(['role' => __('Keep at least one active Owner. Make someone else an Owner first.')]);
        }
    }

    private function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }
}
