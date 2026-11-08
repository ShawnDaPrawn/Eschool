<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Services\People\Pools\PoolAccess;
use App\Services\Platform\Identity\ExpectedAccount;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The school for the pool member endpoints (Action Plan 5.6), after
 * auth:sanctum: only a people-pool-app token, and only the school that
 * token was issued for. Pool accounts have no school membership, so
 * school.context would refuse them; this is the one way in, and it opens
 * nothing but the pool member routes. An X-School-Id naming another school
 * is refused, never followed.
 */
class PoolMemberContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $scope = app(CurrentSchool::class);
        abort_if($scope->isAcrossSchools() || $scope->isImpersonating(), 403);
        $schoolId = app(PoolAccess::class)->tokenSchool($request);
        abort_if($schoolId === null, 403, 'Sign in to the alumni and volunteer pool.');
        ExpectedAccount::check($request);
        abort_if($request->hasHeader('X-School-Id') && $request->header('X-School-Id') !== (string) $schoolId, 403);
        $school = School::active()->find($schoolId);
        abort_if($school === null, 403);

        return $scope->run($school, fn () => $next($request));
    }
}
