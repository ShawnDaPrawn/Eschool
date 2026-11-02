<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\QuestionBankRequest;
use App\Models\Learning\PracticeItem;
use App\Services\Learning\QuestionBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** /question-banks: teachers' practice questions by subject and grade (Action Plan 3.6). */
class QuestionBankController extends Controller
{
    public function __construct(private readonly QuestionBanks $banks) {}

    public function index(QuestionBankRequest $request): View
    {
        $scopes = $this->banks->scopes($request->user());
        $asked = $request->validated('scope');
        $chosen = $asked === null ? ($scopes[0] ?? null) : collect($scopes)->first(fn ($s) => $asked === $s['subject_id'].'-'.$s['grade_level_id']);
        // Another school's subject, or a bank this teacher may not open, is not found.
        abort_if($chosen === null && $asked !== null, 404);
        $bank = $chosen === null ? null : $this->banks->bank($request->user(), $chosen['subject_id'], $chosen['grade_level_id'], (int) $request->validated('page', 1));

        return view('learning.question-banks', ['scopes' => $scopes, 'chosen' => $chosen, 'bank' => $bank, 'visibilities' => PracticeItem::VISIBILITIES]);
    }

    public function use(QuestionBankRequest $request, int $item): RedirectResponse
    {
        return $this->done(fn () => $this->banks->use($request->user(), $item, (int) $request->validated('group_id')), __('The question is now used in that group. The list shows whether its learners get it yet.'));
    }

    public function stop(QuestionBankRequest $request, int $item): RedirectResponse
    {
        return $this->done(fn () => $this->banks->stop($request->user(), $item, (int) $request->validated('group_id')), __('The group no longer practises this question. It stays in the bank.'));
    }

    public function update(QuestionBankRequest $request, int $item): RedirectResponse
    {
        return $this->done(fn () => $this->banks->update($request->user(), $item, $request->validated()), __('Question saved.'));
    }

    /** A refusal the teacher can act on (a rule of the bank) goes back to the page; 403 and 404 stay errors. */
    private function done(callable $write, string $message): RedirectResponse
    {
        try {
            $write();
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return back()->withErrors(['bank' => $error->getMessage()]);
        }

        return back()->with('success', $message);
    }
}
