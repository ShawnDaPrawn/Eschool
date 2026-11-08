<?php

declare(strict_types=1);

/*
 * School offboarding (Action Plan SA.11). engineering/specs/platform/offboarding.md.
 */
return [
    // The largest export archive built in one piece; a larger school's export fails with too_large.
    'maximum_export_bytes' => (int) env('OFFBOARDING_MAXIMUM_EXPORT_BYTES', 256 * 1024 * 1024),
    // How long one signed download link lasts once the Super Admin's page shows it.
    'download_link_minutes' => 15,
    // The fewest days the school has to download and confirm the export.
    'minimum_receipt_days' => 7,
    // Retention when the contract records no deletion term and HQ enters none:
    // the school's data is deleted this many days after its deadline to collect
    // the export. PLACEHOLDER: the period is the owner's decision.
    'default_retention_days' => (int) env('OFFBOARDING_DEFAULT_RETENTION_DAYS', 30),
    // After the second approval, erasure waits at least this long (and until the
    // deletion date), so a mistake can still be stopped with a legal hold or a
    // cancellation. PLACEHOLDER: the period is the owner's decision.
    'erasure_cooling_off_hours' => (int) env('OFFBOARDING_ERASURE_COOLING_OFF_HOURS', 72),
    // Rows deleted per batch; each batch is its own transaction with its progress.
    'erasure_batch_size' => (int) env('OFFBOARDING_ERASURE_BATCH_SIZE', 500),
    // An erasure with no progress for this long is started again by the scheduler.
    'erasure_stalled_minutes' => 30,
];
