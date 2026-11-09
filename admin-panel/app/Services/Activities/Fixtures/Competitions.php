<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\FixtureSide;
use App\Models\Shared\SharedCompetition;
use App\Models\Shared\SharedFixture;
use App\Models\Shared\SharedParticipant;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Shared\SharedRecords;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Competitions and leagues between schools, with running standings from the
 * confirmed results of their fixtures. A competition is a shared record:
 * the organising school invites members, and each member must accept before
 * the others see anything about it beyond its name.
 */
class Competitions
{
    public const TYPE = SharedCompetition::RECORD;

    /** Standings are worked out from at most this many confirmed fixtures. */
    private const FIXTURE_LIMIT = 500;

    public function __construct(private SharedRecords $shared, private CommunityAuthority $access) {}

    public static function rules(string $action): array
    {
        $op = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'competition-create' => $op + ['name' => ['required', 'string', 'min:2', 'max:191'], 'kind' => ['required', Rule::in((array) config('shared_records.competition_kinds', ['sport']))],
                'discipline' => ['required', 'string', 'min:2', 'max:100'], 'season' => ['required', 'string', 'min:1', 'max:100'],
                'points_win' => ['required', 'integer', 'between:0,10'], 'points_draw' => ['required', 'integer', 'between:0,10'], 'points_loss' => ['required', 'integer', 'between:0,10']],
            'competition-invite' => $op + ['school_id' => ['required', 'integer', 'min:1']],
            'competition-decision' => $op + ['decision' => ['required', Rule::in(['accept', 'decline'])]],
            default => [],
        };
    }

    public function create(User $actor, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('competition-create'))->validate();

        return $this->shared->once($actor, 'competition:new', $data, fn () => $this->access->manage($actor), function () use ($actor, $data) {
            $row = SharedCompetition::query()->create(['kind' => $data['kind'], 'name' => trim($data['name']), 'discipline' => trim($data['discipline']), 'season' => trim($data['season']),
                'points_win' => (int) $data['points_win'], 'points_draw' => (int) $data['points_draw'], 'points_loss' => (int) $data['points_loss'], 'active' => true, 'revision' => 1]);
            $part = $this->shared->start(self::TYPE, (int) $row->id, 'organiser', $actor);
            Audit::record('competition.created', $part, [], ['record_id' => (int) $row->id, 'kind' => $row->kind]);

            return $this->view($actor, (int) $row->id);
        });
    }

    public function invite(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('competition-invite'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->manage($actor);
            $part = $this->shared->requireHere(self::TYPE, $id);
            abort_unless($part->role === 'organiser', 403, 'Only the organising school invites members.');
        };

        return $this->shared->once($actor, 'competition-invite:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            SharedCompetition::query()->lockForUpdate()->findOrFail($id);
            $this->shared->invite(self::TYPE, $id, (int) $data['school_id'], 'member');

            return $this->view($actor, $id);
        });
    }

    public function decide(User $actor, int $id, array $input): array
    {
        $this->access->manage($actor);
        $data = Validator::make($input, self::rules('competition-decision'))->validate();
        $authority = function () use ($actor, $id) {
            $this->access->manage($actor);
            $this->shared->requireHere(self::TYPE, $id);
        };

        return $this->shared->once($actor, 'competition-decision:'.$id, $data, $authority, function () use ($actor, $id, $data) {
            SharedCompetition::query()->lockForUpdate()->findOrFail($id);
            $this->shared->decide(self::TYPE, $id, $actor, $data['decision'] === 'accept');

            return $this->view($actor, $id);
        });
    }

    public function listing(User $actor): array
    {
        $this->shared->ownStaff();
        $this->access->staff($actor);
        $ids = $this->shared->idsHere(self::TYPE);

        return ['items' => SharedCompetition::query()->whereIn('id', $ids)->orderBy('name')->get()->map(fn (SharedCompetition $row) => $this->summary($row))->all()];
    }

    /**
     * What this school sees: the competition and the organiser's name; once it
     * accepted, the members (names and states) and the standings.
     */
    public function view(User $actor, int $id): array
    {
        $this->shared->ownStaff();
        $this->access->staff($actor);
        $this->shared->requireHere(self::TYPE, $id);
        $row = SharedCompetition::query()->findOrFail($id);
        $summary = $this->summary($row);
        $accepted = $summary['my_state'] === SharedParticipant::ACCEPTED;
        $members = $accepted ? $this->shared->participants(self::TYPE, $id)->map(fn ($m) => ['name' => $m['name'], 'role' => $m['role'], 'state' => $m['state']])->values()->all() : [];

        return $summary + ['members' => $members, 'standings' => $accepted ? $this->standings($row) : [], 'can_invite' => $accepted && $summary['my_role'] === 'organiser' && $this->access->manages($actor),
            'can_decide' => $summary['my_state'] === SharedParticipant::INVITED && $this->access->manages($actor)];
    }

    private function summary(SharedCompetition $row): array
    {
        $everyone = $this->shared->participants(self::TYPE, (int) $row->id);
        $mine = $this->shared->here(self::TYPE, (int) $row->id);

        return ['id' => (int) $row->id, 'kind' => (string) $row->kind, 'name' => (string) $row->name, 'discipline' => (string) $row->discipline, 'season' => (string) $row->season,
            'points_win' => $row->points_win, 'points_draw' => $row->points_draw, 'points_loss' => $row->points_loss,
            'organiser' => (string) ($everyone->firstWhere('role', 'organiser')['name'] ?? ''), 'my_role' => (string) $mine?->role, 'my_state' => (string) $mine?->state];
    }

    /**
     * The running table: every confirmed, not cancelled fixture of the
     * competition between accepted members (or a member and an opponent
     * outside NavigateEd). Teams are a school's name and its team label.
     *
     * @return list<array{team: string, played: int, won: int, drawn: int, lost: int, scored: int, conceded: int, difference: int, points: int}>
     */
    public function standings(SharedCompetition $competition): array
    {
        $members = $this->shared->participants(self::TYPE, (int) $competition->id)->filter(fn ($m) => $m['state'] === SharedParticipant::ACCEPTED)->keyBy('school_id');
        $table = [];
        $fixtures = SharedFixture::query()->where('shared_competition_id', $competition->id)->where('status', 'scheduled')->where('result_state', 'confirmed')
            ->orderBy('on_date')->orderBy('id')->limit(self::FIXTURE_LIMIT)->get();
        foreach ($fixtures as $fixture) {
            $teams = [];
            foreach ($this->shared->participants(Fixtures::TYPE, (int) $fixture->id) as $part) {
                if ($part['state'] !== SharedParticipant::ACCEPTED || ! $members->has($part['school_id'])) {
                    continue 2;
                }
                $label = (string) $this->shared->published(FixtureSide::class, $part['school_id'], ['team_label'], fn ($q) => $q->where('shared_fixture_id', $fixture->id))->value('team_label');
                $teams[$part['role']] = ['key' => 's'.$part['school_id'].':'.mb_strtolower($label), 'team' => trim($part['name'].' '.$label)];
            }
            if ($fixture->external_opponent !== null) {
                $teams['guest'] = ['key' => 'x:'.mb_strtolower(trim($fixture->external_opponent)), 'team' => (string) $fixture->external_opponent];
            }
            if (! isset($teams['host'], $teams['guest'])) {
                continue;
            }
            // An academic competition's adjudicators name the winning side; a match is won on the score.
            $host = $fixture->winner === null ? (int) $fixture->host_score <=> (int) $fixture->guest_score : match ($fixture->winner) {
                'host' => 1, 'guest' => -1, default => 0
            };
            $this->tally($table, $teams['host'], (int) $fixture->host_score, (int) $fixture->guest_score, $host, $competition);
            $this->tally($table, $teams['guest'], (int) $fixture->guest_score, (int) $fixture->host_score, -$host, $competition);
        }
        $rows = array_values($table);
        usort($rows, fn ($a, $b) => [$b['points'], $b['difference'], $b['scored'], $a['team']] <=> [$a['points'], $a['difference'], $a['scored'], $b['team']]);

        return $rows;
    }

    private function tally(array &$table, array $team, int $for, int $against, int $outcome, SharedCompetition $competition): void
    {
        $row = $table[$team['key']] ?? ['team' => $team['team'], 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'scored' => 0, 'conceded' => 0, 'difference' => 0, 'points' => 0];
        $row['played']++;
        $row['scored'] += $for;
        $row['conceded'] += $against;
        $row['difference'] = $row['scored'] - $row['conceded'];
        if ($outcome > 0) {
            $row['won']++;
            $row['points'] += $competition->points_win;
        } elseif ($outcome === 0) {
            $row['drawn']++;
            $row['points'] += $competition->points_draw;
        } else {
            $row['lost']++;
            $row['points'] += $competition->points_loss;
        }
        $table[$team['key']] = $row;
    }
}
