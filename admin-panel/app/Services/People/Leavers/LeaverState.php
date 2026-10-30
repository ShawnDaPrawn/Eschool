<?php

declare(strict_types=1);

namespace App\Services\People\Leavers;

use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The leaver state (Action Plan 1.21). A learner marked as leaving a year
 * does not lose their account when that year becomes current: their Student
 * membership at the school becomes a leaver membership until leaver_until
 * (31 March after a switch; see untilForSwitch and untilMidYear). Until the end of that day they, and their guardians,
 * may read the school's records about them and write nothing for them there;
 * then their access to those records stops.
 *
 * Access is enforced by the existing gates, which read the membership:
 * MembershipService::active() and SetSchoolContext (which school a person may
 * enter, and LeaverAccess for writes), SchoolMembership::reachable() in
 * ProfilePolicy (whose roles count), LearnerAcademicAccess (dated learner
 * access) and GuardianService (whose guardian rights still count). A leaver
 * is never a current member: SchoolMembership::active() leaves them out, so
 * class lists, registers, groups, messages and new-year carries do too.
 *
 * engineering/specs/academics/new-year-structures.md, "Leaver state".
 */
class LeaverState
{
    public const VIEW = 'promote-student-list';

    public const EDIT = 'promote-student-edit';

    /** Leaver access ends on this day of the year (31 March) unless the office ends it sooner. */
    public const UNTIL_MONTH = 3;

    public const UNTIL_DAY = 31;

    public function __construct(
        private readonly MembershipService $memberships,
        private readonly GuardianService $guardians,
        private readonly EnrolmentService $enrolments,
    ) {}

    public function today(): string
    {
        return now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
    }

    /**
     * The last day for learners leaving at the switch into $year: 31 March of
     * the calendar year the new year starts in (31 March 2027 for a year
     * starting in January 2027). If that day has already passed when the
     * switch happens, access lasts config('leavers.access_days') days.
     */
    public function untilForSwitch(SessionYear $year): string
    {
        $until = CarbonImmutable::parse((string) $year->start_date)->setDate((int) CarbonImmutable::parse((string) $year->start_date)->format('Y'), self::UNTIL_MONTH, self::UNTIL_DAY)->toDateString();

        return $until >= $this->today() ? $until : $this->afterDays();
    }

    /**
     * The last day for a learner marked as leaving the year already in use:
     * config('leavers.access_days') days from today, never past the next 31
     * March.
     */
    public function untilMidYear(): string
    {
        $today = CarbonImmutable::parse($this->today());
        $march = $today->setDate((int) $today->format('Y'), self::UNTIL_MONTH, self::UNTIL_DAY);
        if ($march->toDateString() < $today->toDateString()) {
            $march = $march->addYear();
        }

        return min($this->afterDays(), $march->toDateString());
    }

    private function afterDays(): string
    {
        return CarbonImmutable::parse($this->today())->addDays(max(1, (int) config('leavers.access_days', 90)))->toDateString();
    }

    /**
     * True for a request that changes something: not a safe method, and not
     * a read sent as POST or a read receipt (config('leavers.read_only.allow')).
     * Outside a request (console, queue) nothing is a family write.
     */
    public static function writing(): bool
    {
        if (! app()->bound('request')) {
            return false;
        }
        $request = request();

        return ! $request->isMethodSafe() && ! $request->is(...(array) config('leavers.read_only.allow', []));
    }

    /**
     * Refuse a family write for a leaver: the learner themselves or a
     * guardian, whatever other roles the person holds. Called by the helpers
     * that authorise a learner or guardian for one learner (ReleasedMarks,
     * CheckChild, ParentAccess, LearnerAccessPolicy and others), so it does
     * not depend on how the request names the learner. Reads go on.
     */
    public function guardFamilyWrite(Students|int $learner): void
    {
        if (self::writing() && $this->isLeaver($learner instanceof Students ? (int) $learner->id : $learner)) {
            throw new AccessDeniedHttpException(trans('leaver_read_only'));
        }
    }

