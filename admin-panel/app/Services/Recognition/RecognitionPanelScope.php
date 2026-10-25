<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class RecognitionPanelScope
{
    public function authorize(Request $r): bool
    {
        $module = (string) $r->route('module');
        $actor = app(RecognitionAuthority::class)->staff($r->user(), $module, ! $r->isMethodSafe());
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $actor->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($r->query->count() > 0, 422, 'Submit recognition changes in the request body.');
        }

        return true;
    }
}
