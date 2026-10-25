<?php

declare(strict_types=1);

namespace App\Services\Live;

use Illuminate\Support\Facades\DB;

/** Environment configuration only; no caller can select a transport or destination. */
class LiveConfiguration
{
    public function ready(): bool
    {
        if (! config('live.enabled', false) || config('queue.connections.database.driver') !== 'database'
            || (config('queue.connections.database.connection') ?? DB::getDefaultConnection()) !== DB::getDefaultConnection()) {
            return false;
        }
        if (! app()->environment(['local', 'testing']) && ! in_array(config('cache.default'), ['database', 'redis'], true)) {
            return false; // Web, worker and socket services must share expiring authorization leases.
        }
        $connection = config('broadcasting.connections.reverb', []);
        $key = $connection['key'] ?? null;
        $secret = $connection['secret'] ?? null;
        $id = $connection['app_id'] ?? null;
        $host = config('live.client.host');
        $port = config('live.client.port');
        $scheme = config('live.client.scheme');
        if (! is_string($key) || preg_match('/^[a-zA-Z0-9_-]{8,128}$/D', $key) !== 1 || ! is_string($secret) || strlen($secret) < 32
            || ! is_string($id) || preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $id) !== 1
            || ! is_string($host) || strlen($host) > 253 || (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false && filter_var($host, FILTER_VALIDATE_IP) === false)
            || ! is_int($port) || $port < 1 || $port > 65535 || ! in_array($scheme, ['ws', 'wss'], true)) {
            return false;
        }

        // Cleartext is restricted to an explicitly local/test environment.
        return $scheme === 'wss' || (app()->environment(['local', 'testing']) && in_array($host, ['127.0.0.1', 'localhost', '::1'], true));
    }
}