    /**
     * Learners marked as leaving a year that is now current enter the leaver
     * state; so does each of their guardians who has no current child left at
     * the school. Learners already in it (a second switch) are left alone.
     * Called by the switch (SessionYearService::applyPromotions, $midYear
     * false) and by the promotion pages when the year is already in use
     * ($midYear true), which check their own permissions.
     *
     * @param  iterable<int|string>  $learnerIds
     * @return int how many learners entered the leaver state
     */
    public function begin(iterable $learnerIds, SessionYear $year, bool $midYear = false): int
    {
        $ids = collect($learnerIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        // One transaction (a savepoint inside the switch's), so the outbox event commits with the change.
        return DB::transaction(fn () => $this->enter($ids->all(), $midYear ? $this->untilMidYear() : $this->untilForSwitch($year)));
    }

    /** @param  list<int>  $ids */
    private function enter(array $ids, string $until): int
    {
        $entered = 0;
        $guardians = [];
        foreach (Students::query()->with('user')->whereIn('id', $ids)->get() as $learner) {
            $membership = $this->studentMembership($learner)->where('status', SchoolMembership::ACTIVE)->lockForUpdate()->first();
            if ($membership === null) {
                continue;
            }
            $membership->fill(['status' => SchoolMembership::LEAVER, 'leaver_until' => $until, 'ends_on' => $until])->save();
            $entered++;
            array_push($guardians, ...$this->guardians->guardianUserIds($learner)->all());
            if ($learner->user !== null) {
                $this->memberships->forget($learner->user);
            }
            app(AlumniInvitations::class)->leaverStarted($learner, $membership);
        }
        $this->settleGuardians($guardians);
        if ($entered > 0) {
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'people.leavers_changed'));
        }

