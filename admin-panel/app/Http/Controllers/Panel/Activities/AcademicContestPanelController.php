<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activities\ActivityGroup;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardDefinition;
use App\Models\User;
use App\Services\Activities\Fixtures\AcademicAwards;
use App\Services\Activities\Fixtures\AcademicContests;
use App\Services\Activities\Fixtures\Fixtures;
use App\Services\Activities\Fixtures\ReasoningReviews;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /academic-competitions: debates, quizzes and speech contests between
 * schools for an activity manager (Action Plan 5.3). The pages call the same
 * services as the API; nothing here reads another school's rows. Answering
 * an invitation and keeping the team sheet of entrants are on the fixture page.
 */
class AcademicContestPanelController extends Controller
{
    public function __construct(private readonly AcademicContests $contests) {}

    public function index(Request $r): Response
    {
        $schools = [];
        $search = is_string($r->input('school_search')) ? trim($r->input('school_search')) : '';
        if ($search !== '') {
            try {
                $schools = app(Fixtures::class)->schools($r->user(), ['search' => $search])['items'];
            } catch (ValidationException) {
                $schools = [];
            }
        }

        return response()->view('activities.academic.index', [
            'screen' => $this->screen($r, ['list', 'create'], $search !== '' ? 'create' : 'list'),
            'formInput' => $this->formInput($r),
            'messages' => $r->attributes->get('fixture_errors', []),
            'contests' => $this->contests->listing($r->user(), max(1, min(10000, (int) $r->query('page', 1)))),
            'groups' => ActivityGroup::query()->where('active', true)->orderBy('name')->limit(500)->pluck('name', 'id')->all(),
            'kinds' => AcademicContests::kinds(),
            'schools' => $schools,
            'search' => $search,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $r, int $competition): Response
    {
        $view = $this->contests->view($r->user(), $competition);
        $awards = app(ModuleGate::class)->allows('awards')
            ? AwardDefinition::query()->where('active', true)->orderBy('name')->limit(200)->pluck('name', 'id')->all() : [];

        $staffIds = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereIn('role', ['Teacher', 'Facilitator', 'Super Admin', 'School Admin', 'Principal'])->select('user_id');
        $staff = User::query()->whereIn('id', $staffIds)->where('status', 1)->orderBy('first_name')->limit(500)->get()->mapWithKeys(fn (User $u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all();

        $reviewId = is_scalar($r->input('review_id')) ? max(0, (int) $r->input('review_id')) : 0;
        if ($reviewId) {
            abort_unless(collect($view['academic']['reviews'])->contains('id', $reviewId), 404);
        }

        return response()->view('activities.academic.show', [
            'contest' => $view, 'definitions' => $awards, 'staff' => $staff,
            'screen' => $this->screen($r, ['overview', 'configuration', 'adjudicators', 'result', 'feedback', 'history', 'share', 'awards'], 'overview'),
            'reviewId' => $reviewId,
            'formInput' => $this->formInput($r),
            'messages' => $r->attributes->get('fixture_errors', []),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function write(Request $r, ?int $competition = null, ?int $review = null): Response|RedirectResponse
    {
        $action = substr((string) $r->route()->getName(), strlen('academic-competitions.'));
        $input = $r->except(['_token']);
        $destination = ['details' => 'configuration', 'adjudicators' => 'adjudicators', 'result' => 'result', 'review' => 'history', 'share' => 'history', 'awards' => 'awards'][$action] ?? 'overview';
        $back = $competition === null ? route('academic-competitions.index') : route('academic-competitions.show', ['competition' => $competition, 'screen' => $destination]);
        try {
            $result = match ($action) {
                'create' => $this->contests->create($r->user(), $this->nullable($input, ['guest_school_id', 'external_opponent', 'shared_competition_id', 'starts_at', 'field', 'format', 'rounds', 'host_stance'])),
                'details' => $this->contests->details($r->user(), (int) $competition, $this->nullable($input, ['format', 'rounds', 'host_stance'])),
                'adjudicators' => $this->contests->adjudicators($r->user(), (int) $competition, $this->adjudicators($input)),
                'result' => $this->contests->result($r->user(), (int) $competition, $this->result($input)),
                'review' => app(ReasoningReviews::class)->request($r->user(), (int) $competition, $input),
                'share' => app(ReasoningReviews::class)->share($r->user(), (int) $competition, (int) $review, $input),
                'awards' => app(AcademicAwards::class)->link($r->user(), (int) $competition, $input + ['learner_ids' => []]),
            };
        } catch (ValidationException $e) {
            return $this->failure($r, $competition, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [403, 409, 422], true) || ($e->getStatusCode() === 403 && $action !== 'review')) {
                throw $e;
            }

            return $this->failure($r, $competition, [__($e->getMessage())], $e->getStatusCode());
        }
        $to = $action === 'create' ? route('academic-competitions.show', ['competition' => $result['id']]) : $back;

        return redirect()->to($to)->with('success', __('Saved'));
    }

    private function screen(Request $r, array $allowed, string $default): string
    {
        $screen = $r->input('screen');
        if (! $r->isMethodSafe() && ! is_string($screen)) {
            $action = substr((string) $r->route()->getName(), strlen('academic-competitions.'));
            $screen = ['create' => 'create', 'details' => 'configuration', 'review' => 'feedback', 'share' => 'share'][$action] ?? $action;
        }

        return is_string($screen) && in_array($screen, $allowed, true) ? $screen : $default;
    }

    /** Sensitive notes and reasoning are rendered once, never copied into the session. */
    private function formInput(Request $r): array
    {
        if ($r->isMethodSafe()) {
            return [];
        }
        $safe = array_filter($r->except(['_token']), fn ($value) => is_scalar($value) || $value === null);
        foreach (['staff', 'learner_ids', 'entrant_score', 'entrant_place'] as $key) {
            $safe[$key] = is_array($r->input($key)) ? array_slice(array_filter($r->input($key), 'is_scalar'), 0, 61, true) : [];
        }

        return $safe;
    }

    private function failure(Request $r, ?int $competition, array $messages, int $status): Response
    {
        $r->attributes->set('fixture_errors', $messages);
        if ($r->route('review') && ! $r->filled('review_id')) {
            $r->merge(['review_id' => (int) $r->route('review')]);
        }
        $response = $competition === null ? $this->index($r) : $this->show($r, $competition);

        return $response->setStatusCode($status)->header('Cache-Control', 'private, no-store');
    }

    /** Staff picked from a list, and outside adjudicators typed by name (one a line). */
    private function adjudicators(array $input): array
    {
        $rows = [];
        foreach (array_slice((array) ($input['staff'] ?? []), 0, 11) as $id) {
            if (is_scalar($id) && ctype_digit((string) $id)) {
                $rows[] = ['user_id' => (int) $id];
            }
        }
        foreach (array_slice(preg_split('/\R/', (string) ($input['outside'] ?? '')) ?: [], 0, 11) as $name) {
            if (trim($name) !== '') {
                $rows[] = ['name' => trim($name)];
            }
        }

        return ['operation_id' => $input['operation_id'] ?? null, 'adjudicators' => $rows];
    }

    /** Each entrant's score and place, from the form's rows. */
    private function result(array $input): array
    {
        $entrants = [];
        foreach ((array) ($input['entrant_score'] ?? []) as $id => $score) {
            if (! ctype_digit((string) $id)) {
                continue;
            }
            $place = $input['entrant_place'][$id] ?? null;
            if (($score === null || $score === '') && ($place === null || $place === '')) {
                continue;
            }
            $entrants[] = ['learner_id' => (int) $id, 'score' => $score === '' ? null : $score, 'place' => $place === '' ? null : $place];
        }
        unset($input['entrant_score'], $input['entrant_place']);

        return $this->nullable($input, ['reasons']) + ['entrants' => array_slice($entrants, 0, 61)];
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
