<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class TransferPanelScope
{
    public function authorize(Request $r): bool
    {
        $a = app(TransferAuthority::class);
        $actor = $a->staff($r->user(), ! $r->isMethodSafe());
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $actor->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($r->query->count() > 0, 422, 'Submit transfer changes in the request body.');
            if ($r->route('package') !== null) {
                $r->routeIs('school-transfers.accept') ? $a->incoming($actor, (int) $r->route('package'), true) : $a->outgoing($actor, (int) $r->route('package'), true);
            }
        }

        return true;
    }
}
