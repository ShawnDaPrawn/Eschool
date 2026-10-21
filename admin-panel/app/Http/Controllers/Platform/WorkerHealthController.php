<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\QueueHealth;
use Illuminate\Http\JsonResponse;

/**
 * GET /up/worker, for the worker's uptime check. The worker has no public
 * address, so its health is judged by the queue it empties: 503 once a due
 * job has waited longer than queue.worker_health.max_wait_seconds.
 */
class WorkerHealthController extends Controller
{
    public function __invoke(QueueHealth $health): JsonResponse
    {
        $waited = $health->oldestWaitSeconds();
        $healthy = $waited === null || $waited <= $health->maxWaitSeconds();

        return response()->json([
            'status' => $waited === null ? 'no-queue' : ($healthy ? 'ok' : 'stalled'),
            'oldest_wait_seconds' => $waited,
        ], $healthy ? 200 : 503, ['Cache-Control' => 'no-store']);
    }
}
