<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SupportAccessRequest;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\SupportAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: audited support access to a school (Action Plan SA.9). */
class PlatformSupportAccessController extends Controller
{
    public function __construct(private readonly SupportAccess $support) {}

    public function index(Request $request): View
    {
        return view('saas.support', $this->support->hqList($request->user()) + [
            'schoolChoices' => $this->support->schoolChoices(),
            'canOverride' => app(PlatformStaffAccess::class)->can($request->user(), 'support.emergency'),
            'me' => (int) $request->user()->id,
        ]);
    }

    public function store(SupportAccessRequest $request): RedirectResponse
    {
        $grant = $this->support->request($request->user(), $request->validated());

        return redirect()->route('saas.support.show', $grant->id)->with('success', __('Request sent. The school\'s Super Admin has been told.'));
    }

    public function show(Request $request, int $grant): View
    {
        return view('saas.support-grant', $this->support->hqShow($request->user(), $grant, (int) $request->query('page', 1)) + [
            'canOverride' => app(PlatformStaffAccess::class)->can($request->user(), 'support.emergency'),
            'me' => (int) $request->user()->id,
        ]);
    }

    public function emergency(SupportAccessRequest $request, int $grant): RedirectResponse
    {
        $this->support->emergency($request->user(), $grant, $request->validated());

        return redirect()->route('saas.support.show', $grant)->with('success', __('Emergency access is open. The school\'s Super Admin has been told.'));
    }

    public function end(SupportAccessRequest $request, int $grant): RedirectResponse
    {
        $this->support->endByStaff($request->user(), $grant);

        return redirect()->route('saas.support.show', $grant)->with('success', __('Support access ended.'));
    }
}
