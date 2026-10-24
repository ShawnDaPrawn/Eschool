<?php

use App\Console\Commands\FlagDobPasswords;
use App\Jobs\Academics\RecoverContentConversions;
use App\Jobs\Communication\RecoverMessages;
use App\Jobs\Platform\ApplyRetention;
use App\Jobs\Platform\DispatchOutbox;
use App\Models\People\Enrolment;
use App\Models\Platform\IdempotencyKey;
use App\Models\Platform\School;
use App\Models\Settings;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Runs daily to purge old chat messages and keep the table size manageable.
Schedule::command('app:cleanup-old-chat-messages')->daily()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
// API v2 Idempotency-Key records older than 24 hours.
Schedule::command('model:prune', ['--model' => [IdempotencyKey::class]])->daily();

// The schedule lives only here; Cloud Scheduler runs schedule:run every
// minute. Queued jobs run on the worker (php artisan queue:work).
// Domain events recorded in the outbox go to their listeners.
Schedule::job(new DispatchOutbox)->everyMinute()->name('outbox-dispatch');

// Fills *_cents for fee rows written outside the models and logs any row
// whose cents and float disagree ("money_cents_check").
Schedule::command('money:backfill-cents')->dailyAt('02:10')->withoutOverlapping();

// Learners whose password is still their date of birth are flagged once,
// on the worker, the first night after the release that added the check
// (the worker and the scheduler then run the same release). The command
// records when it has run, so the entry stops queueing it.
Schedule::call(function () {
    // A statement, not an arrow function: the pending dispatch must not be
    // returned (and kept) but released here, which is when it is queued.
    Artisan::queue('learners:flag-dob-passwords', ['--if-not-done' => true]);
})
    ->dailyAt('03:20')
    ->name('learners-flag-dob-passwords')
    ->when(fn () => ! FlagDobPasswords::completed());

// Invitation links are private and reminders use encrypted queued mail.
Schedule::call(function () {
    School::query()->active()->each(function ($school) {
        app(CurrentSchool::class)->run((int) $school->id, fn () => app(GuardianInvitations::class)->remind());
    });
})->dailyAt('08:00')->name('guardian-invitation-reminders')->withoutOverlapping();

// One daily refresh per school's timezone, triggered every minute so a
// school's midnight does not depend on the scheduler's own timezone.
Schedule::call(function () {
    School::query()->active()->each(function ($school) {
        app(CurrentSchool::class)->run((int) $school->id, function () use ($school) {
            $today = now($school->timezone)->toDateString();
            if (schoolSetting('enrolment_projection_date') === $today) {
                return;
            }
            Enrolment::query()->select(['learner_id', 'session_year_id'])->distinct()->get()
                ->each(fn ($entry) => app(EnrolmentService::class)->syncProjection((int) $entry->learner_id, (int) $entry->session_year_id));
            Settings::query()->updateOrCreate(['type' => 'enrolment_projection_date'], ['message' => $today]);
        });
    });
})->everyMinute()->name('enrolment-projections')->withoutOverlapping();

// Canonical dates remain authoritative even if this refresh is delayed.
Schedule::command('academics:refresh-allocations')->everyMinute()->name('academic-allocation-projections')->withoutOverlapping();

Schedule::command('academics:register-absence-alerts')->everyMinute()->name('register-absence-alerts')->withoutOverlapping();

// Expire private academic exit copies; canonical records remain retained.
Schedule::job(new ApplyRetention)->dailyAt('02:40')->name('academic-export-retention')->withoutOverlapping();

Schedule::job(new RecoverContentConversions)->everyMinute()->name('content-conversion-recovery')->withoutOverlapping();

Schedule::job(new RecoverMessages)->everyMinute()->name('communication-message-recovery')->withoutOverlapping();
