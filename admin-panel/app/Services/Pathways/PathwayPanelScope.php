<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PathwayPanelScope
{
    public function available(User $actor): bool
    {
        try {
            app(PathwayAccess::class)->manager($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function authorize(Request $request): bool
    {
        $fresh = app(PathwayAccess::class)->manager($request->user(), ! $request->isMethodSafe());
        if (! $request->isMethodSafe()) {
            $account = $request->input('expected_account_id');
            $school = $request->input('expected_school_id');
            abort_unless((is_string($account) || is_int($account)) && (is_string($school) || is_int($school))
                && (string) $account === (string) $fresh->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
            abort_if($request->query->count() > 0 || $request->allFiles() !== [], 422);
        }

        return true;
    }
}
