<?php

namespace App\Http\Middleware;

use App\Models\StudentSessions;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\People\Leavers\LeaverState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentSessionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Student APIs are for active student accounts only.
        if (! $user || ! $user->hasRole('Student') || (int) $user->status !== 1 || ! $user->student) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
                'data' => null,
                'code' => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        if (! app(LearnerAcademicAccess::class)->allows($user->student)) {
            return response()->json(['message' => 'Your Account is Deactivate Please contact Admin for Further Help.', 'code' => 'SESSION_NOT_ACTIVE'], Response::HTTP_UNAUTHORIZED);
        }

        $session_year_id = getSettings('session_year')['session_year'];
        $studentSession = StudentSessions::where('session_year_id', $session_year_id)
            ->where('student_id', $user->student->id)->first();

        if (! $studentSession) {
            return response()->json([
                'message' => 'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                'code' => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($studentSession->status == 0) {
            // A leaver (Action Plan 1.21) reads their own profile and history,
            // read-only, until leaver_until; pages about this year's class and
            // every write are refused.
            if (app(LeaverState::class)->until($user->student) !== null) {
                if ($request->is(...(array) config('leavers.student_api', [])) && ! LeaverState::writing()) {
                    return $next($request);
                }

                return response()->json([
                    'error' => true,
                    'message' => trans('leaver_read_only'),
                    'data' => null,
                    'code' => Response::HTTP_FORBIDDEN,
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'message' => 'Your Account is Deactivate Please contact Admin for Further Help.',
                'code' => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
