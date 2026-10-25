<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class IntakePanelScope
{
    public function authorize(Request $r): bool
    {
        $actor = app(IntakeAuthority::class)->staff($r->user(), ! $r->isMethodSafe());
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $actor->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($r->query->count() === 0, 422);
        }
        if ($r->route('application') !== null) {
            app(IntakeAuthority::class)->application($actor, (int) $r->route('application'), ! $r->isMethodSafe());
        }

        return true;
    }
}
