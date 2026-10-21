<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * What operations need to see the queue and the worker: the job's name and id
 * on every log line written while it runs, one "Queued job failed" line when a
 * job has used up its tries (the failed-job alert matches that message), and
 * the health routes for the uptime checks.
 */
class ObservabilityServiceProvider extends ServiceProvider
{
    public const JOB_FAILED_MESSAGE = 'Queued job failed';

    private const JOB_CONTEXT = ['job', 'job_id', 'queue'];

    public function boot(): void
    {
        Event::listen(function (JobProcessing $event) {
            Context::add([
                'job' => $event->job->resolveName(),
                'job_id' => $event->job->getJobId(),
                'queue' => $event->job->getQueue(),
            ]);
        });
        // The worker starts each job with fresh context; the sync queue runs the
        // job inside a request, whose later lines must not carry the job's name.
        Event::listen(fn (JobProcessed $event) => Context::forget(self::JOB_CONTEXT));

        Event::listen(function (JobFailed $event) {
            Log::error(self::JOB_FAILED_MESSAGE, [
                'job' => $event->job->resolveName(),
                'job_id' => $event->job->getJobId(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'attempts' => $event->job->attempts(),
                'exception_class' => $event->exception::class,
            ]);
            Context::forget(self::JOB_CONTEXT);
        });

        $this->loadRoutesFrom(base_path('routes/health.php'));
    }
}
