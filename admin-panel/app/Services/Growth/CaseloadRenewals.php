<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\RiskCaseload;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Caseload renewals for a new school year (Action Plan 1.20). Risk caseload
 * grants belong to a year, so each open grant of last year is offered for
 * renewal into the new one: the same professional where they still have
 * restricted pastoral access, another eligible professional, or not at all.
 * Closed grants (ended early, or past their last day) are never offered.
 *
 * Only someone who administers caseloads (RiskAuthority::administer: a dated
 * principal, or the learner's grade head) sees or renews a case, and only
 * cases they administer in both years. A renewal writes one ordinary grant
 * per case through Interventions::assign, dated within the new year, so it
 * gives no one access the administrator could not give by hand. Refused if
 * any grant changed since the page was shown.
 * engineering/specs/growth/caseload-renewals.md.
 */
class CaseloadRenewals
{
    /** Bounds the professionals offered, as the assignment picker bounds its pages. */
    private const PROFESSIONALS = 500;

    /**
     * Open cases offered in one submission, well within PHP's default
     * max_input_vars (1000); the rest are offered once these are renewed.
     */
    public const PER_SUBMISSION = 500;

    /**
     * @return array{to: SessionYear, from: SessionYear, starts_on: string, ends_on: string, cases: list<array<string, mixed>>, waiting: int, unplaced: int, professionals: array<int, string>, fingerprint: string}
     */
    public function plan(User $actor, SessionYear $to): array
    {
        $fresh = $this->manager($actor);
        $a = app(RiskAuthority::class);
        $today = app(AcademicAuthority::class)->today();
        $endsOn = CarbonImmutable::parse($to->end_date)->toDateString();
        if ($endsOn < $today) {
            throw ValidationException::withMessages(['year' => __('Caseloads cannot be renewed into a school year that has ended.')]);
        }
        // Last year is the year just before, even if it was deleted: an older year never stands in for it.
        $from = SessionYear::withTrashed()->where('start_date', '<', $to->start_date)->whereKeyNot($to->id)->orderByDesc('start_date')->first();
        if ($from === null) {
            throw ValidationException::withMessages(['year' => __('There is no earlier school year to renew caseloads from.')]);
        }
        if ($from->trashed()) {
            throw ValidationException::withMessages(['year' => __('The school year before this one was deleted, so its caseloads cannot be renewed.')]);
        }
        $startsOn = max(CarbonImmutable::parse($to->start_date)->toDateString(), $today);
        // Open: not ended early, and running now or until the end of last year.
        $openUntil = min(CarbonImmutable::parse($from->end_date)->toDateString(), $today);
        $grants = RiskCaseload::query()->where('session_year_id', $from->id)->whereNull('revoked_at')->whereDate('ends_on', '>=', $openUntil)
            ->orderBy('learner_id')->orderBy('id')->get();
        $placed = Enrolment::query()->where('session_year_id', $to->id)->whereIn('learner_id', $grants->pluck('learner_id'))->pluck('learner_id')->map(fn ($id) => (int) $id)->flip();
        $professionals = $this->professionals();
        $learners = Students::query()->with('user')->whereIn('id', $grants->pluck('learner_id'))->get()->keyBy('id');
        $people = User::query()->whereIn('id', $grants->pluck('professional_id'))->get()->keyBy('id');

        $cases = [];
        $unplaced = 0;
        $allowed = [];
        foreach ($grants as $grant) {
            $learner = (int) $grant->learner_id;
            $allowed[$learner] ??= $this->administers($fresh, $learner, (int) $from->id) && (! $placed->has($learner) || $this->administers($fresh, $learner, (int) $to->id, $startsOn));
            if (! $allowed[$learner]) {
                continue;
            }
            if (! $placed->has($learner)) {
                $unplaced++;

                continue;
            }
            $renewed = RiskCaseload::query()->where('learner_id', $learner)->where('session_year_id', $to->id)->orderBy('id')->get();
            $person = $people[(int) $grant->professional_id] ?? null;
            $cases[] = [
                'id' => (int) $grant->id, 'revision' => (int) $grant->revision, 'learner_id' => $learner,
                'learner' => (string) ($learners->get($learner)?->user?->full_name ?? '#'.$learner),
                'professional_id' => (int) $grant->professional_id,
                'professional' => $person ? trim($person->first_name.' '.$person->last_name) : __('Previously assigned professional'),
                'starts_on' => $grant->starts_on->toDateString(), 'ends_on' => $grant->ends_on->toDateString(),
                'still_eligible' => isset($professionals[(int) $grant->professional_id]),
                // Already over: offered, but not kept unless chosen again.
                'lapsed' => $grant->ends_on->toDateString() < $today,
                'renewed' => $renewed->map(fn (RiskCaseload $row) => ['id' => (int) $row->id, 'revision' => (int) $row->revision,
                    'professional_id' => (int) $row->professional_id, 'revoked' => $row->revoked_at !== null])->all(),
            ];
        }

        $offered = 0;
        foreach ($cases as &$case) {
            $case['in_form'] = $case['renewed'] === [] && ++$offered <= self::PER_SUBMISSION;
        }
        unset($case);

        return [
            'to' => $to, 'from' => $from, 'starts_on' => $startsOn, 'ends_on' => $endsOn,
            'cases' => $cases, 'waiting' => max(0, $offered - self::PER_SUBMISSION), 'unplaced' => $unplaced, 'professionals' => $professionals,
            'fingerprint' => hash('sha256', json_encode([(int) $from->id, (int) $to->id, $startsOn, $endsOn, $unplaced,
                array_map(fn (array $c) => [$c['id'], $c['revision'], $c['learner_id'], $c['professional_id'], $c['still_eligible'], $c['renewed']], $cases),
                array_keys($professionals)], JSON_THROW_ON_ERROR)),
        ];
    }

    /**
     * Renews each chosen case: case id => the professional to hold it in the
     * new year (the same one, or another eligible one); a case left out or
     * left empty is not renewed.
     *
     * @param  array<int|string, int|string|null>  $choices
     * @return array{renewed: int, reassigned: int}
     */
    public function apply(User $actor, SessionYear $to, array $choices, string $fingerprint): array
    {
        $this->manager($actor);
        app(RiskAuthority::class)->enabled(true);

        return DB::transaction(function () use ($actor, $to, $choices, $fingerprint) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $plan = $this->plan($actor, $to);
            if (! hash_equals($plan['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['fingerprint' => __('Caseloads changed since this page was shown. Check the renewals again, then renew.')]);
            }
            $open = collect($plan['cases'])->filter(fn (array $c) => $c['in_form'])->keyBy('id');
            $writes = [];
            foreach ($choices as $id => $professional) {
                if (! ctype_digit((string) $id) || ! $open->has((int) $id)) {
                    throw ValidationException::withMessages(['cases' => __('A case on this page cannot be renewed. Check the renewals again, then renew.')]);
                }
                if ($professional === null || $professional === '') {
                    continue;
                }
                if (! ctype_digit((string) $professional) || ! isset($plan['professionals'][(int) $professional])) {
                    throw ValidationException::withMessages(['cases' => __('Choose a professional who has restricted pastoral access.')]);
                }
                $case = $open[(int) $id];
                $writes[$case['learner_id'].':'.$professional] = [$case, (int) $professional];
            }
            $done = ['renewed' => 0, 'reassigned' => 0];
            foreach ($writes as [$case, $professional]) {
                app(Interventions::class)->assign($actor, $case['learner_id'], (int) $to->id, $professional,
                    ['starts_on' => $plan['starts_on'], 'ends_on' => $plan['ends_on'], 'expected_revision' => 0], $plan['starts_on']);
                $done[$professional === $case['professional_id'] ? 'renewed' : 'reassigned']++;
            }
            Audit::record('growth.risk_caseloads_renewed', $to, [], ['from_year' => (int) $plan['from']->id, 'to_year' => (int) $to->id, 'counts' => $done, 'by' => (int) $actor->id]);

            return $done;
        }, 3);
    }

    /** Someone who administers caseloads: a dated principal or grade head, with the module on. */
    private function manager(User $actor): User
    {
        $config = app(RiskQueries::class)->configuration($actor);
        abort_unless($config['can_manage'] || $config['grade_ids'] !== [], 403);

        return app(RiskAuthority::class)->actor($actor);
    }

    /** Without $on: administers the year as of today; with it, a grant in the year starting on $on. */
    private function administers(User $actor, int $learner, int $year, ?string $on = null): bool
    {
        try {
            $on === null ? app(RiskAuthority::class)->administer($actor, $learner, $year) : app(RiskAuthority::class)->administerOn($actor, $learner, $year, $on);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** @return array<int, string> professionals with restricted pastoral access now: user id => name */
    private function professionals(): array
    {
        $rows = User::query()->where('status', 1)->whereIn('id', SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())
            ->whereIn('role', [SchoolRoles::COUNSELLOR, SchoolRoles::SAFEGUARDING])->select('user_id'))
            ->orderBy('first_name')->orderBy('last_name')->orderBy('id')->limit(self::PROFESSIONALS)->get();
        $people = [];
        foreach ($rows as $person) {
            try {
                app(SensitiveRecordsAccess::class)->actor($person);
                $people[(int) $person->id] = trim($person->first_name.' '.$person->last_name);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }

        return $people;
    }
}
