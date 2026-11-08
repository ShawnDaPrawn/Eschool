<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\ExamMarkingPreviewRequest;
use App\Http\Requests\Learning\ExamMemoRequest;
use App\Http\Requests\Learning\ExamMemoReviewRequest;
use App\Services\Learning\Marking\PaperMarking;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /exam-memos: teachers capture and review the structured mark points of exam
 * coach memos (Action Plan 3.15), and try marking a typed answer against them
 * (Action Plan 3.16, nothing stored).
 */
class ExamMemoPanelController extends Controller
{
    public function __construct(private readonly PastPaperMemos $memos) {}

    public function index(Request $request): View
    {
        $query = Validator::make($request->query(), ['group' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,1000']])->validate();

        return view('learning.exam-memos.index', $this->memos->listing($request->user(), isset($query['group']) ? (int) $query['group'] : null, (int) ($query['page'] ?? 1)));
    }

    public function show(Request $request, int $group, string $version): View
    {
        return view('learning.exam-memos.show', $this->memos->view($request->user(), $group, $version) + [
            'types' => self::types(), 'ruleKinds' => self::ruleKinds()]);
    }

    public function save(ExamMemoRequest $request, int $group, string $version): RedirectResponse
    {
        return $this->write(fn () => $this->memos->save($request->user(), $group, $version, $request->validated()), $group, $version, __('Mark points saved. Review the memo when every question is complete.'));
    }

    public function review(ExamMemoReviewRequest $request, int $group, string $version): RedirectResponse
    {
        return $this->write(fn () => $this->memos->review($request->user(), $group, $version, $request->validated()), $group, $version, __('Memo reviewed. Its mark points can now be used.'));
    }

    public function marking(Request $request, int $group, string $version): View
    {
        $query = Validator::make($request->query(), ['question' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9().-]{1,16}$/']])->validate();

        return view('learning.exam-memos.marking', app(PaperMarking::class)->page($request->user(), $group, $version) + [
            'types' => self::types(), 'ruleKinds' => self::ruleKinds(), 'chosen' => $query['question'] ?? null, 'answer' => '', 'result' => null]);
    }

    public function preview(ExamMarkingPreviewRequest $request, int $group, string $version): View|RedirectResponse
    {
        $marking = app(PaperMarking::class);
        try {
            $preview = $marking->preview($request->user(), $group, $version, $request->validated());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return redirect()->route('exam-memos.marking', ['group' => $group, 'version' => $version])->withInput()->withErrors(['question' => $error->getMessage()]);
        }

        return view('learning.exam-memos.marking', $marking->page($request->user(), $group, $version) + [
            'types' => self::types(), 'ruleKinds' => self::ruleKinds(), 'chosen' => $request->validated('question'),
            'answer' => (string) $request->validated('answer'), 'result' => $preview['result']]);
    }

    private function write(callable $call, int $group, string $version, string $success): RedirectResponse
    {
        try {
            $call();
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return redirect()->route('exam-memos.show', ['group' => $group, 'version' => $version])->withErrors(['memo' => $error->getMessage()]);
        }

        return redirect()->route('exam-memos.show', ['group' => $group, 'version' => $version])->with('success', $success);
    }

    public static function types(): array
    {
        return ['method' => __('Method'), 'answer' => __('Answer'), 'accuracy' => __('Accuracy'), 'consistent_accuracy' => __('Consistent accuracy'), 'explanation' => __('Explanation')];
    }

    public static function ruleKinds(): array
    {
        return ['none' => __('No rule'), 'exact_value' => __('Exact value'), 'units_required' => __('Units required'),
            'expression' => __('Expression or formula'), 'keywords' => __('Terms'), 'significant_figures' => __('Significant figures')];
    }
}
