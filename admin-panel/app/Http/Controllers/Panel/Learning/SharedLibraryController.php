<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\SharedLibraryRequest;
use App\Models\Library\LibraryContribution;
use App\Services\Learning\Library\SharedLibrary;
use App\Services\Learning\Library\SharedLibraryAccess;
use App\Services\Learning\Library\SharedLibraryBrowse;
use App\Services\Learning\PracticeChoices;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /shared-library: questions teachers share between schools (Action Plan
 * 5.4). Teachers browse, copy and rate shared questions and share their own;
 * leaders switch sharing on and approve what leaves the school.
 */
class SharedLibraryController extends Controller
{
    public function __construct(private readonly SharedLibrary $shared, private readonly SharedLibraryBrowse $browse) {}

    public function index(SharedLibraryRequest $request): View
    {
        $access = app(SharedLibraryAccess::class);
        $actor = $access->member($request->user());
        $teacher = $access->isTeacher($actor);
        $open = $this->browse->open();

        return view('learning.shared-library.index', [
            'settings' => $this->shared->settings(),
            'teacher' => $teacher,
            'leader' => $access->isLeader($actor),
            'catalogue' => $teacher && $open ? $this->browse->catalogue($actor, $request->validated()) : null,
            'chosen' => $request->validated(),
            'mine' => $teacher ? $this->shared->mine($actor) : [],
            'school' => $this->shared->school($actor),
            'licence' => $this->shared->licence(),
        ]);
    }

    public function item(SharedLibraryRequest $request, int $item): View
    {
        $shared = $this->browse->item($request->user(), $item);
        $groups = app(PracticeChoices::class)->access($request->user())['staff_groups'];
        $asked = $request->validated('group');
        $group = $asked === null ? null : collect($groups)->firstWhere('id', (int) $asked);
        abort_if($asked !== null && $group === null, 404);

        return view('learning.shared-library.item', ['item' => $shared, 'groups' => $groups, 'group' => $group,
            'choices' => $group === null ? null : app(PracticeChoices::class)->staff($request->user(), (int) $group['id']), 'operationId' => (string) Str::uuid()]);
    }

    public function rate(SharedLibraryRequest $request, int $item): RedirectResponse
    {
        return $this->done(fn () => $this->browse->rate($request->user(), $item, $request->validated()), __('Thank you. Your rating is saved.'));
    }

    public function copy(SharedLibraryRequest $request, int $item): RedirectResponse
    {
        return $this->done(fn () => $this->browse->copy($request->user(), $item, $request->validated()),
            __('Copied into your question bank and approved for the group. It is now your school\'s own question.'));
    }

    public function share(SharedLibraryRequest $request, int $item): View
    {
        return view('learning.shared-library.share', ['options' => $this->shared->options($request->user(), $item), 'credits' => LibraryContribution::CREDITS]);
    }

    public function contribute(SharedLibraryRequest $request): RedirectResponse
    {
        try {
            $contribution = $this->shared->contribute($request->user(), $request->validated());
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return back()->withInput()->withErrors(['question' => $error->getMessage()]);
        }

        return redirect()->route('shared-library.index')->with('success', $contribution->state === 'awaiting_approval'
            ? __('Thank you. A school leader will look at your question before it goes to Navigate Ed for review.')
            : __('Thank you. Your question went to Navigate Ed for review. Other schools see it only once a reviewer approves it.'));
    }

    /** What a contribution would share, exactly as submitted, for the people who decide it. */
    public function contribution(SharedLibraryRequest $request, int $contribution): View
    {
        return view('learning.shared-library.contribution', ['row' => $this->shared->preview($request->user(), $contribution)]);
    }

    public function approve(SharedLibraryRequest $request, int $contribution): RedirectResponse
    {
        return $this->done(fn () => $this->shared->approve($request->user(), $contribution), __('Approved. The question went to Navigate Ed for review.'));
    }

    public function decline(SharedLibraryRequest $request, int $contribution): RedirectResponse
    {
        return $this->done(fn () => $this->shared->decline($request->user(), $contribution, $request->validated()), __('Not approved. The teacher sees your reason.'));
    }

    public function withdraw(SharedLibraryRequest $request, int $contribution): RedirectResponse
    {
        return $this->done(fn () => $this->shared->withdraw($request->user(), $contribution), __('Withdrawn. No other school is offered it any more.'));
    }

    public function settings(SharedLibraryRequest $request): RedirectResponse
    {
        $this->shared->saveSettings($request->user(), $request->validated());

        return redirect()->route('shared-library.index', ['view' => 'configuration'])->with('success', __('Sharing settings saved.'));
    }

    /** A rule the person can act on (409) goes back to the page; 403 and 404 stay errors. */
    private function done(callable $write, string $message): RedirectResponse
    {
        try {
            $write();
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return back()->withErrors(['shared' => $error->getMessage()]);
        }

        return back()->with('success', $message);
    }
}
