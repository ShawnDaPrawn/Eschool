<?php

declare(strict_types=1);

return [
    // Independent deployment configuration; school modules remain separately scoped.
    'enabled' => (bool) env('LIVE_UPDATES_ENABLED', false),
    'client' => [
        'host' => env('REVERB_PUBLIC_HOST', env('REVERB_HOST', 'localhost')),
        'port' => (int) env('REVERB_PUBLIC_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('REVERB_PUBLIC_SCHEME', 'wss'),
    ],
    'heartbeat_seconds' => 20,
    'presence_seconds' => 45,
];
