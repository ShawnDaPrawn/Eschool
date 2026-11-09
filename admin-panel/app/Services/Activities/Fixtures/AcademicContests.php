<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\AcademicAdjudicator;
use App\Models\Activities\AcademicAwardLink;
use App\Models\Activities\AcademicReasoningReview;
use App\Models\Activities\AcademicResult;
use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Recognition\AwardPresentation;
use App\Models\Shared\SharedFixture;
use App\Models\Shared\SharedParticipant;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Shared\SharedRecords;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Academic competitions and debating between schools (Action Plan 5.3,
 * engineering/specs/activities/academic-competitions.md). An academic
 * competition is a shared fixture of an academic kind (config/shared_records.php
 * 'academic_kinds'): the invitation, each school's entrants (its team sheet)
 * and the rescheduling rules are the fixture's own. This adds the motion or
 * topic, the format and rounds, each school's own adjudicators, and the
 * adjudicators' result: a draft until it is submitted, then the school's
 * report, which counts once both schools submit the same result (or the host
 * does, against an opponent outside NavigateEd), as fixture scores do.
 *
 * Entrants' own scores and places, the written reasons, the adjudicators and
 * any AI feedback never leave the school. Receipts hold ids only.
 */
class AcademicContests
{
    public const MODULE = 'reasoning_feedback';

    public function __construct(private SharedRecords $shared, private CommunityAuthority $access, private Fixtures $fixtures) {}

    /** @return list<string> */
    public static function kinds(): array
    {
        return array_keys((array) config('shared_records.academic_kinds', []));
    }

    /** @return array{entry: string, draw: bool, reasoning_feedback: bool} */
    public static function kind(string $kind): array
    {
        $settings = config('shared_records.academic_kinds.'.$kind);
        abort_unless(is_array($settings), 404);

        return ['entry' => (string) ($settings['entry'] ?? 'team'), 'draw' => (bool) ($settings['draw'] ?? false), 'reasoning_feedback' => (bool) ($settings['reasoning_feedback'] ?? false)];
    }

