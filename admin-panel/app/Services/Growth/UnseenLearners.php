<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Growth\UnseenAssignment;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Settings;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Events\RiskRulesConfigured;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * "No child unseen" lists and named adults (Action Plan 4.5). Grade heads see
 * their current grades, a dated principal the whole school, and a named
 * adult only the learners they were asked to reach out to. Rows carry dates
 * and kinds of contact, never the content of a note, reason or award.
 * engineering/specs/growth/risk-interventions.md.
 */
class UnseenLearners
{
    public const NOTICE = 'growth.unseen_adult_assigned';

    public const LINK = '/risk-interventions/unseen';

    public const PER_PAGE = 50;

    /** Who the reader is here: their current grades, principal or named adult. */
    public function access(User $actor): array
    {
        $a = app(RiskAuthority::class);
        $a->enabled();
        $fresh = $a->actor($actor);
        $year = (int) schoolSetting('session_year');
        $today = app(AcademicAuthority::class)->today();
        $grades = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $principal = app(AcademicAuthority::class)->currentLeadership($fresh, 'principal', 0);
        $named = UnseenAssignment::where('adult_user_id', $fresh->id)->where('session_year_id', $year)->whereNull('cleared_at')->exists();
        abort_unless($principal || $grades !== [] || $named, 403);

        return ['user' => $fresh, 'session_year_id' => $year, 'today' => $today, 'principal' => $principal, 'grade_ids' => $grades, 'named' => $named,
            'grades' => GradeLevel::when(! $principal, fn ($q) => $q->whereIn('id', $grades))->orderBy('sort_order')->get(['id', 'name'])
                ->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name])->all()];
    }

    /** Unseen learners for a grade head's grades or, for a principal, the whole school. */
    public function list(User $actor, ?int $grade = null, int $page = 1): array
    {
        $access = $this->access($actor);
        abort_unless($access['principal'] || $access['grade_ids'] !== [], 403);
        if ($grade !== null) {
            abort_unless($access['principal'] || in_array($grade, $access['grade_ids'], true), 403);
        }
        $grades = $grade !== null ? [$grade] : ($access['principal'] ? null : $access['grade_ids']);
        $roster = $this->roster($access['session_year_id'], $access['today'], $grades);
        $result = app(PositiveContacts::class)->evaluate(array_keys($roster), $access['session_year_id'], $access['today']);
        $unseen = array_filter($result['learners'], fn (array $row) => $row['status'] === 'unseen');
        $unknown = count(array_filter($result['learners'], fn (array $row) => $row['status'] === 'unknown'));
        // Longest unseen first; no contact at all this year comes first of all.
        uksort($unseen, fn (int $a, int $b) => [$unseen[$a]['last_contact_on'] !== null, $unseen[$a]['last_contact_on'], $a] <=> [$unseen[$b]['last_contact_on'] !== null, $unseen[$b]['last_contact_on'], $b]);
        $total = count($unseen);
        $last = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $page), $last);
        $slice = array_slice($unseen, ($page - 1) * self::PER_PAGE, self::PER_PAGE, true);

        return ['school_days' => $result['school_days'], 'window_start' => $result['window_start'], 'sources' => $result['sources'], 'enabled' => $result['enabled'],
            'grade_level_id' => $grade, 'roster' => count($roster), 'unknown' => $unknown, 'total' => $total, 'page' => $page, 'last_page' => $last,
            'learners' => $this->rows($slice, $roster, $access['session_year_id'])];
    }

    /** The learners the reader was named for, with their current state. */
    public function mine(User $actor): array
    {
        $access = $this->access($actor);
        $open = UnseenAssignment::where('adult_user_id', $access['user']->id)->where('session_year_id', $access['session_year_id'])->whereNull('cleared_at')->orderBy('id')->get();
        $roster = $this->roster($access['session_year_id'], $access['today'], null, $open->pluck('learner_id')->map(fn ($id) => (int) $id)->all());
        $result = app(PositiveContacts::class)->evaluate(array_keys($roster), $access['session_year_id'], $access['today']);

        return $this->rows($result['learners'], $roster, $access['session_year_id']);
    }

    /** Active staff members of this school, never another school's. */
    public function staff(User $actor): array
    {
        $access = $this->access($actor);
        abort_unless($access['principal'] || $access['grade_ids'] !== [], 403);

        return $this->staffQuery()->orderBy('first_name')->orderBy('last_name')->orderBy('id')->limit(500)->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $u) => ['id' => (int) $u->id, 'name' => trim($u->first_name.' '.$u->last_name)])->all();
    }

    public function assign(User $actor, int $learner, array $input): UnseenAssignment
    {
        $data = Validator::make($input, ['adult_user_id' => ['required', 'integer', 'min:1']])->validate();
        $authorize = function () use ($actor, $learner): array {
            $a = app(RiskAuthority::class);
            $a->enabled(true);
            $fresh = $a->actor($actor);
            $year = (int) schoolSetting('session_year');
            $grade = $a->currentGrade($learner, $year);
            $authority = app(AcademicAuthority::class);
            abort_unless($authority->currentLeadership($fresh, 'principal', 0) || $authority->leads($fresh, 'grade_head', $grade, $year), 403);

            return [$fresh, $year];
        };
        [$fresh, $year] = $authorize();
        $adult = $this->staffQuery()->find((int) $data['adult_user_id']);
        if ($adult === null) {
            throw ValidationException::withMessages(['adult_user_id' => __('Choose a staff member at this school.')]);
        }
        $status = app(PositiveContacts::class)->evaluate([$learner], $year)['learners'][$learner]['status'];
        abort_unless($status === 'unseen', 422, __('This learner has a recent positive contact, so no named adult is needed.'));

        return DB::transaction(function () use ($authorize, $learner, $adult) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            [$fresh, $year] = $authorize();
            $open = UnseenAssignment::where('learner_id', $learner)->where('session_year_id', $year)->whereNull('cleared_at')->lockForUpdate()->get();
            foreach ($open as $row) {
                $row->update(['cleared_at' => now(), 'cleared_reason' => 'reassigned']);
            }
            $row = UnseenAssignment::create(['learner_id' => $learner, 'session_year_id' => $year, 'adult_user_id' => $adult->id,
                'assigned_by' => $fresh->id, 'assigned_on' => app(AcademicAuthority::class)->today()]);
            // Ids only: the audit names no note, reason or contact detail.
            Audit::record('growth.unseen_adult_assigned', $row, $open->isEmpty() ? [] : ['adult_user_id' => (int) $open->first()->adult_user_id],
                ['learner_id' => $learner, 'adult_user_id' => (int) $adult->id]);
            $notifier = app(Notifier::class);
            $notice = $notifier->makeNotice(self::NOTICE, __('A learner to check in with'), __('Open Early warning to see the learner you have been asked to check in with.'), self::LINK);
            $notifier->assign($notice, [(int) $adult->id]);

            return $row;
        });
    }

    /** Closes open named-adult rows once the learner has a recent positive contact. */
    public function clearSeen(int $learner, int $year, string $status): int
    {
        if ($status !== 'seen') {
            return 0;
        }
        $cleared = 0;
        foreach (UnseenAssignment::where('learner_id', $learner)->where('session_year_id', $year)->whereNull('cleared_at')->get() as $row) {
            $row->update(['cleared_at' => now(), 'cleared_reason' => 'contact_recorded']);
            Audit::record('growth.unseen_adult_cleared', $row, [], ['learner_id' => $learner, 'reason' => 'contact_recorded']);
            $cleared++;
        }

        return $cleared;
    }

    /** A dated principal sets N, the school days without contact before a learner is unseen. */
    public function configure(User $actor, array $input): int
    {
        app(RiskAuthority::class)->principal($actor, true);
        $data = Validator::make($input, ['school_days' => ['required', 'integer', 'between:'.PositiveContacts::MIN_SCHOOL_DAYS.','.PositiveContacts::MAX_SCHOOL_DAYS]])->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->principal($actor, true);
            // Settings rows are audited by their Auditable model.
            Settings::updateOrCreate(['type' => PositiveContacts::SETTING], ['message' => (string) $data['school_days']]);
            Outbox::record(new RiskRulesConfigured(app(CurrentSchool::class)->id()));

            return (int) $data['school_days'];
        });
    }

    /** @return Builder<User> */
    private function staffQuery(): Builder
    {
        return User::where('status', 1)->whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())
            ->whereNotIn('role', [SchoolRoles::PARENT, SchoolRoles::STUDENT])->select('user_id'))
            ->whereNotIn('id', PlatformStaffIdentity::where('active', true)->select('user_id'));
    }

    /**
     * Learners placed today in the current year, by grade: learner => grade.
     *
     * @return array<int, int>
     */
    private function roster(int $year, string $today, ?array $grades, ?array $learners = null): array
    {
        return Enrolment::where('session_year_id', $year)->onDate($today)
            ->whereIn('learner_id', Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('users.id'))->select('id'))
            ->when($learners !== null, fn ($q) => $q->whereIn('learner_id', $learners))
            ->whereHas('homeroom.class', fn ($q) => $grades === null ? $q->whereNotNull('grade_level_id') : $q->whereIn('grade_level_id', $grades))
            ->with('homeroom.class:id,grade_level_id')->orderBy('starts_on')->get()
            ->mapWithKeys(fn (Enrolment $e) => [(int) $e->learner_id => (int) $e->homeroom->class->grade_level_id])->all();
    }

    private function rows(array $evaluated, array $roster, int $year): array
    {
        $ids = array_keys($evaluated);
        $names = Students::whereIn('id', $ids)->with('user:id,first_name,last_name')->get()->keyBy('id');
        $grades = GradeLevel::whereIn('id', array_unique(array_values($roster)))->pluck('name', 'id');
        $open = UnseenAssignment::where('session_year_id', $year)->whereIn('learner_id', $ids)->whereNull('cleared_at')->get()->keyBy('learner_id');
        $adults = User::whereIn('id', $open->pluck('adult_user_id'))->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $rows = [];
        foreach ($evaluated as $learner => $row) {
            $assignment = $open->get($learner);
            $adult = $assignment === null ? null : $adults->get($assignment->adult_user_id);
            $user = $names->get($learner)?->user;
            $rows[] = ['learner_id' => (int) $learner, 'name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
                'grade' => $grades->get($roster[$learner] ?? 0), 'status' => $row['status'], 'last_contact_on' => $row['last_contact_on'],
                'school_days_since' => $row['school_days_since'], 'kinds' => $row['kinds'],
                'adult' => $assignment === null ? null : ['id' => (int) $assignment->adult_user_id, 'name' => trim(($adult?->first_name ?? '').' '.($adult?->last_name ?? '')),
                    'assigned_on' => $assignment->assigned_on->toDateString()]];
        }

        return $rows;
    }
}
