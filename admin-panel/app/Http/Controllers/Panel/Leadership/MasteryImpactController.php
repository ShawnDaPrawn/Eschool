<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Leadership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leadership\MasteryImpactRequest;
use App\Services\Leadership\MasteryImpact;
use Illuminate\Http\Response;

class MasteryImpactController extends Controller
{
    public function index(MasteryImpactRequest $r): Response
    {
        $term = $r->validated('term_id');
        $report = app(MasteryImpact::class)->read($r->user(), $term === null ? null : (int) $term, $r->validated('policy_version'));

        return response()->view('leadership.mastery-impact', ['report' => $report])->header('Cache-Control', 'private, no-store');
    }
}
