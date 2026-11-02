<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SupportAccessDecisionRequest;
use App\Services\Platform\Saas\SupportAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The school's side of Navigate Ed support access (SA.9): decide, end, and see what was viewed. Super Admin only. */
class SchoolSupportAccessController extends Controller
{
    public function __construct(private readonly SupportAccess $support) {}

    public function index(Request $request): Response
    {
        return response()->view('platform.support-access', $this->support->schoolList($request->user()))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, int $grant): Response
    {
        return response()->view('platform.support-access-grant', $this->support->schoolShow($request->user(), $grant, (int) $request->query('page', 1)))
            ->header('Cache-Control', 'private, no-store');
    }

    public function approve(SupportAccessDecisionRequest $request, int $grant): RedirectResponse
    {
        $this->support->approve($request->user(), $grant);

        return redirect()->route('support-access.index')->with('success', __('Support access approved. It ends by itself at the time shown.'));
    }

    public function decline(SupportAccessDecisionRequest $request, int $grant): RedirectResponse
    {
        $this->support->decline($request->user(), $grant);

        return redirect()->route('support-access.index')->with('success', __('Support access declined.'));
    }

    public function end(SupportAccessDecisionRequest $request, int $grant): RedirectResponse
    {
        $this->support->endBySchool($request->user(), $grant);

        return redirect()->route('support-access.index')->with('success', __('Support access ended.'));
    }
}
