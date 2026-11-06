<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Leadership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leadership\BenchmarkingRequest;
use App\Services\Leadership\Benchmarking\SchoolBenchmarks;
use Illuminate\Http\RedirectResponse;

/** A school's choice to take part in anonymous benchmarking (Action Plan 5.5). */
class BenchmarkingController extends Controller
{
    public function update(BenchmarkingRequest $r): RedirectResponse
    {
        $participate = $r->boolean('participate');
        app(SchoolBenchmarks::class)->choose($r->user(), $participate);

        return redirect()->to(route('leadership-dashboard.index').'#benchmarks')
            ->with('success', $participate ? __('Your school takes part in benchmarking. Its first comparison appears after the next weekly update.') : __('Your school no longer takes part in benchmarking.'));
    }
}
