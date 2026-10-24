<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\TransitionPanelRequest;
use App\Services\Academics\Transitions\ExitArtifacts;
use App\Services\Academics\Transitions\ExitPackages;
use App\Services\Academics\Transitions\TransitionFlow;
use App\Services\Academics\Transitions\TransitionPolicies;
use App\Services\Academics\Transitions\TransitionQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransitionWorkbenchController extends Controller
{
    private function model(TransitionPanelRequest $r): array
    {
        $q = app(TransitionQueries::class);
        $learner = $r->validated('learner_id') === null ? null : $q->learner($r->user(), (int) $r->validated('learner_id'), (int) $r->validated('history_page', 1));
        if ($learner !== null) {
            foreach ($learner['exports'] as &$export) {
                $export['download_url'] = $export['state'] === 'ready' ? URL::temporarySignedRoute('academic-transitions.export-download', now()->addMinutes(10)->min(CarbonImmutable::parse($export['expires_at'])), ['package' => $export['id'], 'school' => app(CurrentSchool::class)->id(), 'reader' => $r->user()->id]) : null;
            }
        }
        $configuration = $q->configuration($r->user(), $r->validated('session_year_id') === null ? null : (int) $r->validated('session_year_id'));
        $grade = $r->validated('grade_level_id') === null ? ($configuration['grades'][0] ?? null) : collect($configuration['grades'])->firstWhere('id', (int) $r->validated('grade_level_id'));
        abort_if($r->validated('grade_level_id') !== null && $grade === null, 404);

        return ['configuration' => $configuration, 'learner' => $learner, 'search' => $r->validated('search', ''), 'results' => $q->search($r->user(), (string) $r->validated('search', ''), (int) $r->validated('page', 1)),
            'kind' => $r->validated('kind', 'join'), 'grade' => $grade, 'preview' => null, 'exitPreview' => null, 'draft' => $r->isMethodSafe() ? [] : $r->validated(), 'conflict' => null];
    }

    public function index(TransitionPanelRequest $r): Response
    {
        return response()->view('academics.transition-workbench', $this->model($r));
    }

    public function preview(TransitionPanelRequest $r): Response
    {
        return response()->view('academics.transition-workbench', array_replace($this->model($r), ['preview' => app(TransitionFlow::class)->preview($r->user(), $r->validated())]));
    }

    public function commit(TransitionPanelRequest $r): Response
    {
        try {
            $result = app(TransitionFlow::class)->commit($r->user(), $r->validated());
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 409) {
                throw $e;
            }

            return response()->view('academics.transition-workbench', array_replace($this->model($r), ['conflict' => $e->getMessage()]), 409);
        }

        return redirect()->route('academic-transitions.index', ['learner_id' => $result['learner_id'], 'session_year_id' => $result['session_year_id'], 'kind' => $result['kind']])->with('success', __('Learner change recorded'));
    }

    public function policy(TransitionPanelRequest $r, int $grade): Response
    {
        app(TransitionPolicies::class)->save($r->user(), $grade, $r->validated());

        return redirect()->route('academic-transitions.index', ['grade_level_id' => $grade])->with('success', __('Grade defaults saved'));
    }

    public function retention(TransitionPanelRequest $r): Response
    {
        app(TransitionPolicies::class)->saveRetention($r->user(), $r->validated());

        return redirect()->route('academic-transitions.index')->with('success', __('Export copy retention saved'));
    }

    public function exportPreview(TransitionPanelRequest $r): Response
    {
        $preview = app(ExitPackages::class)->preview($r->user(), $r->validated());
        abort_unless((int) $r->validated('learner_id') === $preview['learner_id'], 404);

        return response()->view('academics.transition-workbench', array_replace($this->model($r), ['exitPreview' => $preview]));
    }

    public function exportPrepare(TransitionPanelRequest $r): Response
    {
        $result = app(ExitPackages::class)->prepare($r->user(), $r->validated());

        return redirect()->route('academic-transitions.index', ['learner_id' => $result['learner_id'], 'kind' => 'leave'])->with('success', __('Exit package queued. Refresh its status shortly.'));
    }

    public function exportRetry(TransitionPanelRequest $r, int $package): Response
    {
        $result = app(ExitPackages::class)->retry($r->user(), $package);

        return redirect()->route('academic-transitions.index', ['learner_id' => $result['learner_id'], 'kind' => 'leave'])->with('success', __('Exit package status updated'));
    }

    public function exportDownload(TransitionPanelRequest $r, int $package): Response
    {
        return response(app(ExitArtifacts::class)->read($r->user(), $package), 200, ['Content-Type' => 'application/zip', 'Content-Disposition' => 'attachment; filename="academic-exit-'.$package.'.zip"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
