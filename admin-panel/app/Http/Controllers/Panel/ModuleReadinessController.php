<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ModuleRequest;
use App\Services\Panel\ModuleReadiness;
use App\Services\Panel\SessionYearContext;

class ModuleReadinessController extends Controller
{
    public function __invoke(ModuleRequest $request, ModuleReadiness $readiness)
    {
        return response()->view('platform.module-readiness', ['modules' => $readiness->forUser($request->user()), 'year' => app(SessionYearContext::class)->selected()])->header('Cache-Control', 'no-store, private');
    }
}
