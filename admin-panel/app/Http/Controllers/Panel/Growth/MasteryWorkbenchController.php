<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\MasteryPanelRequest;
use App\Jobs\Growth\RecomputeMastery;
use App\Models\Subject;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Academics\AcademicAccess;
use App\Services\Growth\MasteryQueries;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MasteryWorkbenchController extends Controller
{
    public function index(MasteryPanelRequest $request): Response
    {
        $queries = app(MasteryQueries::class);
        $year = $request->validated('session_year_id') ?? app(SessionYearContext::class)->selectedId();
        $configuration = $queries->configuration($request->user(), $year === null ? null : (int) $year);
        $groupId = $request->validated('group_id') === null ? ($configuration['groups'][0]['id'] ?? null) : (int) $request->validated('group_id');
        $group = $groupId === null ? null : app(MasteryPolicy::class)->staff($request->user(), $groupId);
        abort_if($group !== null && (int) $group->session_year_id !== $configuration['session_year_id'], 404);
        $roster = $group === null ? null : $queries->roster($request->user(), $groupId, (int) $request->validated('page', 1));
        $learnerId = $request->validated('learner_id') === null ? ($roster['learners'][0]['learner_id'] ?? null) : (int) $request->validated('learner_id');
        abort_if($learnerId !== null && ($group === null || ! in_array($learnerId, array_column($roster['learners'], 'learner_id'), true)), 404);
        $view = $learnerId === null ? null : app(MasteryService::class)->view($request->user(), $learnerId, (int) $group->subject_id, $groupId);
        $subjects = $configuration['can_manage'] ? Subject::query()->orderBy('name')->limit(501)->get(['id', 'name'])->toArray() : [];
        abort_if(count($subjects) > 500, 422, 'The subject catalogue exceeds its bounded editor.');
        $subjectId = $request->validated('subject_id') ?? $group?->subject_id ?? ($subjects[0]['id'] ?? null);
        $graph = $subjectId === null ? null : $queries->graph($request->user(), (int) $subjectId, $groupId);

        $graphSubjectName = $subjectId === null ? null : Subject::query()->findOrFail((int) $subjectId)->name;
        $writable = app(ModuleGate::class)->writable('mastery') && ! app(SessionYearContext::class)->isReadOnly();

        return response()->view('growth.mastery', compact('configuration', 'group', 'roster', 'learnerId', 'view', 'subjects', 'graph', 'graphSubjectName', 'writable'));
    }

    public function save(MasteryPanelRequest $request, int $subject): Response
    {
        try {
            app(TopicGraph::class)->save($request->user(), $subject, $request->validated());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return response()->view('growth.mastery-conflict', ['submitted' => $request->validated(), 'current' => app(TopicGraph::class)->data($subject)], 409);
        }

        return redirect()->route('mastery.index', ['subject_id' => $subject])->with('success', __('Mastery graph saved'));
    }

    public function refresh(MasteryPanelRequest $request, int $subject): Response
    {
        app(AcademicAccess::class)->actor($request->user(), 'curriculum-publish');
        app(TopicGraph::class)->sync($subject);

        return redirect()->route('mastery.index', ['subject_id' => $subject])->with('success', __('Published mastery sources refreshed'));
    }

    public function rebuild(MasteryPanelRequest $request, int $group): Response
    {
        app(MasteryPolicy::class)->staff($request->user(), $group);
        RecomputeMastery::dispatch(app(CurrentSchool::class)->id(), $group);

        return redirect()->route('mastery.index', ['group_id' => $group])->with('success', __('Mastery evidence refresh queued'));
    }
}
