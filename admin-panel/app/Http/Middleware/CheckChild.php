<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\GuardianLink;
use App\Services\People\Leavers\LeaverState;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckChild
{
    /**
     * Only lets a parent through for one of their own children. The child is
     * stored on the request as the 'child' attribute for the controller.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $parent = $request->user()?->parent;
        $childId = filter_var($request->child_id, FILTER_VALIDATE_INT);

        $child = ($parent && $childId) ? $parent->children()->whereKey($childId)->first() : null;
        if (empty($child)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid Child ID Passed.',
                'code' => 105,
            ]);
        }
        // A leaver's records stay readable; nothing is written for them (Action Plan 1.21).
        app(LeaverState::class)->guardFamilyWrite($child);
        if ($request->is('api/parent/*fees*', 'api/parent/*payment*', 'api/parent/*transaction*')) {
            $link = GuardianLink::query()->where('learner_id', $child->id)
                ->where('guardian_user_id', $request->user()->id)->first();
            abort_if($link !== null && ! $link->can_see_fees, 403);
        }
        $request->attributes->set('child', $child);

        return $next($request);
    }
}
