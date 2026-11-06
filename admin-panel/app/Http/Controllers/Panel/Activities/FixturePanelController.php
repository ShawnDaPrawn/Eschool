<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Students;
use App\Services\Activities\Fixtures\Competitions;
use App\Services\Activities\Fixtures\Fixtures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /activity-fixtures: fixtures and competitions between schools for an
 * activity manager (Action Plan 5.1, 5.2). The pages call the same services
 * as the API; nothing here reads another school's rows.
 */
class FixturePanelController extends Controller
{
    public function __construct(private readonly Fixtures $fixtures, private readonly Competitions $competitions) {}

    public function index(Request $r): Response
    {
        $listing = $this->fixtures->listing($r->user(), max(1, min(10000, (int) $r->query('page', 1))));
        $competitions = $this->competitions->listing($r->user())['items'];

        return response()->view('activities.fixtures.index', [
            'fixtures' => $listing,
            'competitions' => $competitions,
            'groups' => ActivityGroup::query()->where('active', true)->orderBy('name')->limit(500)->pluck('name', 'id')->all(),
            'schools' => $this->search($r),
            'search' => trim((string) $r->query('school_search', '')),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $r, int $fixture): Response
    {
        $view = $this->fixtures->view($r->user(), $fixture);

        return response()->view('activities.fixtures.show', [
            'fixture' => $view,
            'groups' => ActivityGroup::query()->where('active', true)->orderBy('name')->limit(500)->pluck('name', 'id')->all(),
            'members' => $view['side'] === null ? [] : $this->members((int) $view['side']['activity_group_id'], $view['on_date']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function competition(Request $r, int $competition): Response
    {
        return response()->view('activities.fixtures.competition', [
            'competition' => $this->competitions->view($r->user(), $competition),
            'schools' => $this->search($r),
            'search' => trim((string) $r->query('school_search', '')),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function write(Request $r, ?int $fixture = null): RedirectResponse
    {
        return $this->handle($r, $fixture, null);
    }

    public function competitionWrite(Request $r, ?int $competition = null): RedirectResponse
    {
        return $this->handle($r, null, $competition);
    }

    private function handle(Request $r, ?int $fixture, ?int $competition): RedirectResponse
    {
        $action = substr((string) $r->route()->getName(), strlen('activity-fixtures.'));
        $input = $r->except(['_token']);
        $back = $competition !== null ? route('activity-fixtures.competition-show', ['competition' => $competition])
            : ($fixture !== null ? route('activity-fixtures.show', ['fixture' => $fixture]) : route('activity-fixtures.index'));
        try {
            $result = match ($action) {
                'create' => $this->fixtures->create($r->user(), $this->nullable($input, ['guest_school_id', 'external_opponent', 'shared_competition_id', 'starts_at', 'field'])),
                'schedule' => $this->fixtures->schedule($r->user(), (int) $fixture, $this->nullable($input, ['starts_at', 'field'])),
                'decision' => $this->fixtures->decide($r->user(), (int) $fixture, $this->nullable($input, ['activity_group_id', 'team_label'])),
                'side' => $this->fixtures->saveSide($r->user(), (int) $fixture, $this->side($input)),
                'result' => $this->fixtures->report($r->user(), (int) $fixture, $input),
                'competition-create' => $this->competitions->create($r->user(), $input),
                'competition-invite' => $this->competitions->invite($r->user(), (int) $competition, $input),
                'competition-decision' => $this->competitions->decide($r->user(), (int) $competition, $input),
            };
        } catch (ValidationException $e) {
            return redirect()->to($back)->withErrors($e->errors());
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return redirect()->to($back)->withErrors(['fixture' => __($e->getMessage())]);
        }
        $to = match ($action) {
            'create' => route('activity-fixtures.show', ['fixture' => $result['id']]),
            'competition-create' => route('activity-fixtures.competition-show', ['competition' => $result['id']]),
            default => $back,
        };

        return redirect()->to($to)->with('success', __('Saved'));
    }

    /** @return list<array{id: int, name: string}> */
    private function search(Request $r): array
    {
        $term = trim((string) $r->query('school_search', ''));
        if ($term === '') {
            return [];
        }
        try {
            return $this->fixtures->schools($r->user(), ['search' => $term])['items'];
        } catch (ValidationException) {
            return [];
        }
    }

    /** The group's members on the fixture day: the learners who can be picked. */
    private function members(int $groupId, string $date): array
    {
        $ids = ActivityMembership::query()->where('activity_group_id', $groupId)->where('active', true)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->pluck('learner_id');

        return Students::query()->with('user')->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (Students $s) => [(int) $s->id => trim(($s->user?->first_name ?? '').' '.($s->user?->last_name ?? ''))])->sort()->all();
    }

    /** Ticked learners with their labels and positions become the selections. */
    private function side(array $input): array
    {
        $picked = array_values(array_filter((array) ($input['pick'] ?? []), fn ($id) => is_scalar($id) && ctype_digit((string) $id)));
        $input['selections'] = array_map(fn ($id) => ['learner_id' => (int) $id, 'sheet_label' => $this->text($input['label'][$id] ?? null), 'position' => $this->text($input['position'][$id] ?? null)], array_slice($picked, 0, 61));
        $input['sheet_shared'] = (bool) ($input['sheet_shared'] ?? false);
        unset($input['pick'], $input['label'], $input['position']);

        return $input;
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** Empty panel fields mean "none". */
    private function nullable(array $input, array $keys): array
    {
        foreach ($keys as $key) {
            if (($input[$key] ?? null) === '') {
                $input[$key] = null;
            }
        }

        return $input;
    }
}
