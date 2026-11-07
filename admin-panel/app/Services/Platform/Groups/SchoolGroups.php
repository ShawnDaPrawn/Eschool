<?php

declare(strict_types=1);

namespace App\Services\Platform\Groups;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolGroup;
use App\Models\Platform\SchoolGroupAdmin;
use App\Models\Platform\SchoolGroupMember;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\SupportAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * School groups: one owner, many schools (Action Plan 5.7, SA.18).
 *
 * HQ (groups.manage) creates a group, invites schools and names the group's
 * administrators. A school joins only when its own Super Admin agrees in the
 * school panel, after reading what the group will see; the agreement records
 * who, when, the wording's version and a hash of the text. The school may
 * leave at any time, and HQ may remove it; either ends the group's view of
 * the school at once (the dashboard reads active memberships on every
 * request). A school has at most one open membership (invited or active) in
 * any group, enforced by a unique index.
 *
 * Membership rows are school-owned and written inside the school; groups and
 * administrator grants are platform rows. Everything is audited.
 * engineering/specs/platform/school-groups.md.
 */
class SchoolGroups
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    // ---- rules (FormRequests and the service use the same ones) ----

    public static function createRules(): array
    {
        return ['name' => ['required', 'string', 'min:3', 'max:120', Rule::unique('school_groups', 'name')], 'notes' => ['nullable', 'string', 'max:500']];
    }

    public static function inviteRules(): array
    {
        return ['school_id' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string', 'max:500']];
    }

    public static function reasonRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public static function adminRules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:191'], 'reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public static function billingRules(): array
    {
        return ['billing' => ['required', 'string', Rule::in([SchoolGroupMember::BILLED_ALONE, SchoolGroupMember::BILLED_THROUGH_GROUP])]];
    }

    /** What a school's Super Admin agrees to, in the reader's language. */
    public static function agreementText(string $group, ?string $locale = null): string
    {
        return __('By agreeing, your school joins :group. The group\'s administrators will see your school\'s name and these figures for it, next to the group\'s other schools: how many learners you have, weekly active families, guardian reach, learners active in the app this week and your recorded attendance rate. Numbers smaller than :k are hidden. They will never see a learner\'s, parent\'s or staff member\'s name or record, and being a group administrator gives nobody any access inside your school. If Navigate Ed bills your school through the group, your monthly invoice lines appear on the group\'s invoice instead of on an invoice to your school. You can leave the group at any time on this page, and the group stops seeing your school at once.',
            ['group' => $group, 'k' => max(1, (int) config('school_groups.min_count', 10))], $locale);
    }

    /** The hash kept with an agreement: the version and the English wording with the group's name. */
    public static function agreementHash(string $group): string
    {
        return hash('sha256', (string) config('school_groups.agreement_version', '').'|'.self::agreementText($group, 'en'));
    }

    // ---- HQ ----

    public function staff(?User $user, string $ability): User
    {
        $access = app(PlatformStaffAccess::class);
        abort_unless($user !== null && $access->can($user, $ability), 403);

        return $access->actor($user);
    }

    /** Every group with how many schools are in it, invited, and its administrators. */
    public function list(User $actor): array
    {
        $this->staff($actor, 'groups.view');

        return $this->currentSchool->acrossSchools(function (): array {
            $counts = DB::table('school_group_members')->whereNotNull('open_key')->groupBy('school_group_id', 'status')
                ->selectRaw('school_group_id, status, COUNT(*) AS n')->get()->groupBy('school_group_id');
            $admins = SchoolGroupAdmin::query()->whereNotNull('open_key')->groupBy('school_group_id')->selectRaw('school_group_id, COUNT(*) AS n')->pluck('n', 'school_group_id');

            return SchoolGroup::query()->orderBy('name')->get()->map(fn (SchoolGroup $g): array => [
                'id' => (int) $g->id, 'name' => (string) $g->name, 'status' => (string) $g->status,
                'schools' => (int) (($counts->get($g->id) ?? collect())->firstWhere('status', SchoolGroupMember::ACTIVE)->n ?? 0),
                'invited' => (int) (($counts->get($g->id) ?? collect())->firstWhere('status', SchoolGroupMember::INVITED)->n ?? 0),
                'admins' => (int) ($admins[$g->id] ?? 0),
            ])->all();
        });
    }

    public function create(User $actor, array $input): SchoolGroup
    {
        $actor = $this->staff($actor, 'groups.manage');
        $data = Validator::make($input, self::createRules())->validate();

        return $this->currentSchool->acrossSchools(function () use ($actor, $data): SchoolGroup {
            $group = SchoolGroup::query()->create(['name' => trim($data['name']), 'notes' => $data['notes'] ?? null, 'status' => SchoolGroup::ACTIVE, 'created_by' => $actor->id]);
            Audit::record('platform_school_group_created', $group, [], ['school_group_id' => $group->id, 'name' => $group->name, 'changed_by' => $actor->id]);

            return $group;
        });
    }

    /** One group for HQ: its memberships (every state), administrators and the schools that could be invited. */
    public function show(User $actor, int $groupId): array
    {
        $this->staff($actor, 'groups.view');

        return $this->currentSchool->acrossSchools(function () use ($groupId): array {
            $group = $this->group($groupId);
            $names = DB::table('schools')->pluck('name', 'id');
            $members = SchoolGroupMember::acrossSchools()->where('school_group_id', $group->id)->orderByRaw('open_key IS NULL')->orderByDesc('invited_at')->orderByDesc('id')->get();
            $people = $this->names([...$members->pluck('decided_by'), ...$members->pluck('invited_by'), ...$members->pluck('ended_by')]);
            $open = DB::table('school_group_members')->whereNotNull('open_key')->pluck('school_id')->map(fn ($id) => (int) $id)->all();
            $admins = SchoolGroupAdmin::query()->where('school_group_id', $group->id)->orderByRaw('open_key IS NULL')->orderBy('id')->get();
            $adminPeople = DB::table('users')->whereIn('id', $admins->pluck('user_id'))->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id');

            return [
                'group' => ['id' => (int) $group->id, 'name' => (string) $group->name, 'status' => (string) $group->status, 'notes' => $group->notes],
                'members' => $members->map(fn (SchoolGroupMember $m): array => [
                    'id' => (int) $m->id, 'school_id' => (int) $m->getAttribute('school_id'), 'school' => (string) ($names[$m->getAttribute('school_id')] ?? ''),
                    'status' => (string) $m->status, 'billing' => (string) $m->billing, 'invited_at' => $this->local($m->invited_at), 'invitation_note' => $m->invitation_note,
                    'decided_by' => $people[(int) $m->decided_by] ?? null, 'decided_at' => $this->local($m->decided_at), 'agreement_version' => $m->agreement_version,
                    'ended_at' => $this->local($m->ended_at), 'ended_side' => $m->ended_side, 'end_reason' => $m->end_reason,
                ])->all(),
                'admins' => $admins->map(fn (SchoolGroupAdmin $a): array => [
                    'id' => (int) $a->id, 'role' => (string) $a->role, 'standing' => $a->open_key !== null,
                    'name' => trim(($adminPeople[$a->user_id]->first_name ?? '').' '.($adminPeople[$a->user_id]->last_name ?? '')), 'email' => (string) ($adminPeople[$a->user_id]->email ?? ''),
                    'reason' => $a->reason, 'revoked_at' => $this->local($a->revoked_at), 'revoke_reason' => $a->revoke_reason,
                ])->all(),
                'invitable' => DB::table('schools')->where('status', School::ACTIVE)->whereNotIn('id', $open)->orderBy('name')->pluck('name', 'id')->all(),
            ];
        });
    }

    /** HQ invites a school. Nothing is shared until the school's Super Admin agrees. */
    public function invite(User $actor, int $groupId, array $input): SchoolGroupMember
    {
        $actor = $this->staff($actor, 'groups.manage');
        $data = Validator::make($input, self::inviteRules())->validate();
        $group = $this->currentSchool->acrossSchools(fn () => $this->group($groupId));
        abort_unless($group->status === SchoolGroup::ACTIVE, 409);
        $schoolId = (int) $data['school_id'];
        $school = $this->currentSchool->acrossSchools(fn () => DB::table('schools')->where('id', $schoolId)->first(['id', 'status']));
        if ($school === null || $school->status !== School::ACTIVE) {
            throw ValidationException::withMessages(['school_id' => __('Choose an active school.')]);
        }
        try {
            return $this->currentSchool->run($schoolId, fn (): SchoolGroupMember => DB::transaction(function () use ($actor, $group, $data, $schoolId): SchoolGroupMember {
                $member = SchoolGroupMember::query()->create([
                    'school_group_id' => $group->id, 'status' => SchoolGroupMember::INVITED, 'open_key' => 1, 'billing' => SchoolGroupMember::BILLED_ALONE,
                    'invited_by' => $actor->id, 'invited_at' => now(), 'invitation_note' => $data['note'] ?? null,
                ]);
                Audit::record('platform_school_group_invited', $member, [], ['member_id' => $member->id, 'school_group_id' => $group->id, 'group' => $group->name, 'changed_by' => $actor->id], $schoolId);

                return $member;
            }, 3));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['school_id' => __('This school is already in a group or invited to one. It can be in one group at a time.')]);
        }
    }

    /** HQ withdraws an invitation, or removes a school; the group stops seeing it at once. */
    public function endByHq(User $actor, int $groupId, int $memberId, array $input): SchoolGroupMember
    {
        $actor = $this->staff($actor, 'groups.manage');
        $data = Validator::make($input, self::reasonRules())->validate();

        return $this->onMember($groupId, $memberId, function (SchoolGroupMember $member) use ($actor, $data): SchoolGroupMember {
            $status = match ($member->status) {
                SchoolGroupMember::INVITED => SchoolGroupMember::WITHDRAWN,
                SchoolGroupMember::ACTIVE => SchoolGroupMember::REMOVED,
                default => abort(409, __('This school is no longer in the group or invited to it.')),
            };

            return $this->end($member, $actor, $status, 'hq', $data['reason']);
        });
    }

    /** Whether a member school is billed alone or through the group, from the next group draft on (billing.manage). */
    public function setBilling(User $actor, int $groupId, int $memberId, array $input): SchoolGroupMember
    {
        $actor = $this->staff($actor, 'billing.manage');
        $data = Validator::make($input, self::billingRules())->validate();

        return $this->onMember($groupId, $memberId, function (SchoolGroupMember $member) use ($actor, $data): SchoolGroupMember {
            abort_unless($member->isOpen(), 409, __('This school is no longer in the group or invited to it.'));
            $before = $member->billing;
            $member->forceFill(['billing' => $data['billing']])->save();
            Audit::record('platform_school_group_billing_changed', $member, ['billing' => $before], ['member_id' => $member->id, 'billing' => $member->billing, 'changed_by' => $actor->id], (int) $member->getAttribute('school_id'));

            return $member;
        });
    }

    /**
     * Names an existing account as a group administrator. The account keeps
     * exactly its school roles; this grants the group's dashboard only.
     */
    public function addAdmin(User $actor, int $groupId, array $input): SchoolGroupAdmin
    {
        $actor = $this->staff($actor, 'groups.manage');
        $data = Validator::make($input, self::adminRules())->validate();

        return $this->currentSchool->acrossSchools(function () use ($actor, $groupId, $data): SchoolGroupAdmin {
            $group = $this->group($groupId);
            abort_unless($group->status === SchoolGroup::ACTIVE, 409);
            $person = User::query()->withoutGlobalScopes()->where('email', mb_strtolower(trim($data['email'])))->whereNull('deleted_at')->first(['id', 'status']);
            if ($person === null || (int) $person->status !== 1) {
                throw ValidationException::withMessages(['email' => __('No active NavigateEd account has this email address. The person needs an account first.')]);
            }
            if (PlatformStaffIdentity::query()->where('user_id', $person->id)->exists()) {
                throw ValidationException::withMessages(['email' => __('Navigate Ed staff cannot be group administrators. HQ sees groups in the platform area.')]);
            }
            try {
                $grant = DB::transaction(fn (): SchoolGroupAdmin => SchoolGroupAdmin::query()->create([
                    'school_group_id' => $group->id, 'user_id' => $person->id, 'role' => 'admin', 'open_key' => 1, 'granted_by' => $actor->id, 'reason' => $data['reason'],
                ]), 3);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => __('This person is already an administrator of the group.')]);
            }
            Audit::record('platform_school_group_admin_granted', $grant, [], ['grant_id' => $grant->id, 'school_group_id' => $group->id, 'user_id' => $person->id, 'role' => 'admin', 'reason' => $data['reason'], 'changed_by' => $actor->id]);

            return $grant;
        });
    }

    public function revokeAdmin(User $actor, int $groupId, int $grantId, array $input): SchoolGroupAdmin
    {
        $actor = $this->staff($actor, 'groups.manage');
        $data = Validator::make($input, self::reasonRules())->validate();

        return $this->currentSchool->acrossSchools(fn (): SchoolGroupAdmin => DB::transaction(function () use ($actor, $groupId, $grantId, $data): SchoolGroupAdmin {
            $grant = SchoolGroupAdmin::query()->where('school_group_id', $groupId)->whereKey($grantId)->lockForUpdate()->first();
            abort_if($grant === null, 404);
            abort_if($grant->open_key === null, 409, __('This administrator was already revoked.'));
            $grant->forceFill(['open_key' => null, 'revoked_at' => now(), 'revoked_by' => $actor->id, 'revoke_reason' => $data['reason']])->save();
            Audit::record('platform_school_group_admin_revoked', $grant, [], ['grant_id' => $grant->id, 'school_group_id' => $groupId, 'user_id' => $grant->user_id, 'reason' => $data['reason'], 'changed_by' => $actor->id]);

            return $grant;
        }, 3));
    }

    // ---- the school's own panel (its Super Admin) ----

    /** The school's Super Admin, as for support access: never in a support session, impersonation or across schools, never platform staff. */
    public function schoolAdmin(?User $user): User
    {
        return app(SupportAccess::class)->schoolAdmin($user);
    }

    /** This school's invitations waiting for an answer, its current group and its history, with the wording to agree to. */
    public function schoolPage(User $actor): array
    {
        $this->schoolAdmin($actor);
        $members = SchoolGroupMember::query()->orderByDesc('invited_at')->orderByDesc('id')->limit(100)->get();
        $groups = $this->currentSchool->acrossSchools(fn () => SchoolGroup::query()->whereIn('id', $members->pluck('school_group_id'))->pluck('name', 'id'));
        $present = fn (SchoolGroupMember $m): array => [
            'id' => (int) $m->id, 'group' => (string) ($groups[$m->school_group_id] ?? ''), 'status' => (string) $m->status, 'billing' => (string) $m->billing,
            'invited_at' => $this->local($m->invited_at), 'note' => $m->invitation_note, 'decided_at' => $this->local($m->decided_at), 'ended_at' => $this->local($m->ended_at),
            'agreement' => self::agreementText((string) ($groups[$m->school_group_id] ?? '')),
        ];

        return [
            'invitations' => $members->where('status', SchoolGroupMember::INVITED)->map($present)->values()->all(),
            'current' => $members->where('status', SchoolGroupMember::ACTIVE)->map($present)->values()->all(),
            'history' => $members->reject(fn (SchoolGroupMember $m) => $m->isOpen())->map($present)->values()->all(),
        ];
    }

    /** The school's Super Admin agrees: the group sees the school's counts from now on. */
    public function agree(User $actor, int $memberId): SchoolGroupMember
    {
        $actor = $this->schoolAdmin($actor);

        return DB::transaction(function () use ($actor, $memberId): SchoolGroupMember {
            $member = $this->lockOwn($memberId);
            abort_unless($member->status === SchoolGroupMember::INVITED, 409, __('This invitation is no longer waiting for an answer.'));
            $group = $this->currentSchool->acrossSchools(fn () => SchoolGroup::query()->find($member->school_group_id));
            abort_unless($group !== null && $group->status === SchoolGroup::ACTIVE, 409, __('This invitation is no longer waiting for an answer.'));
            $member->forceFill(['status' => SchoolGroupMember::ACTIVE, 'decided_by' => $actor->id, 'decided_at' => now(),
                'agreement_version' => (string) config('school_groups.agreement_version'), 'agreement_hash' => self::agreementHash((string) $group->name)])->save();
            Audit::record('platform_school_group_joined', $member, ['status' => SchoolGroupMember::INVITED], ['member_id' => $member->id, 'school_group_id' => $member->school_group_id,
                'agreement_version' => $member->agreement_version, 'agreement_hash' => $member->agreement_hash, 'changed_by' => $actor->id], (int) $member->getAttribute('school_id'));

            return $member;
        }, 3);
    }

    public function decline(User $actor, int $memberId): SchoolGroupMember
    {
        $actor = $this->schoolAdmin($actor);

        return DB::transaction(function () use ($actor, $memberId): SchoolGroupMember {
            $member = $this->lockOwn($memberId);
            abort_unless($member->status === SchoolGroupMember::INVITED, 409, __('This invitation is no longer waiting for an answer.'));
            $member->forceFill(['status' => SchoolGroupMember::DECLINED, 'open_key' => null, 'decided_by' => $actor->id, 'decided_at' => now()])->save();
            Audit::record('platform_school_group_declined', $member, ['status' => SchoolGroupMember::INVITED], ['member_id' => $member->id, 'school_group_id' => $member->school_group_id, 'changed_by' => $actor->id], (int) $member->getAttribute('school_id'));

            return $member;
        }, 3);
    }

    /** The school leaves: the group stops seeing it at once, and its month is billed alone again. */
    public function leave(User $actor, int $memberId, array $input): SchoolGroupMember
    {
        $actor = $this->schoolAdmin($actor);
        $data = Validator::make($input, ['reason' => ['nullable', 'string', 'max:500']])->validate();

        return DB::transaction(function () use ($actor, $memberId, $data): SchoolGroupMember {
            $member = $this->lockOwn($memberId);
            abort_unless($member->status === SchoolGroupMember::ACTIVE, 409, __('Your school is not in this group.'));

            return $this->end($member, $actor, SchoolGroupMember::LEFT, 'school', $data['reason'] ?? null);
        }, 3);
    }

    // ---- internals ----

    private function end(SchoolGroupMember $member, User $actor, string $status, string $side, ?string $reason): SchoolGroupMember
    {
        $before = $member->status;
        $member->forceFill(['status' => $status, 'open_key' => null, 'ended_at' => now(), 'ended_by' => $actor->id, 'ended_side' => $side, 'end_reason' => $reason])->save();
        // A draft group invoice no longer rolls the school up: its own draft is free to be billed alone.
        $released = app(SchoolGroupBilling::class)->releaseSchool((int) $member->getAttribute('school_id'));
        Audit::record('platform_school_group_'.$status, $member, ['status' => $before], ['member_id' => $member->id, 'school_group_id' => $member->school_group_id,
            'status' => $status, 'side' => $side, 'reason' => $reason, 'released_group_drafts' => $released, 'changed_by' => $actor->id], (int) $member->getAttribute('school_id'));

        return $member;
    }

    /** A membership of this group, locked, worked on inside its school; another group's id is not found. */
    private function onMember(int $groupId, int $memberId, callable $change): SchoolGroupMember
    {
        $schoolId = $this->currentSchool->acrossSchools(fn () => SchoolGroupMember::acrossSchools()->where('school_group_id', $groupId)->whereKey($memberId)->value('school_id'));
        abort_if($schoolId === null, 404);

        return $this->currentSchool->run((int) $schoolId, fn (): SchoolGroupMember => DB::transaction(function () use ($groupId, $memberId, $change): SchoolGroupMember {
            $member = SchoolGroupMember::query()->where('school_group_id', $groupId)->whereKey($memberId)->lockForUpdate()->first();
            abort_if($member === null, 404);

            return $change($member);
        }, 3));
    }

    /** One of the current school's memberships (another school's id is not found). */
    private function lockOwn(int $memberId): SchoolGroupMember
    {
        $member = SchoolGroupMember::query()->whereKey($memberId)->lockForUpdate()->first();
        abort_if($member === null, 404);

        return $member;
    }

    private function group(int $groupId): SchoolGroup
    {
        $group = SchoolGroup::query()->find($groupId);
        abort_if($group === null, 404);

        return $group;
    }

    /** @param  iterable<mixed>  $ids @return array<int, string> */
    private function names(iterable $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : iterator_to_array($ids)))));

        return DB::table('users')->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u): array => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();
    }

    private function local(mixed $at): ?string
    {
        return $at === null ? null : $at->copy()->timezone('Africa/Johannesburg')->format('Y-m-d H:i');
    }
}
