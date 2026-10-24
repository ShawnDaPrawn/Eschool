<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Academics\CurriculumPlan;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\AcademicAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Context authority must run before an idempotent response is replayed. */
class AuthorizeCurriculum
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $policy = app(CurriculumPolicy::class);
        $policy->authorise($request->user(), $operation);
        if ($request->route('plan') !== null) {
            $plan = CurriculumPlan::query()->findOrFail($request->route('plan'));
            $policy->authorise($request->user(), $operation, $plan);
            if ($operation === 'copy' && filter_var($request->input('session_year_id'), FILTER_VALIDATE_INT) > 0) {
                app(AcademicAccess::class)->context($request->user(), 'curriculum-manage', (int) $plan->subject_id,
                    (int) $plan->grade_level_id, (int) $request->input('session_year_id'));
            }
        } elseif (! $request->isMethodSafe()) {
            $ids = array_map(fn ($key) => filter_var($request->input($key), FILTER_VALIDATE_INT), ['subject_id', 'grade_level_id', 'session_year_id']);
            if (min($ids) > 0) {
                app(AcademicAccess::class)->context($request->user(), 'curriculum-manage', ...$ids);
            }
        }

        return $next($request);
    }
}
