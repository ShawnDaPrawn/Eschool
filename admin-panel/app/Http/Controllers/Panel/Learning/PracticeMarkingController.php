<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\QuestionBankRequest;
use App\Services\Learning\Library\LibraryContent;
use App\Services\Learning\PracticeChoices;
use App\Services\Learning\PracticeMarking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /question-banks/marking: practice answers waiting for a teacher (Action
 * Plan 3.11), for the current teachers of the learner's group only.
 */
class PracticeMarkingController extends Controller
{
    public function __construct(private readonly PracticeMarking $marking) {}

    public function index(QuestionBankRequest $request): View
    {
        $groups = collect(app(PracticeChoices::class)->access($request->user())['staff_groups'])
            ->map(fn ($group) => $group + ['waiting' => $this->marking->count($request->user(), (int) $group['id'])])->all();
        $asked = $request->validated('group');
        $group = $asked === null ? (collect($groups)->firstWhere(fn ($g) => $g['waiting'] > 0) ?? null) : collect($groups)->firstWhere('id', (int) $asked);
        abort_if($asked !== null && $group === null, 404);
        $queue = $group === null ? null : $this->marking->queue($request->user(), (int) $group['id'], (int) $request->validated('page', 1));

        return view('learning.practice-marking', ['groups' => $groups, 'group' => $group, 'queue' => $queue, 'kinds' => LibraryContent::QUESTION_KINDS,
            'operationId' => fn () => (string) Str::uuid()]);
    }

    public function audio(QuestionBankRequest $request, int $group, int $review): Response
    {
        $audio = $this->marking->audio($request->user(), $group, $review);

        return response($audio['bytes'], 200, ['Content-Type' => $audio['mime'], 'Content-Length' => (string) strlen($audio['bytes']), 'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function mark(QuestionBankRequest $request, int $group, int $review): RedirectResponse
    {
        try {
            $this->marking->mark($request->user(), $group, $review, $request->validated());
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return back()->withErrors(['marking' => $error->getMessage()]);
        }

        return redirect()->route('question-banks.marking', ['group' => $group])->with('success', __('Marked. The learner sees the result the next time their practice loads.'));
    }
}
