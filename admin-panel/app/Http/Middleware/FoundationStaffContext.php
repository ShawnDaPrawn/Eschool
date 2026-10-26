<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Cards\CardAccess;
use App\Services\Classroom\ClassroomAccess;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FoundationStaffContext
{
    public function manager(User $actor): bool
    {
        try {
            app(ClassroomAccess::class)->manager($actor, false);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function available(User $actor): bool
    {
        try {
            $fresh = app(CardAccess::class)->actor($actor);

            return $this->manager($fresh) || in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $this->available($actor), 403);
        $action = (string) $request->route('foundation_action');
        if (! $request->isMethodSafe()) {
            abort_if($request->query->count() > 0, 422, 'Use the private request body for classroom changes.');
            $panel = str_starts_with((string) $request->route()->getName(), 'foundation-panel.');
            if ($panel) {
                $account = $request->input('expected_account_id');
                $school = $request->input('expected_school_id');
                abort_unless((is_int($account) || is_string($account)) && (is_int($school) || is_string($school))
                    && (string) $account === (string) $actor->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
                if (! in_array($action, ['revoke', 'device-revoke'], true)) {
                    abort_if(app(SessionYearContext::class)->isReadOnly(), 423, 'Switch to the current school year before authorizing a classroom.');
                }
            }
            if (in_array($action, ['eligibility', 'register'], true)) {
                app(ClassroomAccess::class)->manager($actor);
            }
        }

        return $next($request);
    }
}
