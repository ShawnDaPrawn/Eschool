<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The school office manages a learner's guardians: any number of them, each
 * with a relationship and two rights (approve the learner's access and school
 * decisions; see fees). A learner always keeps at least one active guardian who
 * can approve. Ending a link takes effect at once: when the person has no other
 * child at this school, their parent role here ends, and when they have no
 * child anywhere, their parent-app sign-ins are revoked.
 * Specification: engineering/specs/platform/guardian-management.md.
 */
class GuardianLinks
{
    public const LEGACY = 'legacy';

    public const MANAGED = 'managed';

    public const RELATIONSHIPS = ['mother', 'father', 'guardian', 'grandparent', 'aunt_or_uncle', 'sibling', 'foster_parent', 'caregiver', 'other'];

    public static function addRules(): array
    {
        return ['email' => ['required', 'email', 'max:191'], 'first_name' => ['required', 'string', 'max:128'], 'last_name' => ['required', 'string', 'max:128'],
            'mobile' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +()-]+$/']] + self::rightsRules();
    }

    public static function rightsRules(): array
    {
        return ['relationship' => ['required', Rule::in(self::RELATIONSHIPS)], 'can_approve' => ['required', 'boolean'], 'can_see_fees' => ['required', 'boolean']];
    }

    /** @return list<array<string, mixed>> active and ended links, active first */
    public function list(User $actor, int $learnerId): array
    {
        $learner = $this->learner($actor, $learnerId);

        return GuardianLink::query()->where('learner_id', $learner->id)->orderByRaw("status = 'active' desc")->orderBy('id')->get()
            ->map(function (GuardianLink $link) {
                $person = User::query()->withTrashed()->find($link->guardian_user_id);

                return ['id' => (int) $link->id, 'name' => trim(($person?->first_name ?? '').' '.($person?->last_name ?? '')), 'email' => $person?->email,
                    'relationship' => $link->relationship, 'can_approve' => $link->can_approve, 'can_see_fees' => $link->can_see_fees,
                    'status' => $link->status, 'source' => $link->source, 'ended_at' => $link->ended_at, 'ended_reason' => $link->ended_reason];
            })->all();
    }

