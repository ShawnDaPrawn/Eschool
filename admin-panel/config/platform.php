<?php

use App\Listeners\Academics\NotifyMarksReleased;
use App\Listeners\Academics\NotifyRegisterAmended;
use App\Listeners\Academics\QueueAssessmentRecompute;
use App\Listeners\Academics\QueueMarkRecompute;
use App\Listeners\Platform\NotifyHandleGuardians;
use App\Listeners\Platform\QueueNotificationFanout;
use App\Listeners\Platform\RefreshAppContext;

/*
|--------------------------------------------------------------------------
| Platform kernel
|--------------------------------------------------------------------------
|
| Settings for the shared services under app/Services/Platform (Build Bible
| section 3.1): queued delivery of mail and push, the outbox, the audit log
| and money. Values that differ per environment are read from .env here and
| only here; code reads them with config('platform.*').
|
*/

return [

    'deliveries' => [
        // Queue the SendMail and SendPush jobs go to (on the default connection).
        'queue' => env('NOTIFICATION_QUEUE', 'default'),

        // Attempts per message and the wait in seconds before each retry.
        'tries' => (int) env('NOTIFICATION_TRIES', 3),
        'backoff' => [60, 300, 900],
    ],

    'outbox' => [
        // Event type => listener classes. Each listener implements
        // App\Services\Platform\Events\OutboxListener and receives the stored
        // event. Listeners for one event run in one database transaction.
        'listeners' => [
            'academics.assessment_published' => [QueueAssessmentRecompute::class],
            'academics.register_changed' => [NotifyRegisterAmended::class],
            'academics.marks_released' => [NotifyMarksReleased::class],
            'academics.marks_changed' => [QueueMarkRecompute::class],
            'academics.assignment_changed' => [QueueMarkRecompute::class],
            'notification.requested' => [QueueNotificationFanout::class],
            'handle.proposed' => [NotifyHandleGuardians::class],
            'context.changed' => [RefreshAppContext::class],
            // 'fees.payment_recorded' => [App\Listeners\Money\UpdateLedger::class],
        ],

        // Events handled per run of the DispatchOutbox job.
        'batch' => (int) env('OUTBOX_BATCH', 100),

        // After this many failed runs an event is parked (left undispatched and
        // logged) until someone looks at it.
        'max_attempts' => (int) env('OUTBOX_MAX_ATTEMPTS', 10),

        // Seconds to wait before retrying an event, by attempt; the last value
        // repeats.
        'backoff' => [60, 300, 900, 3600],
    ],

    'audit' => [
        // Attribute names never written to audit_log, on top of each model's
        // $hidden list.
        'never_store' => [
            'password',
            'remember_token',
            'api_token',
            'fcm_id',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],
    ],

    'money' => [
        // ISO 4217 code for amounts stored without a currency (every fee
        // column in Phase 0; per-school currency arrives with Phase 1).
        'currency' => env('MONEY_CURRENCY', 'ZAR'),
    ],

];
