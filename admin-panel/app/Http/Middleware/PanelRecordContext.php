<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Refuse a stale personal settings form after an account or school switch. */
final class PanelRecordContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = app(CurrentSchool::class);
        abort_unless($request->user() && (int) $request->user()->status === 1
            && $school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        if (! $request->isMethodSafe()) {
            $account = $request->input('expected_account_id');
            $expectedSchool = $request->input('expected_school_id');
            abort_unless((is_string($account) || is_int($account)) && (is_string($expectedSchool) || is_int($expectedSchool))
                && (string) $account === (string) $request->user()->id
                && (string) $expectedSchool === (string) $school->id(), 409,
                'Your account or school changed. Reopen this screen.');
        }

        return $next($request);
    }
}