        return $entered;
    }

    /**
     * A learner the promotion page no longer marks as leaving (Promote
     * learners, "continue") is a current learner again, with their guardians.
     */
    public function cancel(Students $learner): void
    {
        DB::transaction(fn () => $this->reinstate($learner));
    }

    private function reinstate(Students $learner): void
    {
        $membership = $this->studentMembership($learner)->whereNotNull('leaver_until')
            ->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED])->lockForUpdate()->first();
        if ($membership === null) {
            return;
        }
        $membership->fill(['status' => SchoolMembership::ACTIVE, 'ends_on' => null, 'leaver_until' => null])->save();
        foreach ($this->guardians->guardianUserIds($learner) as $guardianId) {
            SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $guardianId)->where('role', 'Parent')
                ->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED])->get()
                ->each(fn (SchoolMembership $row) => $row->fill(['status' => SchoolMembership::ACTIVE, 'ends_on' => null, 'leaver_until' => null])->save());
            $this->forget((int) $guardianId);
        }
        if ($learner->user !== null) {
            $this->memberships->forget($learner->user);
        }
        Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'people.leavers_changed'));
    }

    /**
     * The office brings a leaver back as a returning learner: placed in a
     * homeroom of the current year from today (or the year's first day), a
     * current member again, with their guardians.
     *
     * @return array{learner_id: int, homeroom_id: int, starts_on: string}
     */
    public function restore(User $actor, int $learnerId, int $homeroomId): array
    {
        abort_unless($actor->can(self::EDIT), 403);

        return DB::transaction(function () use ($actor, $learnerId, $homeroomId) {
            $learner = Students::query()->lockForUpdate()->findOrFail($learnerId);
            $homeroom = ClassSection::query()->findOrFail($homeroomId);
            $year = SessionYear::query()->findOrFail((int) schoolSetting('session_year'));
            if (! $this->studentMembership($learner)->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED])->exists()) {
                throw ValidationException::withMessages(['learner' => __('This learner is not a leaver.')]);
            }
            $startsOn = max($this->today(), CarbonImmutable::parse((string) $year->start_date)->toDateString());
            if ($startsOn > CarbonImmutable::parse((string) $year->end_date)->toDateString()) {
                throw ValidationException::withMessages(['learner' => __('The current school year has ended.')]);
            }
            $this->enrolments->place((int) $learner->id, (int) $homeroom->id, (int) $year->id, $startsOn, 'Returning learner (was a leaver)');
            StudentSessions::query()->where('student_id', $learner->id)->where('session_year_id', $year->id)->update(['status' => 1, 'class_section_id' => $homeroom->id]);
            $this->cancel($learner);
            Audit::record('people.leaver_restored', $learner, [], ['session_year_id' => $year->id, 'homeroom_id' => $homeroom->id, 'starts_on' => $startsOn, 'by' => $actor->id]);

            return ['learner_id' => (int) $learner->id, 'homeroom_id' => (int) $homeroom->id, 'starts_on' => $startsOn];
        });
    }

    /**
     * The office ends a leaver's access today, before leaver_until. Their
     * guardians lose it too unless another of their children at the school is
     * still a current learner or a leaver. Anyone left with no active
     * membership anywhere is signed out of the apps and the portals.
     */
    public function endAccess(User $actor, int $learnerId): void
    {
        abort_unless($actor->can(self::EDIT), 403);

        DB::transaction(function () use ($actor, $learnerId) {
            $learner = Students::query()->lockForUpdate()->findOrFail($learnerId);
            $membership = $this->studentMembership($learner)->where('status', SchoolMembership::LEAVER)->lockForUpdate()->first();
            if ($membership === null) {
                throw ValidationException::withMessages(['learner' => __('This learner has no leaver access to end.')]);
            }
            $guardians = $this->guardians->guardianUserIds($learner)->all();
            $before = ['leaver_until' => $membership->leaver_until?->toDateString()];
            $membership->fill(['status' => SchoolMembership::ENDED, 'ends_on' => $this->today()])->save();
            $this->settleGuardians($guardians);
            foreach ([(int) $learner->user_id, ...array_map('intval', $guardians)] as $userId) {
                $this->signOutIfNoAccessLeft($userId);
            }
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'people.leavers_changed'));
            Audit::record('people.leaver_access_ended', $learner, $before, ['ends_on' => $this->today(), 'by' => $actor->id]);
        });
    }

    /**
     * The leavers the office sees, newest first: those still reading
     * (read-only until leaver_until) and those whose access has ended.
     *
     * @return LengthAwarePaginator<int, Students>
     */
    public function leavers(User $actor): LengthAwarePaginator
    {
        abort_unless($actor->can(self::VIEW), 403);
        $rows = SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')
            ->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED]);

        return Students::query()->with(['user', 'class_section.class', 'class_section.section'])
            ->joinSub($rows->select(['user_id', 'status as leaver_status', 'leaver_until', 'ends_on as leaver_ends_on']), 'leaver_rows', 'leaver_rows.user_id', '=', 'students.user_id')
            ->select(['students.*', 'leaver_rows.leaver_status', 'leaver_rows.leaver_until', 'leaver_rows.leaver_ends_on'])
            ->orderByDesc('leaver_rows.leaver_until')->orderBy('students.id')
            ->paginate(50);
    }

    /** How many learners are reading as leavers today (the panel counts them apart from current learners). */
    public function count(): int
    {
        return Students::query()->whereIn('user_id', $this->inPeriodMemberships()->select('user_id'))->count();
    }

    /** The learner's last day of leaver access, while it lasts; null otherwise. */
    public function until(Students $learner): ?string
    {
        $until = $this->studentMembership($learner)->where('status', SchoolMembership::LEAVER)
            ->whereDate('leaver_until', '>=', $this->today())->value('leaver_until');

        return $until === null ? null : CarbonImmutable::parse((string) $until)->toDateString();
    }

    /** True while the learner is a leaver: no family writes for them. */
    public function isLeaver(int $learnerId): bool
    {
        return Students::query()->whereKey($learnerId)
            ->whereIn('user_id', $this->leaverMemberships()->select('user_id'))->exists();
    }

    /** True once a leaver's access has ended (on leaver_until, or early): their guardians' rights stop. */
    public function accessEnded(Students $learner): bool
    {
        return SchoolMembership::query()->where('school_id', $learner->school_id)->where('role', 'Student')->where('user_id', $learner->user_id)
            ->whereNotNull('leaver_until')
            ->where(fn (Builder $ended) => $ended->where('status', SchoolMembership::ENDED)
                ->orWhere(fn (Builder $lapsed) => $lapsed->where('status', SchoolMembership::LEAVER)->whereDate('leaver_until', '<', $this->today())))
            ->exists();
    }

    /**
     * A learner row (students) whose leaver access at its school has ended,
     * for a whereNotExists on students.
     */
    public function whereAccessEnded(QueryBuilder $query): QueryBuilder
    {
        return $query->selectRaw('1')->from('school_memberships as leaver_ended')
            ->whereColumn('leaver_ended.user_id', 'students.user_id')
            ->whereColumn('leaver_ended.school_id', 'students.school_id')->where('leaver_ended.role', 'Student')
            ->whereNotNull('leaver_ended.leaver_until')
            ->where(fn (QueryBuilder $ended) => $ended->where('leaver_ended.status', SchoolMembership::ENDED)
                ->orWhere(fn (QueryBuilder $lapsed) => $lapsed->where('leaver_ended.status', SchoolMembership::LEAVER)->whereDate('leaver_ended.leaver_until', '<', $this->today())));
    }

    /** @return Builder<SchoolMembership> */
    private function studentMembership(Students $learner): Builder
    {
        return SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())
            ->where('user_id', $learner->user_id)->where('role', 'Student');
    }

    /** @return Builder<SchoolMembership> */
    private function leaverMemberships(): Builder
    {
        return SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')
            ->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED]);
    }

    /** @return Builder<SchoolMembership> */
    private function inPeriodMemberships(): Builder
    {
        return SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')
            ->where('status', SchoolMembership::LEAVER)->whereDate('leaver_until', '>=', $this->today());
    }

    /**
     * Settle each guardian's membership after one of their children left, was
     * removed or transferred out: a guardian with a current child here is
     * unchanged; one whose children here are all leavers still reading is a
     * leaver until the last of their dates; one with neither loses access
     * today. Called by begin and endAccess, RemoveLearner and the outgoing
     * transfer (TransferEnrollment::closeSource).
     *
     * @param  iterable<int|string>  $guardianUserIds
     */
    public function settleGuardians(iterable $guardianUserIds): void
    {
        $school = app(CurrentSchool::class)->id();
        foreach (collect($guardianUserIds)->map(fn ($id) => (int) $id)->unique() as $guardianUserId) {
            $membership = SchoolMembership::query()->where('school_id', $school)->where('user_id', $guardianUserId)->where('role', 'Parent')
                ->whereIn('status', [SchoolMembership::ACTIVE, SchoolMembership::LEAVER])->lockForUpdate()->first();
            if ($membership === null) {
                continue;
            }
            $children = fn () => $this->guardians->childrenFor($guardianUserId, (int) (Parents::query()->where('user_id', $guardianUserId)->value('id') ?? 0));
            $current = SchoolMembership::query()->active()->where('school_id', $school)->where('role', 'Student')->select('user_id');
            if ($children()->whereIn('user_id', $current)->exists()) {
                continue;
            }
            $until = $this->inPeriodMemberships()->whereIn('user_id', $children()->select('user_id'))->max('leaver_until');
            if ($until !== null) {
                $last = CarbonImmutable::parse((string) $until)->toDateString();
                $membership->fill(['status' => SchoolMembership::LEAVER, 'leaver_until' => $last, 'ends_on' => $last])->save();
            } else {
                $membership->fill(['status' => SchoolMembership::ENDED, 'ends_on' => $this->today()])->save();
            }
            $this->forget($guardianUserId);
        }
    }

    /** No active membership left anywhere: end the person's app tokens and portal sessions. */
    private function signOutIfNoAccessLeft(int $userId): void
    {
        $user = User::acrossSchools()->find($userId);
        if ($user === null) {
            return;
        }
        $this->memberships->forget($user);
        if ($this->memberships->active($user)->isEmpty()) {
            AppTokens::revokeAll($user);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    private function forget(int $userId): void
    {
        $user = User::query()->find($userId);
        if ($user !== null) {
            $this->memberships->forget($user);
        }
    }
}
