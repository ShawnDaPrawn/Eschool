<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * One account can belong to several schools (Action Plan 1.3), so no single
 * school owns the users row of a person who also belongs elsewhere. A school
 * may change only its own records about them, and end its own roles; it may
 * not change their name, email address, date of birth or other account
 * details, deactivate the account or delete it. Only the person (or HQ)
 * changes those. A changed email address followed by "forgot password" would
 * otherwise hand the person's access at every school to the school that
 * changed it.
 *
 * Specification: engineering/specs/platform/existing-account-invitations.md.
 */
class SharedAccounts
{
    /** The account's own details: the users columns a school may set for its own people only. */
    public const ACCOUNT_FIELDS = ['email', 'first_name', 'last_name', 'dob', 'gender', 'mobile', 'current_address', 'permanent_address', 'image', 'status'];

    /**
     * Whether the person belongs to a school other than the current one (a
     * membership there that has not ended), or is platform staff.
     */
    public function shared(int $userId): bool
    {
        $school = app(CurrentSchool::class)->idOrNull();
        if ($school === null) {
            return false;
        }

        return SchoolMembership::query()->where('user_id', $userId)->where('school_id', '!=', $school)->where('status', '!=', SchoolMembership::ENDED)->exists()
            || PlatformStaffIdentity::query()->where('user_id', $userId)->exists();
    }

    /**
     * The proposed account details that differ from the account's own, for a
     * shared account changed by someone else (none for one this school alone
     * holds, or for the person themselves).
     *
     * @param  array<string, mixed>  $proposed  column => value the form would write
     * @return list<string>
     */
    public function changes(User $user, array $proposed): array
    {
        // The person may change their own details.
        if ((Auth::hasUser() && (int) Auth::id() === (int) $user->id) || ! $this->shared((int) $user->id)) {
            return [];
        }
        $changed = [];
        foreach ($proposed as $field => $value) {
            if ($this->normalise($field, $user->getRawOriginal($field)) !== $this->normalise($field, $value)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /** Whether the account's details are not this school's to change: shared, and someone else is changing them. */
    public function locked(User $user): bool
    {
        return ! (Auth::hasUser() && (int) Auth::id() === (int) $user->id) && $this->shared((int) $user->id);
    }

    public function message(): string
    {
        return __('This person\'s name, email address and other account details belong to their own NavigateEd account, and only they can change them. Save without changing these details.');
    }

    /** Whether the person has an active membership in this role at the current school. */
    public function activeHere(int $userId, string $role): bool
    {
        return SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $userId)->where('role', $role)->exists();
    }

    /**
     * The school ends its own staff roles for the person (Spatie at this school's
     * team; the membership follows through MembershipService). Their account,
     * any Parent or Student role here and every other school are untouched.
     *
     * @param  list<string>|null  $roles  null: every staff role here
     */
    public function endStaffRolesHere(User $user, ?array $roles = null): void
    {
        $user->unsetRelation('roles')->unsetRelation('permissions');
        foreach ($user->getRoleNames() as $role) {
            if (in_array($role, [SchoolRoles::PARENT, SchoolRoles::STUDENT], true) || ($roles !== null && ! in_array($role, $roles, true))) {
                continue;
            }
            $user->removeRole($role);
        }
        app(MembershipService::class)->forget($user);
    }

    /**
     * The model-level backstop (User saving and deleting): inside a school's
     * request, someone other than the person may not change a shared account's
     * details or delete it. Platform code (no school, across schools,
     * impersonation), the console and the person themselves are not limited.
     */
    public static function guard(User $user, bool $deleting = false): void
    {
        if (! $user->exists || ! RequestContext::inHttpRequest() || ! Auth::hasUser() || (int) Auth::id() === (int) $user->getKey()) {
            return;
        }
        $current = app(CurrentSchool::class);
        if (! $current->isSet() || $current->isImpersonating() || $current->isAcrossSchools()) {
            return;
        }
        if (! $deleting && array_intersect(array_keys($user->getDirty()), self::ACCOUNT_FIELDS) === []) {
            return;
        }
        $self = app(self::class);
        if ($self->shared((int) $user->getKey())) {
            throw new ConflictHttpException($self->message());
        }
    }

    private function normalise(string $field, mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return match ($field) {
            'email', 'gender' => mb_strtolower($value),
            'dob' => $value === '' || strtotime($value) === false ? $value : date('Y-m-d', (int) strtotime($value)),
            default => $value,
        };
    }
}
