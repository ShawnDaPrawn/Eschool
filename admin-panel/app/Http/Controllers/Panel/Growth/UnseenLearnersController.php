<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\UnseenLearnersRequest;
use App\Services\Growth\PositiveContacts;
use App\Services\Growth\UnseenLearners;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /risk-interventions/unseen: learners with no recent positive contact from
 * any adult, and their named adults (Action Plan 4.5).
 * engineering/specs/growth/risk-interventions.md.
 */
class UnseenLearnersController extends Controller
{
    public function index(UnseenLearnersRequest $request, UnseenLearners $unseen): Response
    {
        $access = $unseen->access($request->user());
        $leads = $access['principal'] || $access['grade_ids'] !== [];
        $grade = $request->validated('grade');
        $grade = $grade === null ? null : (int) $grade;

        return response()->view('growth.unseen-learners', [
            'access' => $access, 'grade' => $grade,
            'list' => $leads ? $unseen->list($request->user(), $grade, (int) $request->validated('page', 1)) : null,
            'mine' => $unseen->mine($request->user()),
            'staff' => $leads ? $unseen->staff($request->user()) : [],
            'schoolDays' => app(PositiveContacts::class)->schoolDays(),
            'canWrite' => ! app(SessionYearContext::class)->isReadOnly() && app(ModuleGate::class)->writable('early_warning'),
            'actorId' => (int) $request->user()->id, 'schoolId' => app(CurrentSchool::class)->id(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function assign(UnseenLearnersRequest $request, UnseenLearners $unseen, int $learner): RedirectResponse
    {
        $data = $request->validated();
        $back = array_filter(['grade' => $data['grade'] ?? null]);
        try {
            $unseen->assign($request->user(), $learner, $data);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 422) {
                throw $e;
            }

            return redirect()->route('risk-interventions.unseen', $back)->withErrors(['adult_user_id' => $e->getMessage()]);
        }

        return redirect()->route('risk-interventions.unseen', $back)->with('success', __('Named adult saved. They have been sent a notification.'));
    }

    public function configure(UnseenLearnersRequest $request, UnseenLearners $unseen): RedirectResponse
    {
        $days = $unseen->configure($request->user(), $request->validated());

        return redirect()->route('risk-interventions.unseen')->with('success', __('A learner is now flagged after :days school days without a positive contact.', ['days' => $days]));
    }
}
