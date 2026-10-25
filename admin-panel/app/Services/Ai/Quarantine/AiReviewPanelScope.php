<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class AiReviewPanelScope
{
    public function authorize(Request $request): bool
    {
        app(AiReviewAuthority::class)->actor($request->user());
        if (! $request->isMethodSafe()) {
            abort_unless($request->integer('expected_school_id') === app(CurrentSchool::class)->id()
                && $request->integer('expected_account_id') === (int) $request->user()->id, 403);
        }

        return true;
    }
}
