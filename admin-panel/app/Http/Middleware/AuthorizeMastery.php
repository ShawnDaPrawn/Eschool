<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Subject;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Academics\AcademicAccess;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Fresh authority precedes both validation and idempotent replay. */
class AuthorizeMastery
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery'), 403);
        if ($operation === 'family') {
            app(MasteryPolicy::class)->family($request->user(), (int) $request->route('learner'));
        } elseif ($operation === 'manage') {
            abort_unless($gate->writable('mastery'), 403);
            app(AcademicAccess::class)->actor($request->user(), 'curriculum-publish');
            Subject::query()->findOrFail((int) $request->route('subject'));
        } elseif ($operation === 'configuration' || $operation === 'graph') {
            app(AcademicAuthority::class)->staff($request->user());
        } else {
            app(MasteryPolicy::class)->staff($request->user(), (int) $request->route('group'), $request->route('learner') === null ? null : (int) $request->route('learner'));
            if ($operation === 'rebuild') {
                abort_unless($gate->writable('mastery'), 403);
            }
        }

        return $next($request);
    }
}
