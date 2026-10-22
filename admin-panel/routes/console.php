<?php

use App\Jobs\Platform\DispatchOutbox;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs daily to purge old chat messages and keep the table size manageable.
Schedule::command('app:cleanup-old-chat-messages')->daily()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// The schedule lives only here; Cloud Scheduler runs schedule:run every
// minute. Queued jobs run on the worker (php artisan queue:work).
// Domain events recorded in the outbox go to their listeners.
Schedule::job(new DispatchOutbox)->everyMinute()->name('outbox-dispatch');