    public static function rules(string $action): array
    {
        $op = ['operation_id' => ['required', 'uuid']];
        $facts = ['motion' => ['required', 'string', 'min:3', 'max:500'], 'format' => ['nullable', 'string', 'max:60'], 'rounds' => ['nullable', 'integer', 'between:1,20'],
            'host_stance' => ['nullable', Rule::in(['proposition', 'opposition'])]];

        return match ($action) {
            'index' => ['page' => ['sometimes', 'integer', 'between:1,10000']],
            'create' => $op + $facts + Fixtures::rules('create') + ['kind' => ['required', Rule::in(self::kinds())]],
            'details' => $op + $facts + ['revision' => ['required', 'integer', 'min:0']],
            'adjudicators' => $op + ['adjudicators' => ['present', 'array', 'max:10'], 'adjudicators.*' => ['array:user_id,name'],
                'adjudicators.*.user_id' => ['nullable', 'integer', 'min:1', 'distinct', 'required_without:adjudicators.*.name'],
                'adjudicators.*.name' => ['nullable', 'string', 'min:2', 'max:120', 'prohibits:adjudicators.*.user_id']],
            'result' => $op + ['state' => ['required', Rule::in([AcademicResult::DRAFT, 'final'])], 'revision' => ['required', 'integer', 'min:0'],
                'host_score' => ['required', 'integer', 'between:0,9999'], 'guest_score' => ['required', 'integer', 'between:0,9999'], 'winner' => ['required', Rule::in(['host', 'guest', 'draw'])],
                'entrants' => ['present', 'array', 'max:60'], 'entrants.*' => ['array:learner_id,score,place'], 'entrants.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'],
                'entrants.*.score' => ['nullable', 'integer', 'between:0,9999'], 'entrants.*.place' => ['nullable', 'integer', 'between:1,999'],
                'reasons' => ['nullable', 'string', 'max:4000']],
            'awards' => $op + ['award_definition_id' => ['required', 'integer', 'min:1'], 'basis' => ['required', Rule::in(['participation', 'placing'])],
                'learner_ids' => ['required', 'array', 'min:1', 'max:60'], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'presented_on' => ['required', 'date_format:Y-m-d']],
            default => [],
        };
    }

    /**
     * This school's part in an academic competition, or "not found" for a
     * school taking no part, a sports fixture, or a person with no part in it.
     *
     * @return array{0: SharedFixture, 1: SharedParticipant, 2: FixtureSide|null, 3: array{manages: bool, adjudicator: bool, staff: bool}}
     */
    public function access(User $actor, int $id, bool $write = false): array
    {
        $this->shared->ownStaff();
        $this->access->staff($actor, $write);
        $part = $this->shared->requireHere(Fixtures::TYPE, $id);
        $fixture = SharedFixture::query()->findOrFail($id);
        abort_unless(in_array($fixture->kind, self::kinds(), true), 404);
        $side = FixtureSide::query()->where('shared_fixture_id', $id)->first();
        $manages = $this->access->manages($actor);
        $adjudicator = AcademicAdjudicator::query()->where('shared_fixture_id', $id)->where('user_id', $actor->id)->exists();
        $staff = $side !== null && $this->access->canStaff($actor, (int) $side->activity_group_id);
        abort_unless($manages || $adjudicator || $staff, 404);

        return [$fixture, $part, $side, ['manages' => $manages, 'adjudicator' => $adjudicator, 'staff' => $staff]];
    }

    /** Managers and this school's named adjudicators judge; a team's coach does not. */
    public function judge(User $actor, int $id, bool $write = true): array
    {
        $found = $this->access($actor, $id, $write);
        abort_unless($found[3]['manages'] || $found[3]['adjudicator'], 403, 'Only an adjudicator or an activity manager records the result.');

        return $found;
    }

    public function create(User $actor, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('create'))->validate();
        $receipt = $this->shared->once($actor, 'academic:new', $data, fn () => $this->access->manage($actor), function () use ($actor, $data) {
            $kind = (string) $data['kind'];
            abort_if($kind !== 'debate' && ($data['host_stance'] ?? null) !== null, 422, 'Only a debate has a proposition and an opposition.');
            $id = $this->fixtures->arrange($actor, $data, $kind, $this->facts($data));

            return ['id' => $id];
        });

        return $this->view($actor, (int) $receipt['id']);
    }

    private function facts(array $data): array
    {
        return ['motion' => trim($data['motion']), 'format' => $this->fixtures->blank($data['format'] ?? null), 'rounds' => isset($data['rounds']) ? (int) $data['rounds'] : null,
            'host_stance' => $data['host_stance'] ?? null];
    }

    /** The host changes the motion or topic, format, rounds and stance, until a result is reported. */
    public function details(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('details'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->manage($actor);
            [, $part] = $this->access($actor, $id, true);
            abort_unless($part->role === 'host', 403, 'Only the host school changes the competition details.');
        };
        $this->shared->once($actor, 'academic-details:'.$id, $data, $authority, function () use ($id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            abort_unless($fixture->revision === (int) $data['revision'], 409, 'This competition changed. Refresh before saving.');
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            abort_unless($fixture->result_state === 'none', 409, 'A competition with a reported result keeps its motion.');
            abort_if($fixture->kind !== 'debate' && ($data['host_stance'] ?? null) !== null, 422, 'Only a debate has a proposition and an opposition.');
            $before = $fixture->only(['motion', 'format', 'rounds', 'host_stance', 'revision']);
            $fixture->forceFill($this->facts($data) + ['revision' => $fixture->revision + 1])->save();
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->firstOrFail();
            Audit::record('academic.details_changed', $side, $before, $fixture->only(['motion', 'format', 'rounds', 'host_stance', 'revision']));

            return ['id' => $id];
        });

        return $this->view($actor, $id);
    }

    /**
     * This school's own adjudicators: its staff (who may then record the
     * result and ask for AI feedback) or people from outside, by name.
     * Saving replaces the list. The other school never sees it.
     */
    public function adjudicators(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('adjudicators'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->manage($actor);
            [, $part] = $this->access($actor, $id, true);
            abort_unless($part->state === SharedParticipant::ACCEPTED, 409, 'Accept the competition first.');
        };
        $this->shared->once($actor, 'academic-adjudicators:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            SharedFixture::query()->lockForUpdate()->findOrFail($id);
            $rows = [];
            foreach ($data['adjudicators'] as $entry) {
                $userId = isset($entry['user_id']) ? (int) $entry['user_id'] : null;
                if ($userId !== null) {
                    abort_unless($this->isStaff($userId), 422, 'Choose adjudicators from your school\'s staff, or type the name of someone from outside.');
                }
                $rows[] = ['user_id' => $userId, 'name' => $userId === null ? trim((string) $entry['name']) : null];
            }
            $before = AcademicAdjudicator::query()->where('shared_fixture_id', $id)->count();
            AcademicAdjudicator::query()->where('shared_fixture_id', $id)->delete();
            foreach ($rows as $row) {
                AcademicAdjudicator::query()->create($row + ['shared_fixture_id' => $id, 'added_by' => $actor->id]);
            }
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->firstOrFail();
            Audit::record('academic.adjudicators_saved', $side, ['adjudicators' => $before], ['adjudicators' => count($rows), 'staff' => count(array_filter(array_column($rows, 'user_id')))]);

            return ['id' => $id];
        });

        return $this->view($actor, $id);
    }

    /** A current member of staff in this school (not a parent or learner). */
    private function isStaff(int $userId): bool
    {
        try {
            $user = User::query()->findOrFail($userId);
            $this->access->staff($user);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /**
     * The adjudicators' result, kept as a draft or submitted as this school's
     * report. Only a human adjudicator (or a manager) records it; AI feedback
     * is never a result. Submitted reports from both schools that agree on
     * the scores and the winning side confirm the result.
     */
    public function result(User $actor, int $id, array $input): array
    {
        $this->access->staff($actor, true);
        $data = Validator::make($input, self::rules('result'))->validate();
        $authority = function () use ($actor, $id) {
            [, $part, $side] = $this->judge($actor, $id);
            abort_unless($part->state === SharedParticipant::ACCEPTED && $side !== null, 409, 'Accept the competition first.');
        };
        $this->shared->once($actor, 'academic-result:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            $fixture = SharedFixture::query()->lockForUpdate()->findOrFail($id);
            abort_if($fixture->status === 'cancelled', 409, 'This fixture was cancelled.');
            $kind = self::kind((string) $fixture->kind);
            $host = (int) $data['host_score'];
            $guest = (int) $data['guest_score'];
            $winner = (string) $data['winner'];
            abort_if($winner === 'draw' && ! $kind['draw'], 422, 'This kind of competition has a winning side.');
            if ($fixture->kind !== 'debate') {
                // Points decide a quiz or a speech contest; a debate's adjudicators decide its winner.
                abort_unless($winner === match ($host <=> $guest) {
                    1 => 'host', -1 => 'guest', default => 'draw'
                }, 422, 'The winner must have the higher score.');
            }
            $side = FixtureSide::query()->where('shared_fixture_id', $id)->lockForUpdate()->firstOrFail();
            $sheet = FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->pluck('learner_id')->map(fn ($v) => (int) $v)->all();
            $entrants = [];
            foreach ($data['entrants'] as $entry) {
                abort_unless(in_array((int) $entry['learner_id'], $sheet, true), 422, 'Record results only for your own entrants.');
                $entrants[] = ['learner_id' => (int) $entry['learner_id'], 'score' => isset($entry['score']) ? (int) $entry['score'] : null, 'place' => isset($entry['place']) ? (int) $entry['place'] : null];
            }
            $row = AcademicResult::query()->where('shared_fixture_id', $id)->lockForUpdate()->first();
            abort_unless(($row?->revision ?? 0) === (int) $data['revision'], 409, 'This result changed. Refresh before saving.');
            $final = $data['state'] === 'final';
            if ($final) {
                abort_if($fixture->on_date->toDateString() > $this->access->today(), 422, 'Results can be submitted from the day of the competition.');
                abort_unless($this->shared->open(Fixtures::TYPE, $id), 409, 'Waiting for the other school to accept the competition.');
            }
            $row ??= new AcademicResult(['shared_fixture_id' => $id, 'revision' => 0]);
            $before = ['state' => $row->state, 'revision' => (int) $row->revision];
            $row->forceFill(['state' => $final ? AcademicResult::SUBMITTED : AcademicResult::DRAFT, 'host_score' => $host, 'guest_score' => $guest, 'winner' => $winner,
                'entrants' => $entrants, 'reasons' => $this->fixtures->blank($data['reasons'] ?? null), 'recorded_by' => $actor->id,
                'submitted_at' => $final ? now() : $row->submitted_at, 'revision' => (int) $row->revision + 1])->save();
            // Entrants' scores, places and the reasons stay out of the audit log.
            Audit::record('academic.result_saved', $row, $before, ['state' => $row->state, 'revision' => $row->revision, 'entrants' => count($entrants)]);
            if ($final) {
                $this->report($fixture, $side, $actor, [$host, $guest, $winner]);
            }

            return ['id' => $id];
        });

        return $this->view($actor, $id);
    }

    /** The submitted result becomes this school's report, settled against the other school's as fixture scores are. */
    private function report(SharedFixture $fixture, FixtureSide $side, User $actor, array $mine): void
    {
        $id = (int) $fixture->id;
        if ([$side->reported_host_score, $side->reported_guest_score, $side->reported_winner] !== $mine) {
            $before = $side->only(['reported_host_score', 'reported_guest_score', 'reported_winner']);
            $side->forceFill(['reported_host_score' => $mine[0], 'reported_guest_score' => $mine[1], 'reported_winner' => $mine[2], 'reported_by' => $actor->id, 'reported_at' => now(), 'revision' => $side->revision + 1])->save();
            Audit::record('academic.result_reported', $side, $before, $side->only(['reported_host_score', 'reported_guest_score', 'reported_winner']));
        }
        $everyone = $this->shared->participants(Fixtures::TYPE, $id);
        $reports = [$mine];
        $other = $everyone->first(fn ($row) => $row['school_id'] !== app(CurrentSchool::class)->id());
        if ($other !== null) {
            // A locking read after the fixture lock sees a report the other school committed meanwhile.
            $theirs = $this->shared->published(FixtureSide::class, $other['school_id'], ['reported_host_score', 'reported_guest_score', 'reported_winner'], fn ($q) => $q->where('shared_fixture_id', $id), true)->first();
            $reports[] = $theirs?->reported_host_score === null || $theirs->reported_winner === null ? null
                : [(int) $theirs->reported_host_score, (int) $theirs->reported_guest_score, (string) $theirs->reported_winner];
        }
        $this->fixtures->settle($fixture, $reports, $everyone->pluck('school_id')->all());
    }

    public function listing(User $actor, int $page = 1): array
    {
        $adjudicating = AcademicAdjudicator::query()->where('user_id', $actor->id)->pluck('shared_fixture_id')->map(fn ($v) => (int) $v)->all();

        return $this->fixtures->listing($actor, $page, self::kinds(), fn (int $id) => in_array($id, $adjudicating, true) || $this->fixtures->visibleToStaff($actor, $id));
    }

    /** What this school sees: the fixture's view, the shared competition facts and, for its judges, its own adjudication. */
    public function view(User $actor, int $id): array
    {
        [$fixture, $part, $side, $roles] = $this->access($actor, $id);
        $view = $this->fixtures->render($actor, $fixture, $part, $side);
        $judges = $roles['manages'] || $roles['adjudicator'];
        $kind = self::kind((string) $fixture->kind);
        $theirWinner = null;
        if ($view['result']['state'] === 'disputed') {
            $other = collect($this->shared->participants(Fixtures::TYPE, $id))->first(fn ($row) => $row['school_id'] !== app(CurrentSchool::class)->id());
            $theirWinner = $other === null ? null : $this->shared->published(FixtureSide::class, $other['school_id'], ['reported_winner'], fn ($q) => $q->where('shared_fixture_id', $id))->value('reported_winner');
        }
        $result = $judges ? AcademicResult::query()->where('shared_fixture_id', $id)->first() : null;
        $reviews = ! $judges ? collect() : AcademicReasoningReview::query()->where('shared_fixture_id', $id)
            ->when(! $roles['manages'], fn ($q) => $q->where('requested_by', $actor->id))->orderByDesc('id')->limit(100)->get();
        $open = $view['open'];
        $scheduled = $fixture->status === 'scheduled';

        return $view + ['academic' => [
            'kind' => (string) $fixture->kind,
            'entry' => $kind['entry'],
            'motion' => (string) $fixture->motion,
            'format' => $fixture->format,
            'rounds' => $fixture->rounds === null ? null : (int) $fixture->rounds,
            'host_stance' => $fixture->host_stance,
            'my_stance' => $this->stance($fixture, $part->role),
            'their_winner' => $theirWinner,
            'adjudicators' => $roles['manages'] || $roles['adjudicator'] ? $this->adjudicatorList($id) : [],
            'result' => $result === null ? null : ['state' => (string) $result->state, 'host_score' => $result->host_score, 'guest_score' => $result->guest_score, 'winner' => $result->winner,
                'entrants' => array_values((array) $result->entrants), 'reasons' => $result->reasons, 'revision' => (int) $result->revision,
                'submitted_at' => $result->submitted_at?->toIso8601String()],
            'reviews' => $reviews->map(fn (AcademicReasoningReview $r) => app(ReasoningReviews::class)->present($r))->values()->all(),
            'awards' => $roles['manages'] ? $this->awardList($id) : [],
            'reasoning_feedback' => $judges && $kind['reasoning_feedback'] && $this->feedbackSwitchedOn(),
            'can_edit_details' => $roles['manages'] && $part->role === 'host' && $scheduled && $fixture->result_state === 'none',
            'can_adjudicate' => $judges && $part->state === SharedParticipant::ACCEPTED && $side !== null && $scheduled,
            'can_submit' => $judges && $open && $side !== null && $scheduled && $fixture->on_date->toDateString() <= $this->access->today(),
            'can_award' => $roles['manages'] && $open && $side !== null,
        ]];
    }

    /** Proposition or opposition for a school's side of a debate (null for other kinds or an unset stance). */
    public function stance(SharedFixture $fixture, string $role): ?string
    {
        if ($fixture->kind !== 'debate' || $fixture->host_stance === null) {
            return null;
        }

        return $role === 'host' ? $fixture->host_stance : ($fixture->host_stance === 'proposition' ? 'opposition' : 'proposition');
    }

    /** Whether this school and the platform have AI feedback on reasoning switched on. */
    public function feedbackSwitchedOn(): bool
    {
        return config('ai.inference.enabled') === true && config('ai.inference.features.'.ReasoningReviews::FEATURE.'.enabled') === true && app(ModuleGate::class)->allows(self::MODULE);
    }

    private function adjudicatorList(int $id): array
    {
        $rows = AcademicAdjudicator::query()->where('shared_fixture_id', $id)->orderBy('id')->get();
        $names = User::query()->whereIn('id', $rows->pluck('user_id')->filter())->get()->mapWithKeys(fn (User $u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)]);

        return $rows->map(fn (AcademicAdjudicator $row) => ['user_id' => $row->user_id, 'name' => $row->user_id === null ? (string) $row->name : (string) ($names[$row->user_id] ?? '')])->all();
    }

    private function awardList(int $id): array
    {
        $links = AcademicAwardLink::query()->where('shared_fixture_id', $id)->orderBy('id')->get();
        $titles = AwardPresentation::query()->whereIn('id', $links->pluck('award_presentation_id'))->pluck('title', 'id');

        return $links->map(fn (AcademicAwardLink $l) => ['learner_id' => $l->learner_id, 'basis' => (string) $l->basis, 'award_presentation_id' => $l->award_presentation_id,
            'title' => (string) ($titles[$l->award_presentation_id] ?? '')])->all();
    }

    /**
     * The academic competitions a learner is an entrant in, as their family
     * sees them: once both schools accepted, with the team's confirmed result,
     * feedback an adjudicator chose to share with this learner and the awards
     * linked for them. No other learner, no place, score or ranking of a child.
     */
    public function forLearner(User $actor, int $learnerId): array
    {
        $this->shared->ownStaff();
        abort_unless(in_array($learnerId, $this->access->learnerIds($actor), true), 404);
        $sides = FixtureSide::query()->whereIn('id', FixtureSelection::query()->where('learner_id', $learnerId)->select('shared_fixture_side_id'))->get()->keyBy('shared_fixture_id');
        $items = [];
        foreach (SharedFixture::query()->whereIn('id', $sides->keys())->whereIn('kind', self::kinds())->orderByDesc('on_date')->limit(100)->get() as $fixture) {
            $summary = $this->fixtures->summary($fixture);
            if (! $summary['open']) {
                continue;
            }
            $ours = $summary['my_role'];
            $theirs = $ours === 'host' ? 'guest' : 'host';
            $confirmed = $summary['result']['state'] === 'confirmed';
            $winner = $summary['result']['winner'];
            $feedback = AcademicReasoningReview::query()->where('shared_fixture_id', $fixture->id)->where('shared_learner_id', $learnerId)->whereNotNull('shared_at')->orderBy('id')->get()
                ->map(fn (AcademicReasoningReview $r) => ['text' => (string) $r->shared_feedback, 'shared_on' => $r->shared_at->toDateString()])->values()->all();
            $awards = AwardPresentation::query()->where('state', 'active')->whereIn('id', AcademicAwardLink::query()->where('shared_fixture_id', $fixture->id)->where('learner_id', $learnerId)->select('award_presentation_id'))
                ->orderBy('id')->pluck('title')->map(fn ($t) => (string) $t)->values()->all();
            $items[] = ['id' => $summary['id'], 'kind' => (string) $fixture->kind, 'discipline' => $summary['discipline'], 'motion' => (string) $fixture->motion, 'on_date' => $summary['on_date'],
                'starts_at' => $summary['starts_at'], 'venue' => $summary['venue'], 'status' => $summary['status'], 'competition' => $summary['competition']['name'] ?? null,
                'team_label' => (string) $sides[$fixture->id]->team_label, 'opponent' => $summary[$theirs]['name'], 'stance' => $this->stance($fixture, $ours),
                'result' => $confirmed ? ['state' => 'confirmed', 'our_score' => $summary['result'][$ours.'_score'], 'their_score' => $summary['result'][$theirs.'_score'],
                    'outcome' => $winner === 'draw' ? 'draw' : ($winner === $ours ? 'won' : 'lost')] : ['state' => 'pending', 'our_score' => null, 'their_score' => null, 'outcome' => null],
                'feedback' => $feedback, 'awards' => $awards];
        }

        return ['items' => $items];
    }

    /** Whether the actor may open this competition (for the panel), without raising. */
    public function canView(User $actor, int $id): bool
    {
        try {
            $this->access($actor, $id);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
