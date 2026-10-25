<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\SupportDisclosure;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class SupportPanelScope
{
    public function authorize(Request $request): bool
    {
        $operation = substr((string) $request->route()->getName(), strlen('support-plans.'));
        $a = app(SupportAuthority::class);
        $actor = $a->actor($request->user(), ! $request->isMethodSafe());
        $audiences = $a->audiences($actor);
        abort_unless($audiences !== [], 403);
        if ($request->isMethodSafe()) {
            abort_unless(in_array($request->input('audience', $audiences[0]), $audiences, true), 403);

            return true;
        }
        // A private draft opened by another account cannot be submitted under
        // replacement browser cookies, even when both people share a caseload.
        abort_unless((int) $request->input('expected_account_id') === (int) $actor->id && (int) $request->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        if (in_array($operation, ['assign', 'revoke-assignment'], true)) {
            $a->principal($actor, true);
        } else {
            $a->professional($actor, true);
            if ($request->route('plan') !== null) {
                $a->plan($actor, (int) $request->route('plan'), true);
            }
            if ($operation === 'revoke-disclosure') {
                $row = SupportDisclosure::select('id', 'support_plan_id')->findOrFail((int) $request->route('disclosure'));
                $a->plan($actor, (int) $row->support_plan_id, true);
            }
        }

        return true;
    }
}
