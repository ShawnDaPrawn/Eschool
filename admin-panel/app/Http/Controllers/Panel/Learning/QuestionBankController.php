<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\QuestionBankRequest;
use App\Models\Learning\PracticeItem;
use App\Services\Learning\Library\LibraryContent;
use App\Services\Learning\PracticeAccess;
use App\Services\Learning\PracticeAnswers;
use App\Services\Learning\PracticeBank;
use App\Services\Learning\PracticeChoices;
use App\Services\Learning\PracticeMedia;
use App\Services\Learning\QuestionBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use InvalidArgumentException;
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

    /**
     * Write a new practice question for one of the teacher's groups, of any
     * type (Action Plan 3.11): it is approved for that group and joins the
     * subject-and-grade bank like a question approved in the teacher app.
     */
    public function write(QuestionBankRequest $request): View
    {
        $groups = app(PracticeChoices::class)->access($request->user())['staff_groups'];
        $asked = $request->validated('group');
        $group = $asked === null ? null : collect($groups)->firstWhere('id', (int) $asked);
        abort_if($asked !== null && $group === null, 404);
        $choices = $group === null ? null : app(PracticeChoices::class)->staff($request->user(), (int) $group['id']);

        return view('learning.question-write', ['groups' => $groups, 'group' => $group, 'choices' => $choices, 'kinds' => LibraryContent::QUESTION_KINDS,
            'operationId' => (string) Str::uuid()]);
    }

    public function create(QuestionBankRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $groupId = (int) $data['group_id'];
        try {
            $image = null;
            if ($data['question_kind'] === 'diagram_label') {
                $id = $data['diagram_id'] ?? null;
                if ($request->file('diagram') !== null) {
                    app(PracticeAccess::class)->staff($request->user(), $groupId, true);
                    $id = (string) app(PracticeMedia::class)->storeSchoolDiagram($request->user(), $request->file('diagram'))->id;
                }
                $image = app(PracticeMedia::class)->withSchoolDiagram(['kind' => 'diagram_label', 'diagram' => ['id' => $id]])['diagram'];
            }
            $definition = app(PracticeAnswers::class)->fromForm($data, $image);
            app(PracticeBank::class)->approve($request->user(), $groupId, null, ['operation_id' => $data['operation_id'], 'expected_revision' => 0,
                'topic_id' => (int) $data['topic_id'], 'content_version_id' => (int) $data['content_version_id'], 'estimated_seconds' => (int) $data['estimated_seconds'],
                'active' => true, 'definition' => $definition]);
        } catch (InvalidArgumentException $error) {
            return back()->withInput($request->except('diagram'))->withErrors(['question' => $error->getMessage()]);
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return back()->withInput($request->except('diagram'))->withErrors(['question' => $error->getMessage()]);
        }

        return redirect()->route('question-banks.write', ['group' => $groupId])->with('success', __('Question approved. Its learners get it in daily practice once its material and topic are published to the group.'));
    }

    /** A school diagram picture, for the teachers who write and use practice questions. */
    public function diagram(QuestionBankRequest $request, string $diagram): Response
    {
        abort_unless(app(PracticeChoices::class)->access($request->user())['staff_groups'] !== [], 403);

        return response(app(PracticeMedia::class)->diagram(['source' => 'school', 'id' => $diagram]), 200, ['Content-Type' => 'image/jpeg', 'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
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