    /** Links an existing person by email, or creates their account, as a guardian of the learner. */
    public function add(User $actor, int $learnerId, array $input): GuardianLink
    {
        $data = Validator::make($input, self::addRules())->validate();
        $learner = $this->learner($actor, $learnerId);
        $email = strtolower(trim($data['email']));

        $link = DB::transaction(function () use ($learner, $data, $email) {
            $guardian = User::acrossSchools()->withTrashed()->firstOrCreate(['email' => $email], [
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'mobile' => $data['mobile'] ?? '',
                'gender' => 'Not specified', 'password' => bin2hex(random_bytes(32)), 'status' => 1]);
            $created = $guardian->wasRecentlyCreated;
            $guardian = User::acrossSchools()->withTrashed()->whereKey($guardian->id)->lockForUpdate()->firstOrFail();
            if ($guardian->trashed() || (int) $guardian->status !== 1 || (int) $guardian->id === (int) $learner->user_id) {
                throw ValidationException::withMessages(['email' => __('This email address cannot be added as a guardian. Ask the person to contact the school.')]);
            }
            $school = app(CurrentSchool::class)->id();
            abort_if(SchoolMembership::query()->where('school_id', $school)->where('user_id', $guardian->id)->where('role', 'Parent')->where('status', SchoolMembership::SUSPENDED)->exists(),
                409, __('Review this person’s suspended school membership first.'));
            if ($created) {
                $guardian->forceFill(['password_change_required' => true])->save();
            }
            $existing = GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)->lockForUpdate()->first();
            if ($existing?->status === 'active') {
                throw ValidationException::withMessages(['email' => __('This person is already a guardian of this learner.')]);
            }
            $guardian->unsetRelation('roles')->unsetRelation('permissions')->assignRole('Parent');
            app(MembershipService::class)->forget($guardian);
            Parents::query()->firstOrCreate(['user_id' => $guardian->id], ['first_name' => $guardian->first_name, 'last_name' => $guardian->last_name,
                'email' => $guardian->email, 'mobile' => $guardian->mobile ?? '', 'gender' => $guardian->gender ?? 'Not specified']);
            $before = $existing?->getAttributes() ?? [];
            $link = $existing ?? new GuardianLink(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id]);
            $link->fill(['relationship' => $data['relationship'], 'can_approve' => (bool) $data['can_approve'], 'can_see_fees' => (bool) $data['can_see_fees'],
                'status' => 'active', 'source' => self::MANAGED, 'ended_at' => null, 'ended_by' => null, 'ended_reason' => null])->save();
            Audit::record('guardian_link_added', $link, $before, $link->getAttributes());
            Outbox::record(new ContextChanged($school, 'people.guardians_changed'));

            return $link;
        }, 3);
        $guardian = User::query()->findOrFail($link->guardian_user_id);
        if ($guardian->email_verified_at === null) {
            // The invitation verifies their email address before they can sign in or approve anything.
            app(GuardianInvitations::class)->issue($guardian, $actor);
        }

        return $link;
    }

    public function update(User $actor, int $linkId, array $input): GuardianLink
    {
        $data = Validator::make($input, self::rightsRules())->validate();

        return DB::transaction(function () use ($actor, $linkId, $data) {
            $link = GuardianLink::query()->where('status', 'active')->lockForUpdate()->findOrFail($linkId);
            $this->learner($actor, (int) $link->learner_id);
            if ($link->can_approve && ! $data['can_approve']) {
                $this->keepAnApprover($link);
            }
            $before = $link->getAttributes();
            $link->fill(['relationship' => $data['relationship'], 'can_approve' => (bool) $data['can_approve'], 'can_see_fees' => (bool) $data['can_see_fees']])->save();
            Audit::record('guardian_link_changed', $link, $before, $link->getAttributes());
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'people.guardians_changed'));

            return $link;
        }, 3);
    }

    /** Ends a guardian link now. Refused for the learner's last approving guardian. */
    public function end(User $actor, int $linkId, array $input): void
    {
        $data = Validator::make($input, ['reason' => ['required', 'string', 'min:5', 'max:500']])->validate();
        DB::transaction(function () use ($actor, $linkId, $data) {
            $link = GuardianLink::query()->where('status', 'active')->lockForUpdate()->findOrFail($linkId);
            $this->learner($actor, (int) $link->learner_id);
            if ($link->can_approve) {
                $this->keepAnApprover($link);
            }
            $before = $link->getAttributes();
            $link->fill(['status' => 'ended', 'ended_at' => now(), 'ended_by' => $actor->id, 'ended_reason' => $data['reason']])->save();
            Audit::record('guardian_link_ended', $link, $before, $link->getAttributes());
            $school = app(CurrentSchool::class)->id();
            $guardian = User::query()->withTrashed()->findOrFail($link->guardian_user_id);
            $children = app(GuardianService::class)->childrenFor((int) $guardian->id, (int) (Parents::query()->where('user_id', $guardian->id)->value('id') ?? 0))->count();
            if ($children === 0) {
                // No child left here: the parent role ends, so every school-scoped read refuses at once.
                $guardian->unsetRelation('roles')->unsetRelation('permissions')->removeRole('Parent');
                app(MembershipService::class)->forget($guardian);
            }
            $elsewhere = GuardianLink::acrossSchools()->where('guardian_user_id', $guardian->id)->where('status', 'active')->exists();
            if ($children === 0 && ! $elsewhere) {
                AppTokens::revokeApp($guardian, AppTokens::PARENT);
            }
            Outbox::record(new ContextChanged($school, 'people.guardians_changed'));
        }, 3);
    }

    private function keepAnApprover(GuardianLink $link): void
    {
        $others = GuardianLink::query()->where('learner_id', $link->learner_id)->where('status', 'active')->where('can_approve', true)
            ->whereKeyNot($link->id)->lockForUpdate()->count();
        if ($others === 0) {
            throw ValidationException::withMessages(['guardian' => __('A learner must keep at least one guardian who can approve. Add another approving guardian first.')]);
        }
    }

    private function learner(User $actor, int $learnerId): Students
    {
        $learner = Students::query()->findOrFail($learnerId);
        abort_unless(Gate::forUser($actor)->allows('manageGuardians', $learner), 403);
        // Parents still held only in the legacy father, mother and guardian fields get their link first.
        app(GuardianService::class)->syncLegacy($learner);

        return $learner;
    }
}
