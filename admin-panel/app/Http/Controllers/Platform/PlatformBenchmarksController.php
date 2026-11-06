<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Leadership\Benchmarking\PlatformBenchmarks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** HQ: the published benchmark medians and quartiles (Action Plan 5.5). Read-only, names no school. */
class PlatformBenchmarksController extends Controller
{
    public function index(Request $request, PlatformBenchmarks $benchmarks): View
    {
        return view('saas.benchmarks', $benchmarks->overview($request->user()));
    }
}
