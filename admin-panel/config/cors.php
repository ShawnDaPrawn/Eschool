<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| Browsers may call the API from the panel itself (APP_URL) and from the web
| portals only. The portals' origins come from CORS_ALLOWED_ORIGINS (comma
| separated, e.g. https://app.navigateed.co.za,https://teach.navigateed.co.za).
| Without it, a production install allows the portal hostnames decided in
| engineering/phases/phase-0-questions.md (Q0.2): app. and teach. on the
| NavigateEd domain, prefixed with staging- when APP_URL is a staging- host.
| Local and test installs allow http(s)://localhost and 127.0.0.1 on any port
| instead (flutter run picks a port).
|
| supports_credentials is on for the portals' Sanctum cookie mode
| (config/sanctum.php lists the same hosts as stateful). The native apps send
| no Origin header, so none of this applies to them.
|
*/

$origin = static function (?string $url): ?string {
    $parts = parse_url(trim((string) $url));
    if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    return strtolower($parts['scheme'].'://'.$parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
};

$appUrl = (string) env('APP_URL', 'http://localhost');
$local = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true);
$configured = trim((string) env('CORS_ALLOWED_ORIGINS', ''));

if ($configured !== '') {
    $portals = explode(',', $configured);
} elseif ($local) {
    $portals = [];
} else {
    $prefix = str_starts_with((string) parse_url($appUrl, PHP_URL_HOST), 'staging-') ? 'staging-' : '';
    $portals = ["https://{$prefix}app.navigateed.co.za", "https://{$prefix}teach.navigateed.co.za"];
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter(array_map($origin, [$appUrl, ...$portals])))),

    'allowed_origins_patterns' => $local && $configured === ''
        ? ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
