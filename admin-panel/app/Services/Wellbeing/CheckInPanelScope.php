<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use Illuminate\Http\Request;

/** Runs before a historical-year refusal is shown on a check-in write: the same authority as the write itself. */
class CheckInPanelScope
{
    public function authorize(Request $r): bool
    {
        abort_if(! $r->isMethodSafe() && $r->query->count() > 0, 422, 'Private changes must be sent in the request body.');
        $checkIns = app(CheckIns::class);
        $checkIns->professional($r->user());
        $id = $r->route('checkIn');
        if (is_string($id) && ctype_digit($id)) {
            $checkIns->authorized($r->user(), (int) $id);
        }

        return true;
    }
}
