<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class CardEncodingPanelScope
{
    public function authorize(Request $request): bool
    {
        $actor = $request->user();
        $account = $request->input('expected_account_id');
        $school = $request->input('expected_school_id');
        abort_unless((is_int($account) || is_string($account)) && (is_int($school) || is_string($school))
            && (string) $account === (string) $actor->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
        app(CardAccess::class)->encode($actor, ! $request->isMethodSafe());
        abort_if(! $request->isMethodSafe() && $request->query->count() > 0, 422, 'Send chip preparation fields in the private form.');

        return true;
    }
}
