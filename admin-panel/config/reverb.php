<?php

declare(strict_types=1);

$origins = array_values(array_unique(array_filter(array_map(
    static fn ($origin) => preg_match('/^[a-zA-Z0-9.-]+$/D', trim($origin)) === 1 ? trim($origin) : null,
    explode(',', (string) env('REVERB_ALLOWED_ORIGINS', 'app.navigateed.co.za,teach.navigateed.co.za,staging-app.navigateed.co.za,staging-teach.navigateed.co.za'))
))));
if (in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)) {
    $origins = array_values(array_unique([...$origins, 'localhost', '127.0.0.1']));
}

return [
    'default' => 'reverb',
    'servers' => ['reverb' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => (int) env('REVERB_SERVER_PORT', 8080),
        'path' => '',
        'hostname' => env('REVERB_HOST'),
        'options' => ['tls' => []],
        'max_request_size' => 10000,
        'scaling' => ['enabled' => false],
        'pulse_ingest_interval' => 15,
        'telescope_ingest_interval' => 15,
    ]],
    'apps' => ['provider' => 'config', 'apps' => [[
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', 'localhost'),
            'port' => (int) env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
        'allowed_origins' => $origins,
        'ping_interval' => 30,
        'activity_timeout' => 30,
        'max_connections' => (int) env('REVERB_MAX_CONNECTIONS', 1000),
        'max_message_size' => 4096,
        // Clients publish no data; only the authorized server relay may invalidate.
        'accept_client_events_from' => 'none',
        'rate_limiting' => ['enabled' => true, 'max_attempts' => 60, 'decay_seconds' => 60, 'terminate_on_limit' => true],
    ]]],
];
