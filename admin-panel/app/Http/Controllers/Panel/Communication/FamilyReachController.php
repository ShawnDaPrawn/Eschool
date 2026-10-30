<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Communication;

use App\Http\Controllers\Controller;
use App\Services\Communication\FamilyReach;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Which learners have a guardian the school can reach (Action Plan 2.6). */
class FamilyReachController extends Controller
{
    public function __invoke(Request $request, FamilyReach $reach): Response
    {
        return response()->view('communication.family-reach', ['report' => $reach->report($request->user())])->header('Cache-Control', 'private, no-store');
    }
}
