<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Recognition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recognition\RecognitionPanelRequest;
use App\Models\Activities\ActivityGroup;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardCategoryAdmin;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\HouseMembership;
use App\Models\Recognition\HousePoint;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Panel\SessionYearContext;
use App\Services\Recognition\AwardNominations;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\HousePoints;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Recognition\RecognitionCatalog;
use App\Services\Recognition\RecognitionQueries;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecognitionPanelController extends Controller
{
    private function model(RecognitionPanelRequest $r, array $messages = []): array
    {
        $module = (string) $r->route('module');
        $q = app(RecognitionQueries::class);
        $config = $q->configuration($r->user(), $module);
        $year = (int) $r->input('session_year_id', app(SessionYearContext::class)->selectedId() ?? schoolSetting('session_year'));
        $learner = $r->filled('learner_id') ? (int) $r->input('learner_id') : null;
        $selected = null;
        $history = null;
        $profile = null;
        $membership = null;
        if ($learner !== null) {
            app(RecognitionAuthority::class)->retainedLearner($r->user(), $learner, $module);
            $selected = Students::with('user')->findOrFail($learner);
            if ($module === 'house_points') {
                $history = app(HousePoints::class)->history($r->user(), $learner, $year, false, (int) $r->input('before', 0));
                $membership = HouseMembership::where('learner_id', $learner)->where('session_year_id', $year)->where('active', true)->whereDate('starts_on', '<=', $config['access']['today'])->where(fn ($s) => $s->whereNull('ends_on')->orWhereDate('ends_on', '>=', $config['access']['today']))->first();
            } else {
                $profile = app(AwardPresentations::class)->profile($r->user(), $learner, false, (int) $r->input('before', 0));
            }
        }
        $choices = $q->learners($r->user(), $module, (string) $r->input('search', ''), (int) $r->input('after', 0));
        $counter = $module === 'house_points' ? app(HousePoints::class)->counters($r->user(), $year) : null;
        $queue = null;
        if ($module === 'awards') {
            try {
                $queue = $q->nominations($r->user(), $year, null, (int) $r->input('before', 0));
            } catch (HttpExceptionInterface $e) {
                if ($e->getStatusCode() !== 403) {
                    throw $e;
                }
            }
        }
        $operationId = Str::isUuid((string) $r->input('operation_id')) && $messages === [] ? (string) $r->input('operation_id') : (string) Str::uuid();
        $account = (int) $r->user()->id;
        $school = $config['access']['school_id'];
        $canWrite = $config['access']['can_write'] && ! app(SessionYearContext::class)->isReadOnly() && $year === $config['access']['session_year_id'];
        $assembly = $module === 'awards' && (bool) $r->input('assembly', false);
        $draft = [];
        if (! $r->isMethodSafe() && ! $assembly) {
            foreach (['reason' => 4000, 'criteria' => 4000, 'title' => 160, 'event' => 191, 'outside_organisation' => 160, 'name' => 160] as $key => $limit) {
                if (is_string($r->input($key))) {
                    $draft[$key] = mb_substr($r->input($key), 0, $limit);
                }
            }
        }
        $subjects = Subject::orderBy('name')->get(['id', 'name'])->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name])->all();
        $groupIds = [];
        if (! $config['access']['manager']) {
            foreach ($config['award_categories'] ?? [] as $category) {
                if ($category['scope_kind'] === 'group') {
                    $groupIds[] = (int) $category['activity_group_id'];
                    foreach ($config['award_windows'] ?? [] as $window) {
                        if ($window['award_category_id'] === $category['id'] && $window['session_year_id'] === $year && $window['activity_group_id'] !== null) {
                            $groupIds[] = (int) $window['activity_group_id'];
                        }
                    }
                }
            }
        }
        $groups = ActivityGroup::where('session_year_id', $year)->when(! $config['access']['manager'], fn ($q) => $q->whereIn('id', array_unique($groupIds)))->orderBy('name')->get(['id', 'name'])->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name])->all();
        $staff = [];
        if ($config['access']['manager']) {
            $staff = User::where('status', 1)->whereIn('id', SchoolMembership::active()->where('school_id', $school)->whereNotIn('role', ['Parent', 'Student'])->select('user_id'))->orderBy('first_name')->limit(500)->get()->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->full_name])->all();
        }
        $pointControls = [];
        if ($history !== null) {
            foreach ($history['items'] as $entry) {
                $p = HousePoint::findOrFail($entry['id']);
                $pointControls[$p->id] = $p->reverses_id === null && ! HousePoint::where('reverses_id', $p->id)->exists() && ($config['access']['manager'] || (int) $p->staff_id === $account);
            }
        }
        $presentationControls = [];
        if ($profile !== null) {
            foreach ($profile['items'] as $entry) {
                $p = AwardPresentation::findOrFail($entry['id']);
                $presentationControls[$p->id] = $config['access']['manager'] || ($p->award_category_id !== null && app(RecognitionAuthority::class)->categoryAdmin($r->user(), AwardCategory::findOrFail($p->award_category_id)));
            }
        }

        $canApprove = ! $config['settings']['principal_approval_required'] || app(AcademicAuthority::class)->currentLeadership($r->user(), 'principal', 0);
        $administrators = [];
        if ($module === 'awards' && $config['access']['manager']) {
            foreach (AwardCategoryAdmin::orderByDesc('id')->limit(200)->get() as $grant) {
                $administrators[] = ['id' => (int) $grant->id, 'revision' => $grant->revision, 'award_category_id' => (int) $grant->award_category_id, 'user_id' => (int) $grant->user_id, 'name' => User::find($grant->user_id)?->full_name ?? __('Unavailable staff member'), 'starts_on' => $grant->starts_on->toDateString(), 'ends_on' => $grant->ends_on->toDateString(), 'active' => $grant->active];
            }
        }

        $selectedGrade = null;
        $nominationDefinitions = [];
        $presentationDefinitions = [];
        if ($module === 'awards' && $selected !== null) {
            try {
                $a = app(RecognitionAuthority::class);
                $place = $a->placement($learner, $year);
                $selectedGrade = (int) $place->homeroom->class->grade_level_id;
                foreach ($config['award_definitions'] as $definition) {
                    if (! $definition['active']) {
                        continue;
                    }$category = AwardCategory::findOrFail($definition['award_category_id']);
                    try {
                        $a->eligible($category, $selected, $place);
                    } catch (HttpExceptionInterface|ModelNotFoundException) {
                        continue;
                    }
                    if ($a->categoryAdmin($r->user(), $category) && $canApprove) {
                        $presentationDefinitions[] = $definition;
                    }
                    if (collect($config['award_windows'])->contains(fn ($w) => $w['award_category_id'] === $definition['award_category_id'] && $w['session_year_id'] === $year && $w['status'] === 'open' && $w['opens_on'] <= $config['access']['today'] && $w['closes_on'] >= $config['access']['today'])) {
                        $nominationDefinitions[] = $definition;
                    }
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return compact('selectedGrade', 'nominationDefinitions', 'presentationDefinitions', 'canApprove', 'administrators', 'subjects', 'groups', 'staff', 'pointControls', 'presentationControls', 'module', 'config', 'year', 'learner', 'selected', 'history', 'profile', 'membership', 'choices', 'counter', 'queue', 'operationId', 'account', 'school', 'canWrite', 'assembly', 'draft', 'messages');
    }

    public function index(RecognitionPanelRequest $r): Response
    {
        return response()->view('recognition.workbench', $this->model($r));
    }

    public function failure(RecognitionPanelRequest $r, array $messages, int $status): Response
    {
        return response()->view('recognition.workbench', $this->model($r, $messages), $status)->header('Cache-Control', 'private, no-store');
    }

    public function write(RecognitionPanelRequest $r): Response|RedirectResponse
    {
        $module = (string) $r->route('module');
        $op = (string) $r->route('operation');
        $id = (int) $r->route('record');
        $d = $r->validated();
        $actor = $r->user();
        try {
            if ($op === 'catalogue') {
                $kind = (string) $r->route('kind');
                abort_unless(($module === 'house_points') === in_array($kind, ['houses', 'point-categories', 'house-memberships'], true), 404);
                $method = RecognitionQueries::CATALOGUES[$kind] ?? abort(404);
                $row = app(RecognitionCatalog::class)->$method($actor, $d, $id > 0 ? $id : null);
            } else {
                $row = match ($op) {
                    'configure' => app(RecognitionCatalog::class)->configure($actor, $module, $d),
                    'issue' => $module === 'house_points' ? app(HousePoints::class)->issue($actor, $d) : abort(404),
                    'reverse' => $module === 'house_points' ? app(HousePoints::class)->reverse($actor, $id, $d) : abort(404),
                    'nominate' => $module === 'awards' ? app(AwardNominations::class)->nominate($actor, $d) : abort(404),
                    'review' => $module === 'awards' ? app(AwardNominations::class)->review($actor, $id, $d) : abort(404),
                    'withdraw' => $module === 'awards' ? app(AwardNominations::class)->withdraw($actor, $id, $d) : abort(404),
                    'present' => $module === 'awards' ? app(AwardPresentations::class)->present($actor, $d) : abort(404),
                    'outside' => $module === 'awards' ? app(AwardPresentations::class)->outside($actor, $d) : abort(404),
                    'void' => $module === 'awards' ? app(AwardPresentations::class)->void($actor, $id, $d) : abort(404),
                    default => abort(404)
                };
            }
        } catch (ValidationException $e) {
            return $this->failure($r, $e->validator->errors()->all(), 422);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return $this->failure($r, [$e->getMessage(), __('Review the current recognition record before trying again.')], $e->getStatusCode());
        }

        $selection = ['module' => $module];
        if ($module === 'awards' && (bool) $r->input('assembly', false)) {
            $selection += ['assembly' => 1, 'session_year_id' => (int) $r->input('session_year_id', schoolSetting('session_year'))];
        } elseif ($r->filled('learner_id')) {
            $selection['learner_id'] = (int) $r->input('learner_id');
        }

        return redirect()->route('recognition.index', $selection)->with('success', __('Recognition change saved'))->header('Cache-Control', 'private, no-store');
    }
}
