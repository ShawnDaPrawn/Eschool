<?php

use App\Listeners\Academics\NotifyContentPublished;
use App\Listeners\Academics\NotifyMarksReleased;
use App\Listeners\Academics\NotifyRegisterAmended;
use App\Listeners\Academics\NotifyReportPublished;
use App\Listeners\Academics\QueueAssessmentRecompute;
use App\Listeners\Academics\QueueContentConversion;
use App\Listeners\Academics\QueueMarkRecompute;
use App\Listeners\Academics\QueueReportGeneration;
use App\Listeners\Consultations\NotifyConsultationOpened;
use App\Listeners\Discipline\QueueDisciplineNotices;
use App\Listeners\Growth\QueueMasteryRecompute;
use App\Listeners\Growth\QueueProgressBands;
use App\Listeners\Growth\QueueRiskEvaluate;
use App\Listeners\Learning\QueueTaskInsights;
use App\Listeners\Live\QueueLiveInvalidation;
use App\Listeners\Platform\NotifyHandleGuardians;
use App\Listeners\Platform\QueueNotificationFanout;
use App\Listeners\Platform\RefreshAppContext;
use App\Listeners\Wellbeing\QueuePrivateNotice;

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
            'house_points.changed' => [QueueLiveInvalidation::class],
            'recognition.changed' => [QueueLiveInvalidation::class],
            'consultations.counts_changed' => [QueueLiveInvalidation::class],
            'activity.group_changed' => [QueueLiveInvalidation::class],
            'academics.content_published' => [NotifyContentPublished::class],
            'academics.content_uploaded' => [QueueContentConversion::class],
            'academics.report_published' => [NotifyReportPublished::class],
            'academics.report_approved' => [QueueReportGeneration::class],
            'academics.assessment_published' => [QueueAssessmentRecompute::class, QueueMasteryRecompute::class, QueueProgressBands::class, QueueRiskEvaluate::class],
            'academics.register_changed' => [QueueRiskEvaluate::class, NotifyRegisterAmended::class],
            'growth.risk_rules_configured' => [QueueRiskEvaluate::class],
            'discipline.framework_published' => [QueueRiskEvaluate::class],
            'discipline.record_changed' => [QueueRiskEvaluate::class, QueueDisciplineNotices::class],
            'discipline.detention_changed' => [QueueDisciplineNotices::class],
            'growth.progress_bands_configured' => [QueueProgressBands::class],
            'academics.marks_released' => [NotifyMarksReleased::class, QueueMasteryRecompute::class, QueueProgressBands::class, QueueRiskEvaluate::class],
            'academics.marks_changed' => [QueueTaskInsights::class, QueueMarkRecompute::class, QueueMasteryRecompute::class, QueueProgressBands::class, QueueRiskEvaluate::class],
            'academics.assignment_changed' => [QueueTaskInsights::class, QueueMarkRecompute::class, QueueMasteryRecompute::class, QueueProgressBands::class, QueueRiskEvaluate::class],
            'wellbeing.notice_requested' => [QueuePrivateNotice::class],
            'consultations.opened' => [NotifyConsultationOpened::class],
            'notification.requested' => [QueueNotificationFanout::class],
            'handle.proposed' => [NotifyHandleGuardians::class],
            'context.changed' => [RefreshAppContext::class, QueueMasteryRecompute::class, QueueProgressBands::class, QueueRiskEvaluate::class],
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
