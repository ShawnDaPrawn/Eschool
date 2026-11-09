<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Shared\SharedCompetition;
use App\Models\Shared\SharedFixture;
use App\Models\Shared\SharedParticipant;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Shared\SharedRecords;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Inter-school fixtures (engineering/specs/activities/inter-school-fixtures.md),
 * the first use of shared records. A fixture is a SharedFixture between a host
 * school and either a guest school (which must accept) or an opponent outside
 * NavigateEd entered by name. Each school keeps its own side: its activity
 * group, team label, team sheet, notes and its own report of the score.
 */
class Fixtures
{
    public const TYPE = SharedFixture::RECORD;

    public function __construct(private SharedRecords $shared, private CommunityAuthority $access) {}

    public static function rules(string $action): array
    {
        $op = ['operation_id' => ['required', 'uuid']];
        $schedule = ['on_date' => ['required', 'date_format:Y-m-d'], 'starts_at' => ['nullable', 'date_format:H:i'], 'venue' => ['required', 'string', 'min:2', 'max:191'],
            'field' => ['nullable', 'string', 'max:100'], 'host_is_home' => ['required', 'boolean']];
        $label = ['team_label' => ['required', 'string', 'min:1', 'max:100']];

        return match ($action) {
            'index' => ['page' => ['sometimes', 'integer', 'between:1,10000']],
            'schools' => ['search' => ['required', 'string', 'min:3', 'max:100']],
            'create' => $op + $schedule + $label + ['activity_group_id' => ['required', 'integer', 'min:1'], 'discipline' => ['required', 'string', 'min:2', 'max:100'],
                'shared_competition_id' => ['nullable', 'integer', 'min:1'],
                'guest_school_id' => ['nullable', 'integer', 'min:1', 'required_without:external_opponent', 'prohibits:external_opponent'],
                'external_opponent' => ['nullable', 'string', 'min:2', 'max:191', 'required_without:guest_school_id']],
            'schedule' => $op + $schedule + ['revision' => ['required', 'integer', 'min:0'], 'status' => ['required', Rule::in(['scheduled', 'cancelled'])]],
            'decision' => $op + ['decision' => ['required', Rule::in(['accept', 'decline'])], 'activity_group_id' => ['required_if:decision,accept', 'nullable', 'integer', 'min:1'],
                'team_label' => ['required_if:decision,accept', 'nullable', 'string', 'min:1', 'max:100']],
            'side' => $op + $label + ['revision' => ['required', 'integer', 'min:0'], 'activity_group_id' => ['sometimes', 'integer', 'min:1'], 'sheet_shared' => ['required', 'boolean'],
                'notes' => ['nullable', 'string', 'max:2000'], 'selections' => ['present', 'array', 'max:60'], 'selections.*' => ['array:learner_id,sheet_label,position'],
                'selections.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'], 'selections.*.sheet_label' => ['nullable', 'string', 'max:60'], 'selections.*.position' => ['nullable', 'string', 'max:40']],
            'result' => $op + ['host_score' => ['required', 'integer', 'between:0,999'], 'guest_score' => ['required', 'integer', 'between:0,999']],
            default => [],
        };
    }

    /** Managers make, schedule and answer fixtures; assigned group staff keep their own side and report scores. */
    private function side(User $actor, FixtureSide $side, bool $write): void
    {
        $this->access->groupStaff($actor, (int) $side->activity_group_id, $write);
    }

    /** This school's participation and side, or "not found" for a school (or person) outside it. */
    private function mine(User $actor, int $id, bool $write = false): array
    {
        $this->shared->ownStaff();
        $this->access->staff($actor, $write);
        $part = $this->shared->requireHere(self::TYPE, $id);
        $fixture = SharedFixture::query()->findOrFail($id);
        $side = FixtureSide::query()->where('shared_fixture_id', $id)->first();
        if (! $this->access->manages($actor)) {
            abort_if($side === null, 404);
            $this->side($actor, $side, $write);
        }

        return [$fixture, $part, $side];
    }

    private function group(int $groupId, string $date): ActivityGroup
    {
        $group = ActivityGroup::query()->where('active', true)->findOrFail($groupId);
        abort_if($date < $group->starts_on->toDateString() || $date > $group->ends_on->toDateString(), 422, 'The fixture date must be within the group\'s season.');

        return $group;
    }

    public function create(User $actor, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('create'))->validate();

        return $this->shared->once($actor, 'fixture:new', $data, fn () => $this->access->manage($actor), fn () => $this->view($actor, $this->arrange($actor, $data, 'sport')));
    }

    /**
     * Make the shared fixture (of a kind, with any further shared facts), this
     * school's accepted host row, the invitation to the guest school and this
     * school's own side. Inside a write that SharedRecords::once guards.
     */
    public function arrange(User $actor, array $data, string $kind, array $facts = []): int
    {
        $this->group((int) $data['activity_group_id'], $data['on_date']);
        $guest = isset($data['guest_school_id']) ? (int) $data['guest_school_id'] : null;
        $competition = isset($data['shared_competition_id']) ? (int) $data['shared_competition_id'] : null;
        if ($competition !== null) {
            $member = $this->shared->here(SharedCompetition::RECORD, $competition);
            abort_unless($member?->state === SharedParticipant::ACCEPTED, 404);
            abort_unless((string) SharedCompetition::query()->whereKey($competition)->value('kind') === $kind, 422, 'Choose a competition of the same kind.');
            if ($guest !== null) {
                abort_unless($this->shared->participants(SharedCompetition::RECORD, $competition)->contains(fn ($row) => $row['school_id'] === $guest && $row['state'] === SharedParticipant::ACCEPTED), 422, 'The other school must be in the competition first.');
            }
        }
        $fixture = SharedFixture::query()->create($facts + ['kind' => $kind, 'shared_competition_id' => $competition, 'external_opponent' => $guest === null ? trim($data['external_opponent']) : null,
            'host_is_home' => (bool) $data['host_is_home'], 'discipline' => trim($data['discipline']), 'on_date' => $data['on_date'], 'starts_at' => $this->time($data['starts_at'] ?? null),
            'venue' => trim($data['venue']), 'field' => $this->blank($data['field'] ?? null), 'status' => 'scheduled', 'result_state' => 'none', 'revision' => 1]);
        $this->shared->start(self::TYPE, (int) $fixture->id, 'host', $actor);
        if ($guest !== null) {
            $this->shared->invite(self::TYPE, (int) $fixture->id, $guest, 'guest');
        }
        $side = FixtureSide::query()->create(['shared_fixture_id' => $fixture->id, 'activity_group_id' => (int) $data['activity_group_id'], 'team_label' => trim($data['team_label']), 'sheet_shared' => false, 'revision' => 1]);
        Audit::record('fixture.created', $side, [], ['shared_fixture_id' => (int) $fixture->id, 'activity_group_id' => (int) $side->activity_group_id, 'kind' => $kind]);

        return (int) $fixture->id;
    }

    public function schedule(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('schedule'))->validate();
        $authority = function () use ($actor, $id) {
            [, $part] = $this->mine($actor, $id, true);
            abort_unless($part->role === 'host', 403, 'Only the host school changes the fixture details.');
        };

        return $this->shared->once($actor, 'fixture-schedule:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            abort_unless($fixture->revision === (int) $data['revision'], 409, 'This fixture changed. Refresh before saving.');
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            abort_if($fixture->result_state !== 'none' && $fixture->on_date->toDateString() !== $data['on_date'], 409, 'A fixture with a reported score keeps its date.');
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->firstOrFail();
            if ($data['status'] === 'scheduled' && $data['on_date'] !== $fixture->on_date->toDateString()) {
                $this->suitsBothTeams($id, $data['on_date']);
            }
            $before = $fixture->only(['on_date', 'starts_at', 'venue', 'field', 'host_is_home', 'status', 'revision']);
            $fixture->forceFill(['on_date' => $data['on_date'], 'starts_at' => $this->time($data['starts_at'] ?? null), 'venue' => trim($data['venue']), 'field' => $this->blank($data['field'] ?? null),
                'host_is_home' => (bool) $data['host_is_home'], 'status' => $data['status'], 'revision' => $fixture->revision + 1])->save();
            Audit::record('fixture.scheduled', $side, $before, $fixture->only(['on_date', 'starts_at', 'venue', 'field', 'host_is_home', 'status', 'revision']));

            return $this->view($actor, $id);
        });
    }

    /**
     * A new date must suit every accepted school's team: its group's season
     * covers it and everyone on its team sheet is still in the group that
     * day. Otherwise the change is refused, nothing is cleared, and the coach
     * updates the sheet or the schools agree another date. Each school is
     * asked inside its own school, with locking reads; only a yes or no comes
     * back, never its group, learners or sheet.
     */
    private function suitsBothTeams(int $id, string $date): void
    {
        $me = app(CurrentSchool::class)->id();
        foreach ($this->shared->participants(self::TYPE, $id) as $part) {
            if ($part['state'] !== SharedParticipant::ACCEPTED) {
                continue;
            }
            $problem = $this->shared->inSchool($part['school_id'], fn () => $this->teamProblem($id, $date));
            if ($problem !== null) {
                abort(422, $part['school_id'] === $me
                    ? ($problem === 'season' ? 'The fixture date must be within the group\'s season.' : 'Some learners on your team sheet are not in the group on the new date. Update the team sheet first.')
                    : 'The new date does not suit the other school\'s team. Agree another date with them, or ask them to update their team sheet first.');
            }
        }
    }

    /** In the side's own school: null when the date suits its team, else why not. */
    private function teamProblem(int $id, string $date): ?string
    {
        $side = FixtureSide::query()->where('shared_fixture_id', $id)->sharedLock()->first();
        if ($side === null) {
            return null;
        }
        $group = ActivityGroup::query()->where('active', true)->sharedLock()->find($side->activity_group_id);
        if ($group === null || $date < $group->starts_on->toDateString() || $date > $group->ends_on->toDateString()) {
            return 'season';
        }
        $picked = FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->sharedLock()->pluck('learner_id')->map(fn ($v) => (int) $v)->all();
        $members = ActivityMembership::query()->where('activity_group_id', $group->id)->where('active', true)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
            ->sharedLock()->pluck('learner_id')->map(fn ($v) => (int) $v)->all();

        return array_diff($picked, $members) === [] ? null : 'sheet';
    }

    /** The invited school accepts (with its own group and team label) or declines. */
    public function decide(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('decision'))->validate();

        return $this->shared->once($actor, 'fixture-decision:'.$id, $data, fn () => $this->mine($actor, $id, true), function () use ($actor, $id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            $accept = $data['decision'] === 'accept';
            if ($accept) {
                $this->group((int) $data['activity_group_id'], $fixture->on_date->toDateString());
            }
            $this->shared->decide(self::TYPE, $id, $actor, $accept);
            if ($accept) {
                $side = FixtureSide::query()->create(['shared_fixture_id' => $id, 'activity_group_id' => (int) $data['activity_group_id'], 'team_label' => trim($data['team_label']), 'sheet_shared' => false, 'revision' => 1]);
                Audit::record('fixture.accepted', $side, [], ['shared_fixture_id' => $id, 'activity_group_id' => (int) $side->activity_group_id]);
            }

            return $this->view($actor, $id);
        });
    }

    /** This school's own side: group, label, team sheet (and whether to share it) and private notes. */
    public function saveSide(User $actor, int $id, array $input): array
    {
        $this->access->staff($actor, true);
        $data = Validator::make($input, self::rules('side'))->validate();
        $authority = function () use ($actor, $id, $data) {
            [, $part, $side] = $this->mine($actor, $id, true);
            abort_unless($part->state === SharedParticipant::ACCEPTED && $side !== null, 409, 'Accept the fixture first.');
            if (isset($data['activity_group_id']) && (int) $data['activity_group_id'] !== $side->activity_group_id) {
                abort_unless($this->access->manages($actor), 403, 'Only an activity manager changes the group.');
            }
        };

        return $this->shared->once($actor, 'fixture-side:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->lockForUpdate()->firstOrFail();
            abort_unless($side->revision === (int) $data['revision'], 409, 'This team sheet changed. Refresh before saving.');
            $date = $fixture->on_date->toDateString();
            $group = $this->group((int) ($data['activity_group_id'] ?? $side->activity_group_id), $date);
            $members = ActivityMembership::query()->where('activity_group_id', $group->id)->where('active', true)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
                ->pluck('learner_id')->map(fn ($v) => (int) $v)->all();
            $rows = [];
            foreach ($data['selections'] as $entry) {
                $learnerId = (int) $entry['learner_id'];
                abort_unless(in_array($learnerId, $members, true), 422, 'Choose learners who are in the group on the fixture day.');
                $label = trim((string) ($entry['sheet_label'] ?? ''));
                $rows[] = ['learner_id' => $learnerId, 'sheet_label' => $label === '' ? $this->defaultLabel($learnerId) : $label, 'position' => $this->blank($entry['position'] ?? null)];
            }
            $before = ['team_label' => $side->team_label, 'sheet_shared' => $side->sheet_shared, 'activity_group_id' => $side->activity_group_id, 'selections' => FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->count(), 'revision' => $side->revision];
            $side->forceFill(['activity_group_id' => $group->id, 'team_label' => trim($data['team_label']), 'sheet_shared' => (bool) $data['sheet_shared'], 'notes' => $this->blank($data['notes'] ?? null), 'revision' => $side->revision + 1])->save();
            FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->delete();
            foreach ($rows as $row) {
                FixtureSelection::query()->create($row + ['shared_fixture_side_id' => $side->id]);
            }
            // Learner ids and notes stay out of the audit log's after-image.
            Audit::record('fixture.side_saved', $side, $before, ['team_label' => $side->team_label, 'sheet_shared' => $side->sheet_shared, 'activity_group_id' => $side->activity_group_id, 'selections' => count($rows), 'revision' => $side->revision]);

            return $this->view($actor, $id);
        });
    }

    /**
     * This school's report of the score. Two NavigateEd schools: the result is
     * confirmed when both reports agree, and disputed while they differ. An
     * opponent outside NavigateEd: the host's report confirms it. Reporting the
     * same score again changes nothing.
     */
    public function report(User $actor, int $id, array $input): array
    {
        $this->access->staff($actor, true);
        $data = Validator::make($input, self::rules('result'))->validate();
        $authority = function () use ($actor, $id) {
            [, $part, $side] = $this->mine($actor, $id, true);
            abort_unless($part->state === SharedParticipant::ACCEPTED && $side !== null, 409, 'Accept the fixture first.');
        };

        return $this->shared->once($actor, 'fixture-result:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            // An academic competition's result is its adjudicators' (AcademicContests::result).
            abort_unless($fixture->kind === 'sport', 409, 'Record the adjudicators\' result for this competition.');
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            abort_if($fixture->on_date->toDateString() > $this->access->today(), 422, 'Scores can be reported from the day of the fixture.');
            $everyone = $this->shared->participants(self::TYPE, $id);
            abort_unless($everyone->every(fn ($row) => $row['state'] === SharedParticipant::ACCEPTED), 409, 'Waiting for the other school to accept the fixture.');
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->lockForUpdate()->firstOrFail();
            $host = (int) $data['host_score'];
            $guest = (int) $data['guest_score'];
            $before = $side->only(['reported_host_score', 'reported_guest_score']);
            if ($side->reported_host_score !== $host || $side->reported_guest_score !== $guest) {
                $side->forceFill(['reported_host_score' => $host, 'reported_guest_score' => $guest, 'reported_by' => $actor->id, 'reported_at' => now(), 'revision' => $side->revision + 1])->save();
                Audit::record('fixture.score_reported', $side, $before, $side->only(['reported_host_score', 'reported_guest_score']));
            }
            $reports = [[$host, $guest]];
            $other = $everyone->first(fn ($row) => $row['school_id'] !== app(CurrentSchool::class)->id());
            if ($other !== null) {
                // A locking read after the fixture lock: a report the other school committed
                // while this write waited for the lock is seen, so agreeing scores confirm.
                $theirs = $this->shared->published(FixtureSide::class, $other['school_id'], ['reported_host_score', 'reported_guest_score'], fn ($q) => $q->where('shared_fixture_id', $id), true)->first();
                $reports[] = $theirs?->reported_host_score === null ? null : [(int) $theirs->reported_host_score, (int) $theirs->reported_guest_score];
            }
            $this->settle($fixture, $reports, $everyone->pluck('school_id')->all());

            return $this->view($actor, $id);
        });
    }

    /**
     * Agreeing reports confirm the result; any report missing leaves it
     * awaiting, and differing reports leave it disputed. An academic
     * competition's report also names the winning side.
     *
     * @param  list<array{0: int, 1: int, 2?: string|null}|null>  $reports
     */
    public function settle(SharedFixture $fixture, array $reports, array $schools): void
    {
        $missing = in_array(null, $reports, true);
        $agreed = ! $missing && count(array_unique(array_map('json_encode', $reports))) === 1;
        if ($agreed) {
            [$host, $guest] = $reports[0];
            $winner = $reports[0][2] ?? null;
            if ($fixture->result_state === 'confirmed' && $fixture->host_score === $host && $fixture->guest_score === $guest && $fixture->winner === $winner) {
                return;
            }
            $fixture->forceFill(['result_state' => 'confirmed', 'host_score' => $host, 'guest_score' => $guest, 'winner' => $winner, 'result_confirmed_at' => now(), 'result_revision' => $fixture->result_revision + 1])->save();
            foreach ($schools as $school) {
                $this->shared->inSchool((int) $school, fn () => app(FixtureNotices::class)->confirmed($fixture));
            }

            return;
        }
        $fixture->forceFill(['result_state' => $missing ? 'awaiting' : 'disputed', 'host_score' => null, 'guest_score' => null, 'winner' => null, 'result_confirmed_at' => null])->save();
    }

    /** @param  list<string>|null  $kinds  only these kinds (academic competitions list their own) */
    public function listing(User $actor, int $page = 1, ?array $kinds = null, ?callable $visible = null): array
    {
        $this->shared->ownStaff();
        $this->access->staff($actor);
        $ids = $this->shared->idsHere(self::TYPE);
        if (! $this->access->manages($actor)) {
            $ids = array_values(array_filter($ids, $visible ?? fn (int $id) => $this->visibleToStaff($actor, $id)));
        }
        $fixtures = SharedFixture::query()->whereIn('id', $ids)->when($kinds !== null, fn ($q) => $q->whereIn('kind', $kinds))->orderByDesc('on_date')->orderByDesc('id')->get();
        $items = $fixtures->slice(($page - 1) * 30, 30)->map(fn (SharedFixture $f) => array_diff_key($this->summary($f), ['participants' => true]))->values()->all();

        return ['items' => $items, 'page' => $page, 'last_page' => max(1, (int) ceil($fixtures->count() / 30)), 'total' => $fixtures->count()];
    }

    public function visibleToStaff(User $actor, int $id): bool
    {
        $side = FixtureSide::query()->where('shared_fixture_id', $id)->first();

        return $side !== null && $this->access->canStaff($actor, (int) $side->activity_group_id);
    }

    /** What this school sees of one fixture. */
    public function view(User $actor, int $id): array
    {
        [$fixture, $part, $side] = $this->mine($actor, $id);

        return $this->render($actor, $fixture, $part, $side);
    }

    /**
     * This school's view of a fixture whose access the caller has already
     * checked (here, or for an academic competition's adjudicators).
     */
    public function render(User $actor, SharedFixture $fixture, SharedParticipant $part, ?FixtureSide $side): array
    {
        $id = (int) $fixture->id;
        $summary = $this->summary($fixture);
        $open = $summary['open'];
        $me = app(CurrentSchool::class)->id();
        $other = collect($summary['participants'])->first(fn ($row) => $row['school_id'] !== $me);
        $theirs = $open && $other !== null
            ? $this->shared->published(FixtureSide::class, $other['school_id'], ['id', 'team_label', 'sheet_shared', 'reported_host_score', 'reported_guest_score'], fn ($q) => $q->where('shared_fixture_id', $id))->first()
            : null;
        $mySide = $side === null ? null : $this->presentSide($side);
        $myRole = $part->role;
        $labels = ['host' => $myRole === 'host' ? $side?->team_label : $theirs?->team_label, 'guest' => $myRole === 'guest' ? $side?->team_label : $theirs?->team_label];
        $view = $summary;
        unset($view['participants']);
        $view['host']['team_label'] = $labels['host'];
        $view['guest']['team_label'] = $view['guest']['external'] ? null : $labels['guest'];
        $mine = $side?->reported_host_score === null ? null : ['host_score' => $side->reported_host_score, 'guest_score' => $side->reported_guest_score];
        $their = $theirs?->reported_host_score === null ? null : ['host_score' => (int) $theirs->reported_host_score, 'guest_score' => (int) $theirs->reported_guest_score];
        $view['result'] += [
            'my_report' => $mine,
            // The other school's report is shown only while the two differ, so each can check theirs.
            'their_report' => $fixture->result_state === 'disputed' ? $their : null,
            'waiting_for' => $fixture->result_state !== 'awaiting' ? null : ($mine === null ? 'us' : 'them'),
        ];
        $sheet = $theirs !== null && $theirs->sheet_shared
            ? $this->shared->published(FixtureSelection::class, $other['school_id'], ['sheet_label', 'position'], fn ($q) => $q->where('shared_fixture_side_id', $theirs->id)->orderBy('sheet_label'))
                ->map(fn ($row) => ['sheet_label' => (string) $row->sheet_label, 'position' => $row->position])->values()->all()
            : null;
        $manages = $this->access->manages($actor);

        return $view + [
            'side' => $mySide,
            'their_sheet' => $sheet,
            'can_decide' => $manages && $part->state === SharedParticipant::INVITED && $fixture->status === 'scheduled',
            'can_schedule' => $manages && $myRole === 'host' && $fixture->status === 'scheduled',
            'can_report' => $fixture->kind === 'sport' && $open && $side !== null && $fixture->status === 'scheduled' && $fixture->on_date->toDateString() <= $this->access->today(),
        ];
    }

    /** The shared facts and the names and states of the schools: what any school taking part may see. */
    public function summary(SharedFixture $fixture): array
    {
        $me = app(CurrentSchool::class)->id();
        $everyone = $this->shared->participants(self::TYPE, (int) $fixture->id)->all();
        $mine = collect($everyone)->firstWhere('school_id', $me);
        $host = collect($everyone)->firstWhere('role', 'host');
        $guest = collect($everyone)->firstWhere('role', 'guest');
        $open = collect($everyone)->every(fn ($row) => $row['state'] === SharedParticipant::ACCEPTED);
        $external = $fixture->external_opponent !== null;
        $confirmed = $open && $fixture->result_state === 'confirmed';

        return [
            'id' => (int) $fixture->id,
            'kind' => (string) $fixture->kind,
            'competition' => $fixture->shared_competition_id === null ? null : ['id' => (int) $fixture->shared_competition_id, 'name' => (string) SharedCompetition::query()->whereKey($fixture->shared_competition_id)->value('name')],
            'discipline' => (string) $fixture->discipline,
            'on_date' => $fixture->on_date->toDateString(),
            'starts_at' => $fixture->starts_at === null ? null : substr((string) $fixture->starts_at, 0, 5),
            'venue' => (string) $fixture->venue,
            'field' => $fixture->field,
            'host_is_home' => (bool) $fixture->host_is_home,
            'status' => (string) $fixture->status,
            'revision' => (int) $fixture->revision,
            'my_role' => (string) ($mine['role'] ?? ''),
            'my_state' => (string) ($mine['state'] ?? ''),
            'open' => $open,
            'host' => ['name' => (string) ($host['name'] ?? ''), 'external' => false, 'state' => (string) ($host['state'] ?? '')],
            'guest' => $external ? ['name' => (string) $fixture->external_opponent, 'external' => true, 'state' => 'external']
                : ['name' => (string) ($guest['name'] ?? ''), 'external' => false, 'state' => (string) ($guest['state'] ?? '')],
            'result' => ['state' => $open ? (string) $fixture->result_state : 'none', 'host_score' => $confirmed ? $fixture->host_score : null, 'guest_score' => $confirmed ? $fixture->guest_score : null,
                'winner' => $confirmed ? $fixture->winner : null],
            'participants' => $everyone,
        ];
    }

    private function presentSide(FixtureSide $side): array
    {
        $selections = FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->orderBy('id')->get();
        $names = Students::withTrashed()->with('user')->whereIn('id', $selections->pluck('learner_id'))->get()
            ->mapWithKeys(fn (Students $s) => [(int) $s->id => trim(($s->user?->first_name ?? '').' '.($s->user?->last_name ?? ''))]);

        return ['activity_group_id' => (int) $side->activity_group_id, 'group_name' => (string) ActivityGroup::query()->whereKey($side->activity_group_id)->value('name'),
            'team_label' => (string) $side->team_label, 'sheet_shared' => (bool) $side->sheet_shared, 'notes' => $side->notes, 'revision' => (int) $side->revision,
            'selections' => $selections->map(fn (FixtureSelection $row) => ['learner_id' => (int) $row->learner_id, 'learner_name' => (string) ($names[$row->learner_id] ?? ''),
                'sheet_label' => (string) $row->sheet_label, 'position' => $row->position])->all()];
    }

    /** "Asha D.": a first name and an initial, which the school can change before sharing. */
    private function defaultLabel(int $learnerId): string
    {
        $user = Students::query()->with('user')->findOrFail($learnerId)->user;
        $last = trim((string) ($user?->last_name ?? ''));

        return mb_substr(trim(((string) ($user?->first_name ?? '')).($last === '' ? '' : ' '.mb_substr($last, 0, 1).'.')), 0, 60);
    }

    /**
     * Fixtures a learner is on the team sheet for, as their family sees them:
     * once both schools accepted, with the confirmed score. No other learner.
     */
    public function forLearner(User $actor, int $learnerId): array
    {
        $this->shared->ownStaff();
        abort_unless(in_array($learnerId, $this->access->learnerIds($actor), true), 404);
        $sides = FixtureSide::query()->whereIn('id', FixtureSelection::query()->where('learner_id', $learnerId)->select('shared_fixture_side_id'))->get()->keyBy('shared_fixture_id');
        $items = [];
        foreach (SharedFixture::query()->whereIn('id', $sides->keys())->orderByDesc('on_date')->limit(100)->get() as $fixture) {
            $summary = $this->summary($fixture);
            if (! $summary['open']) {
                continue;
            }
            $ours = $summary['my_role'];
            $theirs = $ours === 'host' ? 'guest' : 'host';
            $confirmed = $summary['result']['state'] === 'confirmed';
            $items[] = ['id' => $summary['id'], 'discipline' => $summary['discipline'], 'on_date' => $summary['on_date'], 'starts_at' => $summary['starts_at'],
                'venue' => $summary['venue'], 'field' => $summary['field'], 'status' => $summary['status'], 'competition' => $summary['competition']['name'] ?? null,
                'team_label' => (string) $sides[$fixture->id]->team_label, 'opponent' => $summary[$theirs]['name'], 'home' => ($ours === 'host') === $summary['host_is_home'],
                'result' => $confirmed ? ['state' => 'confirmed', 'our_score' => $summary['result'][$ours.'_score'], 'their_score' => $summary['result'][$theirs.'_score']] : ['state' => 'pending', 'our_score' => null, 'their_score' => null]];
        }

        return ['items' => $items];
    }

    public function schools(User $actor, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('schools'))->validate();

        return ['items' => $this->shared->directory($data['search'])];
    }

    /** Whether the actor may open this fixture (for the panel), without raising. */
    public function canView(User $actor, int $id): bool
    {
        try {
            $this->mine($actor, $id);

            return true;
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    public function time(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value.':00';
    }

    public function blank(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : $value;
    }
}
