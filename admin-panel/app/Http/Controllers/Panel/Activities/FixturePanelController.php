<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Students;
use App\Services\Activities\CommunityAuthority;
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
            'screen' => $this->screen($r, ['fixtures', 'fixture-create', 'competitions', 'competition-create'], $r->filled('school_search') ? 'fixture-create' : 'fixtures'),
            'formInput' => $this->formInput($r),
            'messages' => $r->attributes->get('fixture_errors', []),
            'fixtures' => $listing,
            'competitions' => $competitions,
            'groups' => ActivityGroup::query()->where('active', true)->orderBy('name')->limit(500)->pluck('name', 'id')->all(),
            'schools' => $this->search($r),
            'search' => is_string($r->input('school_search')) ? trim($r->input('school_search')) : '',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $r, int $fixture): Response
    {
        $view = $this->fixtures->view($r->user(), $fixture);
        $canChangeGroup = app(CommunityAuthority::class)->manages($r->user());
        $selectedGroupId = (int) ($view['side']['activity_group_id'] ?? 0);
        if ($view['side'] !== null && $r->filled('activity_group_id')) {
            $requested = $r->input('activity_group_id');
            abort_unless(is_scalar($requested) && ctype_digit((string) $requested), 422);
            $requested = (int) $requested;
            abort_unless($requested === $selectedGroupId || $canChangeGroup, 403);
            $selectedGroupId = (int) ActivityGroup::query()->where('active', true)->findOrFail($requested)->id;
        }

        return response()->view('activities.fixtures.show', [
            'screen' => $this->screen($r, ['overview', 'invitation', 'team', 'result', 'configuration'], 'overview'),
            'formInput' => $this->formInput($r),
            'messages' => $r->attributes->get('fixture_errors', []),
            'canChangeGroup' => $canChangeGroup,
            'selectedGroupId' => $selectedGroupId,
            'fixture' => $view,
            'groups' => ActivityGroup::query()->where('active', true)->orderBy('name')->limit(500)->pluck('name', 'id')->all(),
            'members' => $view['side'] === null ? [] : $this->members($selectedGroupId, $view['on_date']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function competition(Request $r, int $competition): Response
    {
        return response()->view('activities.fixtures.competition', [
            'screen' => $this->screen($r, ['overview', 'invitation', 'standings', 'members', 'invite'], $r->filled('school_search') ? 'invite' : 'overview'),
            'formInput' => $this->formInput($r),
            'messages' => $r->attributes->get('fixture_errors', []),
            'competition' => $this->competitions->view($r->user(), $competition),
            'schools' => $this->search($r),
            'search' => is_string($r->input('school_search')) ? trim($r->input('school_search')) : '',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function write(Request $r, ?int $fixture = null): Response|RedirectResponse
    {
        return $this->handle($r, $fixture, null);
    }

    public function competitionWrite(Request $r, ?int $competition = null): Response|RedirectResponse
    {
        return $this->handle($r, null, $competition);
    }

    private function handle(Request $r, ?int $fixture, ?int $competition): Response|RedirectResponse
    {
        $action = substr((string) $r->route()->getName(), strlen('activity-fixtures.'));
        $input = $r->except(['_token']);
        $destination = ['side' => 'team', 'schedule' => 'configuration', 'result' => 'result', 'competition-invite' => 'members'][$action] ?? 'overview';
        $back = $competition !== null ? route('activity-fixtures.competition-show', ['competition' => $competition, 'screen' => $destination])
            : ($fixture !== null ? route('activity-fixtures.show', ['fixture' => $fixture, 'screen' => $destination]) : route('activity-fixtures.index'));
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
            return $this->failure($r, $fixture, $competition, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, $fixture, $competition, [__($e->getMessage())], $e->getStatusCode());
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
        $term = is_string($r->input('school_search')) ? trim($r->input('school_search')) : '';
        if ($term === '') {
            return [];
        }
        try {
            return $this->fixtures->schools($r->user(), ['search' => $term])['items'];
        } catch (ValidationException) {
            return [];
        }
    }

    private function screen(Request $r, array $screens, string $default): string
    {
        $screen = $r->input('screen');

        if (! $r->isMethodSafe() && ! is_string($screen)) {
            $action = substr((string) $r->route()->getName(), strlen('activity-fixtures.'));
            $screen = ['create' => 'fixture-create', 'competition-create' => 'competition-create', 'schedule' => 'configuration', 'decision' => 'invitation', 'side' => 'team', 'result' => 'result', 'competition-invite' => 'invite', 'competition-decision' => 'invitation'][$action] ?? $default;
        }

        return is_string($screen) && in_array($screen, $screens, true) ? $screen : $default;
    }

    /** Private form values stay in this response; never flash team notes into session. */
    private function formInput(Request $r): array
    {
        if ($r->isMethodSafe()) {
            return [];
        }

        $safe = array_filter($r->except(['_token']), fn ($value) => is_scalar($value) || $value === null);
        foreach (['pick', 'label', 'position'] as $key) {
            if (is_array($r->input($key))) {
                $safe[$key] = array_slice(array_filter($r->input($key), 'is_scalar'), 0, 61, true);
            }
        }

        return $safe;
    }

    private function failure(Request $r, ?int $fixture, ?int $competition, array $messages, int $status): Response
    {
        $r->attributes->set('fixture_errors', $messages);
        $response = $competition !== null ? $this->competition($r, $competition) : ($fixture !== null ? $this->show($r, $fixture) : $this->index($r));

        return $response->setStatusCode($status)->header('Cache-Control', 'private, no-store');
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
