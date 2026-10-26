<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Resolve office identity and any claimed source before reflecting private input. */
class AssistancePanelScope
{
    public function authorize(Request $request): bool
    {
        $access = app(AssistanceAccess::class);
        $actor = $access->office($request->user(), ! $request->isMethodSafe());
        if (! $request->isMethodSafe()) {
            abort_unless((string) $request->input('expected_account_id') === (string) $actor->id
                && (string) $request->input('expected_school_id') === (string) app(CurrentSchool::class)->id(), 403);
        }
        $operation = (string) $request->route('operation');
        if ($request->route('thread') !== null && $operation !== 'claim') {
            $access->thread($actor, (string) $request->route('thread'), false, true);
        }
        $turnId = $request->route('turn') ?? ($request->isMethodSafe() ? $request->query('turn') : null);
        if ($turnId !== null) {
            abort_unless(is_string($turnId) && Str::isUuid($turnId), 404);
            $turn = app(AssistanceInference::class)->authorize($actor, $turnId);
            abort_unless($turn->office, 404);
        }

        return true;
    }
}
