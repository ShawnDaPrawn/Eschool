<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FamilyPanelScope
{
    public function available(User $user): bool
    {
        try {
            $actor = app(FamilyFormAccess::class)->actor($user);
            if (array_diff(app(ProfilePolicy::class)->roles($actor), ['Parent', 'Student']) === []) {
                return false;
            }

            return app(FamilyFormQueries::class)->configuration($actor)['groups'] !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function identifier(mixed $value): int
    {
        abort_unless((is_string($value) || is_int($value)) && preg_match('/^[1-9][0-9]*$/D', (string) $value) === 1, 422);

        return (int) $value;
    }

    public function authorize(Request $request): bool
    {
        $actor = app(FamilyFormAccess::class)->actor($request->user());
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($actor), ['Parent', 'Student']) !== [], 403);
        if (! $request->isMethodSafe()) {
            $account = $request->input('expected_account_id');
            $school = $request->input('expected_school_id');
            abort_unless((is_string($account) || is_int($account)) && (is_string($school) || is_int($school))
                && (string) $account === (string) $actor->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
            abort_if($request->query->count() > 0, 422, 'Send private family fields in the form.');
        }
        $operation = (string) $request->route('operation');
        if (in_array($operation, ['prepare', 'create'], true) || $request->filled('activity_group_id')) {
            app(CommunityAuthority::class)->groupStaff($actor, $this->identifier($request->input('activity_group_id')), in_array($operation, ['prepare', 'create'], true));
        }
        if (in_array($operation, ['managed', 'cancel'], true)) {
            app(Consents::class)->managedRequest($actor, (string) $request->route('consent'));
        }

        return true;
    }
}
