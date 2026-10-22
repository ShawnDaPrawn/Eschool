<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\WorkerHealthController;
use Illuminate\Support\Facades\Route;

/*
| Health checks for the uptime checks (engineering/runbooks/environments.md).
| /up itself is the framework's (bootstrap/app.php). These routes sit outside
| the web and api groups: no session, no cookies, no installer redirect.
*/

Route::get('up/worker', WorkerHealthController::class)
    ->middleware('throttle:60,1')
    ->name('health.worker');
