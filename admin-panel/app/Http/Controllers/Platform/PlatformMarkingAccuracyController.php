<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\MarkingAccuracyRequest;
use App\Services\Learning\Marking\Accuracy\AccuracyEvaluation;
use App\Services\Learning\Marking\Accuracy\AccuracyPages;
use App\Services\Learning\Marking\Accuracy\EvaluationImports;
use App\Services\Learning\Marking\Accuracy\MarkingGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/** HQ: the marking accuracy gate (Action Plan 3.18): evaluation scripts, runs and each subject's learner-marking switch. */
class PlatformMarkingAccuracyController extends Controller
{
    public function index(Request $request, AccuracyPages $pages): View
    {
        return view('saas.marking-accuracy.index', $pages->index($request->user()) + ['report' => $request->session()->get('import_report')]);
    }

    public function run(Request $request, AccuracyPages $pages, int $run): View
    {
        return view('saas.marking-accuracy.run', $pages->run($request->user(), $run));
    }

    public function storePaper(MarkingAccuracyRequest $request, EvaluationImports $imports): RedirectResponse
    {
        $report = $imports->paper($request->user(), $this->content($request->file('file')), $request->boolean('dry_run'));

        return redirect()->route('saas.marking-accuracy.index')->with('import_report', $report);
    }

    public function storeScripts(MarkingAccuracyRequest $request, EvaluationImports $imports): RedirectResponse
    {
        $report = $imports->scripts($request->user(), (int) $request->validated('paper_id'), (string) $request->validated('format'), $this->content($request->file('file')), $request->boolean('dry_run'));

        return redirect()->route('saas.marking-accuracy.index')->with('import_report', $report);
    }

    public function evaluate(MarkingAccuracyRequest $request, AccuracyEvaluation $evaluation, string $subject): RedirectResponse
    {
        $run = $evaluation->run($request->user(), $subject);

        return redirect()->route('saas.marking-accuracy.runs.show', $run->id)->with('success', $run->passed ? __('The run passed.') : __('The run did not pass.'));
    }

    public function switchOn(MarkingAccuracyRequest $request, MarkingGate $gate, string $subject): RedirectResponse
    {
        $gate->switchOn($request->user(), $subject, $request->validated());

        return redirect()->route('saas.marking-accuracy.index')->with('success', __('Learner marking is switched on in this subject.'));
    }

    public function switchOff(MarkingAccuracyRequest $request, MarkingGate $gate, string $subject): RedirectResponse
    {
        $gate->switchOff($request->user(), $subject, $request->validated());

        return redirect()->route('saas.marking-accuracy.index')->with('success', __('Learner marking is switched off in this subject.'));
    }

    private function content(?UploadedFile $file): string
    {
        return $file === null ? '' : (string) file_get_contents($file->getRealPath());
    }
}
