<?php

return [
    'maximum_source_bytes' => 25 * 1024 * 1024,
    'maximum_markdown_bytes' => 2 * 1024 * 1024,
    'maximum_groups' => 30,
    'maximum_topics' => 50,
    'notification_batch_size' => 100,
    'offline_lease_hours' => 24,
    'converter_url' => env('CONTENT_CONVERTER_URL'),
    'converter_token' => env('CONTENT_CONVERTER_TOKEN'),
    'converter_iam' => (bool) env('CONTENT_CONVERTER_IAM', false),
    'source_origin' => env('CONTENT_SOURCE_ORIGIN', env('APP_URL')),
    'allow_http' => (bool) env('CONTENT_ALLOW_HTTP', false),
    'clamav_host' => env('CONTENT_CLAMAV_HOST'),
    'clamav_port' => (int) env('CONTENT_CLAMAV_PORT', 3310),
    'clamav_timeout' => 30,
    'converter_timeout' => 270,
    'lease_seconds' => 360,
];
