<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformLuritsConflicts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: LURITS claims waiting for support (Action Plan 1.0f). */
class PlatformLuritsController extends Controller
{
    public function __construct(private readonly PlatformLuritsConflicts $conflicts) {}

    public function index(): View
    {
        return view('saas.lurits', ['open' => $this->conflicts->list(true), 'closed' => $this->conflicts->list(false)]);
    }

    public function resolve(Request $request, int $conflict): RedirectResponse
    {
        $this->conflicts->resolve($conflict, $request->only('outcome'), $request->user());

        return redirect()->route('saas.lurits')->with('success', __('Claim closed.'));
    }
}
