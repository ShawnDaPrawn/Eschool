<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\ExamMemoRequest;
use App\Http\Requests\Learning\ExamMemoReviewRequest;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** /exam-memos: teachers capture and review the structured mark points of exam coach memos (Action Plan 3.15). */
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
        return ['none' => __('No rule'), 'exact_value' => __('Exact value'), 'units_required' => __('Units required')];
    }
}
