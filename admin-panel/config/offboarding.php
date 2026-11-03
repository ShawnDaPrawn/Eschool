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
];
