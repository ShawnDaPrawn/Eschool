<?php

declare(strict_types=1);

namespace App\Services\Learning\Strengths;

use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Learning\StrengthsMirrorShare;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The learner's own shares of their strengths mirror (Action Plan 4.8).
 *
 * Only the learner creates or ends a share, and only with one named person:
 *  - a guardian with an active approving link to them (guardian), or
 *  - a teacher at their school who currently teaches one of their teaching
 *    groups (teacher).
 * The viewer sees a read-only copy of the mirror taken when it was shared
 * (never live data), only the sections the learner chose, and only until the
 * learner revokes it or it expires (at most config strengths_mirror.
 * share_max_days). Every share, revocation and view is audited, without the
 * copy's content. A school leader, HQ, another guardian or teacher, or
 * anyone at another school has no share and so sees nothing.
 */
class StrengthsShares
{
    public const KINDS = ['guardian', 'teacher'];

    /** @return list<array{user_id: int, name: string, kind: string, detail: string}> */
    public function recipients(User $actor): array
    {
        return array_values($this->eligible(app(StrengthsAccess::class)->learner($actor)));
    }

    /** How many ended (revoked or expired) shares the learner's list shows. */
    public const HISTORY_LIMIT = 100;

    /**
     * The learner's own shares: every open share in full, newest first, so
     * one can always be found and ended, then the most recent ended ones.
     * Open shares are already bounded by max_open_shares.
     */
    public function mine(User $actor): array
    {
        $learner = app(StrengthsAccess::class)->learner($actor);
        $own = fn () => StrengthsMirrorShare::query()->where('learner_id', $learner->id)->where('owner_user_id', $learner->user_id);
        $now = now();
        $open = $own()->whereNull('revoked_at')->where('expires_at', '>', $now)->orderByDesc('id')->get();
        $ended = $own()->where(fn ($q) => $q->whereNotNull('revoked_at')->orWhere('expires_at', '<=', $now))->orderByDesc('id')->limit(self::HISTORY_LIMIT)->get();
        $rows = $open->concat($ended);
        $names = User::query()->whereIn('id', $rows->pluck('viewer_user_id')->unique())->get(['id', 'first_name', 'last_name'])->keyBy('id');

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $learner->user_id,
            'shares' => $rows->map(fn (StrengthsMirrorShare $row) => $this->summary($row, (string) ($names->get($row->viewer_user_id)?->full_name ?? '')))->values()->all()];
    }

    public function create(User $actor, array $data): array
    {
        $learner = app(StrengthsAccess::class)->learner($actor, true);
        $sections = array_values(array_intersect(StrengthsMirror::SECTIONS, $data['sections']));
        $days = (int) ($data['days'] ?? config('strengths_mirror.share_max_days', 30));
        $recipient = (int) $data['recipient_user_id'];
        $hash = hash('sha256', json_encode([$recipient, $sections, $days]));

        return DB::transaction(function () use ($actor, $learner, $data, $sections, $days, $recipient, $hash): array {
            // One learner's shares are written one at a time.
            Students::query()->whereKey($learner->id)->lockForUpdate()->firstOrFail();
            $old = StrengthsMirrorShare::query()->where('owner_user_id', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation was already used for another share.');

                return $this->summary($old, (string) User::query()->find($old->viewer_user_id)?->full_name);
            }
            $eligible = $this->eligible($learner);
            if (! isset($eligible[$recipient])) {
                throw ValidationException::withMessages(['recipient_user_id' => __('Choose someone from your list.')]);
            }
            $open = StrengthsMirrorShare::query()->where('learner_id', $learner->id)->whereNull('revoked_at')->where('expires_at', '>', now())->count();
            abort_if($open >= (int) config('strengths_mirror.max_open_shares', 20), 422, 'End an older share before making a new one.');
            $mirror = app(StrengthsMirror::class)->forLearner($actor);
            $snapshot = ['learner_id' => (int) $learner->id, 'first_name' => $mirror['first_name'], 'as_of' => $mirror['as_of'],
                'sections' => array_intersect_key($mirror['sections'], array_flip($sections))];
            $share = StrengthsMirrorShare::create(['learner_id' => $learner->id, 'owner_user_id' => $actor->id, 'viewer_user_id' => $recipient,
                'viewer_kind' => $eligible[$recipient]['kind'], 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'sections' => $sections,
                'snapshot' => $snapshot, 'snapshot_on' => $mirror['as_of'], 'expires_at' => now()->addDays($days)]);
            Audit::record('strengths.share_created', $share, [], ['learner_id' => (int) $learner->id, 'viewer_user_id' => $recipient,
                'viewer_kind' => $share->viewer_kind, 'sections' => $sections, 'expires_at' => $share->expires_at->toISOString()]);

            return $this->summary($share, $eligible[$recipient]['name']);
        }, 3);
    }

    public function revoke(User $actor, int $id): array
    {
        $learner = app(StrengthsAccess::class)->learner($actor, true);

        return DB::transaction(function () use ($actor, $learner, $id): array {
            $share = StrengthsMirrorShare::query()->where('learner_id', $learner->id)->where('owner_user_id', $actor->id)->lockForUpdate()->findOrFail($id);
            if ($share->revoked_at === null) {
                $share->update(['revoked_at' => now()]);
                Audit::record('strengths.share_revoked', $share, [], ['learner_id' => (int) $learner->id, 'viewer_user_id' => $share->viewer_user_id]);
            }

            return $this->summary($share, (string) User::query()->find($share->viewer_user_id)?->full_name);
        }, 3);
    }

    /** Open shares made to the signed-in guardian or teacher. */
    public function sharedWithMe(User $actor): array
    {
        $viewer = app(StrengthsAccess::class)->viewer($actor);
        $rows = StrengthsMirrorShare::query()->where('viewer_user_id', $viewer->id)->whereNull('revoked_at')->where('expires_at', '>', now())
            ->orderByDesc('id')->limit(100)->get()->filter(fn (StrengthsMirrorShare $row) => $this->stillShared($row, $viewer))->values();
        $learners = Students::query()->whereIn('id', $rows->pluck('learner_id'))->with('user:id,first_name,last_name')->get()->keyBy('id');

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $viewer->id,
            'shares' => $rows->map(fn (StrengthsMirrorShare $row) => ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id,
                'learner_name' => (string) ($learners->get($row->learner_id)?->user?->full_name ?? ''), 'kind' => $row->viewer_kind,
                'shared_on' => $row->snapshot_on->toDateString(), 'expires_at' => $row->expires_at->utc()->toISOString()])->all()];
    }

    /** The named viewer's read-only copy; anyone else, or an ended share, gets 404. */
    public function view(User $actor, int $id): array
    {
        $viewer = app(StrengthsAccess::class)->viewer($actor);
        $share = StrengthsMirrorShare::query()->where('viewer_user_id', $viewer->id)->findOrFail($id);
        abort_unless($share->open() && $this->stillShared($share, $viewer), 404);
        $share->forceFill(['last_viewed_at' => now()])->save();
        Audit::record('strengths.share_viewed', $share, [], ['learner_id' => (int) $share->learner_id, 'viewer_user_id' => (int) $viewer->id]);
        $snapshot = $share->snapshot;

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $viewer->id, 'id' => (int) $share->id,
            'kind' => $share->viewer_kind, 'shared_on' => $share->snapshot_on->toDateString(), 'expires_at' => $share->expires_at->utc()->toISOString(),
            'notice' => __(':name shared this with you on :date. It is a copy from that day, and only you can see it.',
                ['name' => $snapshot['first_name'] ?? '', 'date' => $share->snapshot_on->toDateString()]),
            'mirror' => $snapshot];
    }

    /**
     * Who this learner may share with today, keyed by user id.
     *
     * @return array<int, array{user_id: int, name: string, kind: string, detail: string}>
     */
    private function eligible(Students $learner): array
    {
        $result = [];
        foreach ($this->approvingGuardians($learner) as $guardian) {
            $result[$guardian['user_id']] = ['user_id' => $guardian['user_id'], 'name' => $guardian['name'], 'kind' => 'guardian', 'detail' => $guardian['relationship']];
        }
        $today = app(AcademicAuthority::class)->today();
        $groups = TeachingGroupMembership::query()->where('learner_id', $learner->id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->whereHas('group', fn ($q) => $q->where('active', true))->pluck('teaching_group_id')->unique();
        $allocations = TeachingAllocation::query()->whereIn('teaching_group_id', $groups)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->get(['teaching_group_id', 'teacher_id']);
        $teachers = Teacher::query()->whereIn('id', $allocations->pluck('teacher_id')->unique())->pluck('user_id', 'id');
        $users = $this->currentMembers($teachers->values()->all(), 'Teacher')->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $subjects = Subject::withTrashed()->whereIn('id', TeachingGroup::query()->whereIn('id', $groups)->pluck('subject_id'))->pluck('name', 'id');
        $groupSubjects = TeachingGroup::query()->whereIn('id', $groups)->pluck('subject_id', 'id');
        foreach ($allocations as $allocation) {
            $userId = (int) $teachers->get($allocation->teacher_id);
            $user = $users->get($userId);
            if ($user === null || (isset($result[$userId]) && $result[$userId]['kind'] === 'guardian')) {
                continue;
            }
            $subject = (string) $subjects->get($groupSubjects->get($allocation->teaching_group_id), '');
            $known = isset($result[$userId]) ? array_filter(explode(', ', $result[$userId]['detail'])) : [];
            $result[$userId] = ['user_id' => $userId, 'name' => (string) $user->full_name, 'kind' => 'teacher',
                'detail' => implode(', ', array_values(array_unique(array_filter([...$known, $subject]))))];
        }

        return $result;
    }

    /**
     * Active approving links to verified, active guardians who are current members here.
     *
     * @return list<array{user_id: int, name: string, relationship: string}>
     */
    private function approvingGuardians(Students $learner): array
    {
        $links = GuardianLink::query()->where('learner_id', $learner->id)->where('status', 'active')->where('can_approve', true)
            ->orderBy('id')->get(['guardian_user_id', 'relationship']);
        $users = $this->currentMembers($links->pluck('guardian_user_id')->map(fn ($id) => (int) $id)->all(), 'Parent')
            ->whereNotNull('email_verified_at')->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $result = [];
        foreach ($links as $link) {
            $user = $users->get($link->guardian_user_id);
            if ($user !== null && ! isset($result[(int) $user->id])) {
                $result[(int) $user->id] = ['user_id' => (int) $user->id, 'name' => (string) $user->full_name, 'relationship' => (string) $link->relationship];
            }
        }

        return array_values($result);
    }

    /** Active users who are current members of this school in the given role. */
    private function currentMembers(array $userIds, string $role)
    {
        return User::query()->whereIn('id', $userIds)->where('status', 1)
            ->whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('role', $role)->select('user_id'));
    }

    /**
     * A share stays visible only while the learner is still a current,
     * approved learner here and the viewer still holds the role it was made
     * for: an approving guardian link, or a current teacher of this school.
     */
    private function stillShared(StrengthsMirrorShare $share, User $viewer): bool
    {
        $learner = Students::query()->find($share->learner_id);
        if ($learner === null || ! app(ApprovalService::class)->approved($learner)
            || ! SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->exists()) {
            return false;
        }

        return match ($share->viewer_kind) {
            'guardian' => in_array((int) $viewer->id, array_column($this->approvingGuardians($learner), 'user_id'), true),
            'teacher' => $this->currentMembers([(int) $viewer->id], 'Teacher')->exists(),
            default => false,
        };
    }

    private function summary(StrengthsMirrorShare $row, string $name): array
    {
        $status = $row->revoked_at !== null ? 'revoked' : ($row->expires_at->isFuture() ? 'open' : 'expired');

        return ['id' => (int) $row->id, 'recipient_user_id' => (int) $row->viewer_user_id, 'recipient_name' => $name, 'kind' => $row->viewer_kind,
            'sections' => array_values($row->sections ?? []), 'shared_on' => $row->snapshot_on->toDateString(), 'expires_at' => $row->expires_at->utc()->toISOString(),
            'revoked_at' => $row->revoked_at?->utc()->toISOString(), 'last_viewed_at' => $row->last_viewed_at?->utc()->toISOString(), 'status' => $status];
    }
}
