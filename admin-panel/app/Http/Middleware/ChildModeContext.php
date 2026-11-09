<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Services\ChildMode\ChildModeAccess;
use App\Services\ChildMode\ChildModeSessions;
use App\Services\Platform\Identity\ExpectedAccount;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The school and child for the child mode endpoints (Action Plan 4.2), after
 * auth:sanctum: only a child token, only the school and child it was issued
 * for, and only while its session is still allowed (ChildModeSessions::
 * verify re-reads the guardian link, approval, PIN, stage and the parent-app
 * sign-in on every request). An X-School-Id naming another school is
 * refused, never followed. A child token authenticates nowhere else
 * (ChildModeAccess::allowedHere), so this is the only way in.
 */
class ChildModeContext
{
    public const SESSION = 'child_mode.session';

    public function handle(Request $request, Closure $next): Response
    {
        $scope = app(CurrentSchool::class);
        abort_if($scope->isAcrossSchools() || $scope->isImpersonating(), 403);
        $binding = app(ChildModeAccess::class)->binding($request);
        abort_if($binding === null, 403, __('child_mode_token_only'));
        ExpectedAccount::check($request);
        abort_if($request->hasHeader('X-School-Id') && $request->header('X-School-Id') !== (string) $binding['school'], 403);
        $school = School::active()->find($binding['school']);
        abort_if($school === null, 401, __('child_mode_ended'));

        return $scope->run($school, function () use ($request, $next, $binding) {
            $request->attributes->set(self::SESSION, app(ChildModeSessions::class)->verify($request, $binding['learner']));

            return $next($request);
        });
    }
}
