<?php

namespace App\Http\Middleware;

use App\Models\StudentSessions;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\People\Leavers\LeaverState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentChildrenSessionIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->parent) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ], Response::HTTP_FORBIDDEN);
        }
        $session_year_id = getSettings('session_year')['session_year'];

        // Fresh GuardianLinks override old parent columns, including ended rights.
        $children = $user->parent->children()->get();
        $hasRetainedDeparture = $children->contains(fn ($child) => app(LearnerAcademicAccess::class)->departed($child))
            // A year-end leaver's guardians read until leaver_until (Action Plan 1.21).
            || $children->contains(fn ($child) => app(LeaverState::class)->until($child) !== null);

        $hasAnyValidSession = false;
        $hasAnyActiveChild = false;

        foreach ($children as $child) {

            $session = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $child->id)
                ->first();

            // Valid session exists
            if ($session) {
                $hasAnyValidSession = true;

                // Active / studying
                if ((int) $session->status === 1) {
                    $hasAnyActiveChild = true;
                }
            }
        }

        // No child has session for current year
        if (! $hasAnyValidSession && ! $hasRetainedDeparture) {
            return response()->json([
                'message' => 'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                'code' => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // All children are deactivated
        if (! $hasAnyActiveChild && ! $hasRetainedDeparture) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact admin for further help.',
                'code' => 'ACCOUNT_DEACTIVATED',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
