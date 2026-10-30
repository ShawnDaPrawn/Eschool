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
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The leaver state (Action Plan 1.21). A learner marked as leaving a year
 * does not lose their account when that year becomes current: their Student
 * membership at the school becomes a leaver membership until leaver_until
 * (31 March by default). Until the end of that day they, and their guardians,
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
        return now(app(CurrentSchool::class)->school()?->timezone ?? config('app.timezone', 'UTC'))->toDateString();
    }

    /**
     * The default last day: the first 31 March on or after the later of today
     * and the new year's first day (31 March 2027 for a year starting in
     * January 2027, whether the switch is in December or January).
     */
    public function defaultUntil(SessionYear $year): string
    {
        $from = max($this->today(), CarbonImmutable::parse((string) $year->start_date)->toDateString());
        $until = CarbonImmutable::parse($from)->setDate((int) substr($from, 0, 4), self::UNTIL_MONTH, self::UNTIL_DAY);

        return ($until->toDateString() < $from ? $until->addYear() : $until)->toDateString();
    }

    /**
     * Learners marked as leaving a year that is now current enter the leaver
     * state; so does each of their guardians who has no current child left at
     * the school. Learners already in it (a second switch) are left alone.
     * Called by the switch (SessionYearService::applyPromotions) and the
     * promotion pages, which check their own permissions.
     *
     * @param  iterable<int|string>  $learnerIds
     * @return int how many learners entered the leaver state
     */
    public function begin(iterable $learnerIds, SessionYear $year): int
    {
        $ids = collect($learnerIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }
        $until = $this->defaultUntil($year);
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
        foreach (array_unique($guardians) as $guardianId) {
            $this->settleGuardian((int) $guardianId);
        }

        return $entered;
    }

    /**
     * A learner the promotion page no longer marks as leaving (Promote
     * learners, "continue") is a current learner again, with their guardians.
     */
    public function cancel(Students $learner): void
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
     * still a current learner or a leaver.
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
            foreach ($guardians as $guardianId) {
                $this->settleGuardian((int) $guardianId);
            }
            if ($learner->user !== null) {
                $this->memberships->forget($learner->user);
            }
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
     * A guardian whose children at the school are all leavers becomes a
     * leaver too, until the last of their leaver_until dates; one with none
     * left reading loses access today. One with a current child is unchanged.
     */
    private function settleGuardian(int $guardianUserId): void
    {
        $school = app(CurrentSchool::class)->id();
        $membership = SchoolMembership::query()->where('school_id', $school)->where('user_id', $guardianUserId)->where('role', 'Parent')
            ->whereIn('status', [SchoolMembership::ACTIVE, SchoolMembership::LEAVER])->lockForUpdate()->first();
        if ($membership === null) {
            return;
        }
        $children = fn () => $this->guardians->childrenFor($guardianUserId, (int) (Parents::query()->where('user_id', $guardianUserId)->value('id') ?? 0));
        $current = SchoolMembership::query()->active()->where('school_id', $school)->where('role', 'Student')->select('user_id');
        if ($children()->whereIn('user_id', $current)->exists()) {
            return;
        }
        $until = $this->inPeriodMemberships()->whereIn('user_id', $children()->select('user_id'))->max('leaver_until');
        if ($until !== null) {
            $membership->fill(['status' => SchoolMembership::LEAVER, 'leaver_until' => CarbonImmutable::parse((string) $until)->toDateString(), 'ends_on' => CarbonImmutable::parse((string) $until)->toDateString()])->save();
        } elseif ($membership->status === SchoolMembership::LEAVER) {
            $membership->fill(['status' => SchoolMembership::ENDED, 'ends_on' => $this->today()])->save();
        }
        $this->forget($guardianUserId);
    }

    private function forget(int $userId): void
    {
        $user = User::query()->find($userId);
        if ($user !== null) {
            $this->memberships->forget($user);
        }
    }
}
