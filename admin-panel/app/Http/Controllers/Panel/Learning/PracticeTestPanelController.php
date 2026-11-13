<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\PracticeTestPanelRequest;
use App\Services\Learning\PracticeChoices;
use App\Services\Learning\Tests\TestBlueprints;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** /practice-tests: teachers set cycle tests for their groups and see how each learner is getting on (Action Plan 3.17). */
class PracticeTestPanelController extends Controller
{
    public function __construct(private readonly TestBlueprints $blueprints) {}

    public function index(PracticeTestPanelRequest $request): View
    {
        $groups = $this->groups($request);
        $asked = $request->validated('group');
        $groupId = $asked === null ? ($groups[0]['id'] ?? null) : (int) $asked;
        // Another school's group, or one this teacher does not teach today, is not found.
        abort_if($groupId !== null && ! in_array($groupId, array_column($groups, 'id'), true), 404);

        return view('learning.practice-tests.index', ['groups' => $groups, 'groupId' => $groupId,
            'listing' => $groupId === null ? null : $this->blueprints->index($request->user(), $groupId, (int) $request->validated('page', 1)),
            'options' => $groupId === null ? null : $this->blueprints->options($request->user(), $groupId),
            'operationId' => (string) Str::uuid(), 'zone' => app(CurrentSchool::class)->school()->timezone, 'workspaceView' => $request->validated('view', 'tests')]);
    }

    public function create(PracticeTestPanelRequest $request, int $group): RedirectResponse
    {
        return $this->done(fn () => $this->blueprints->create($request->user(), $group, $request->validated()), $group,
            __('Cycle test set. Learners in the group can start it when it opens.'), true);
    }

    public function show(PracticeTestPanelRequest $request, int $group, int $blueprint): View
    {
        return view('learning.practice-tests.show', ['results' => $this->blueprints->results($request->user(), $group, $blueprint),
            'operationId' => (string) Str::uuid(), 'zone' => app(CurrentSchool::class)->school()->timezone]);
    }

    public function withdraw(PracticeTestPanelRequest $request, int $group, int $blueprint): RedirectResponse
    {
        return $this->done(fn () => $this->blueprints->withdraw($request->user(), $group, $blueprint, $request->validated()), $group,
            __('Cycle test withdrawn. Learners can no longer start it; anyone already sitting it can still hand it in.'));
    }

    /** @return list<array{id: int, name: string, subject_name: string}> */
    private function groups(PracticeTestPanelRequest $request): array
    {
        try {
            return app(PracticeChoices::class)->access($request->user())['staff_groups'];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
    }

    /** A refusal the teacher can act on (a rule of the test) goes back to the page; 403 and 404 stay errors. */
    private function done(callable $write, int $group, string $message, bool $creating = false): RedirectResponse
    {
        try {
            $write();
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [409, 422], true)) {
                throw $error;
            }

            return redirect()->route('practice-tests.index', ['group' => $group, 'view' => $creating ? 'create' : 'tests'])->withErrors(['practice_test' => $error->getMessage()])->withInput();
        }

        return redirect()->route('practice-tests.index', ['group' => $group])->with('success', $message);
    }
}
