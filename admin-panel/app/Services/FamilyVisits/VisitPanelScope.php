<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VisitPanelScope
{
    public function available(User $actor): bool
    {
        try {
            $fresh = app(VisitAccess::class)->actor($actor);

            return array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function authorize(Request $request): bool
    {
        $revocation = in_array($request->route('operation'), ['meeting-cancel', 'pass-cancel'], true);
        $fresh = app(VisitAccess::class)->actor($request->user(), ! $request->isMethodSafe(), $revocation);
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [], 403);
        if (! $request->isMethodSafe()) {
            $account = $request->input('expected_account_id');
            $school = $request->input('expected_school_id');
            abort_unless((is_string($account) || is_int($account)) && (is_string($school) || is_int($school))
                && (string) $account === (string) $fresh->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
            abort_if($request->query->count() > 0, 422);
            app(VisitAccess::class)->manager($fresh, true, $revocation);
        }

        return true;
    }
}
