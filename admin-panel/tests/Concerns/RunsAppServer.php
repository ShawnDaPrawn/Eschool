<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Serves the app from a separate `php -S` process and talks to it over HTTP.
 *
 * Many endpoints (every API controller via ResponseService, /js/lang) call
 * exit() after sending their response, which would end the test run if they
 * were called with in-process test requests.
 *
 * The server can't see data inside a test transaction, so tests using this
 * migrate and seed for real (TestCase only allows *_test databases).
 */
trait RunsAppServer
{
    private ?Process $server = null;

    private string $serverStorage;

    private string $serverUrl;

    /** Migrate a fresh database and seed it the way the web installer does. */
    protected function freshInstall(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
    }

    /** @param  array<string, string>  $env  extra environment variables for the server */
    protected function startAppServer(array $env = []): void
    {
        $this->serverStorage = $this->makeServerStorage();
        $this->serverUrl = 'http://127.0.0.1:'.$this->freePort();

        $db = config('database.connections.'.config('database.default'));
        $this->server = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', Str::after($this->serverUrl, 'http://'),
                base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            public_path(),
            array_merge([
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => config('app.key'),
                'APP_URL' => $this->serverUrl,
                'LARAVEL_STORAGE_PATH' => $this->serverStorage,
                'DB_CONNECTION' => config('database.default'),
                'DB_HOST' => $db['host'],
                'DB_PORT' => (string) $db['port'],
                'DB_DATABASE' => $db['database'],
                'DB_USERNAME' => $db['username'],
                'DB_PASSWORD' => (string) $db['password'],
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'array',
                'MAIL_MAILER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'LOG_CHANNEL' => 'single',
            ], $env),
        );
        $this->server->start();

        for ($i = 0; $i < 100; $i++) {
            if (@fsockopen('127.0.0.1', (int) Str::afterLast($this->serverUrl, ':'))) {
                return;
            }
            usleep(100_000);
        }
        $this->fail('Test server did not start: '.$this->server->getErrorOutput());
    }

    protected function stopAppServer(): void
    {
        $this->server?->stop();
        $this->server = null;
        File::deleteDirectory($this->serverStorage);
        // Make the next RefreshDatabase test start from an empty database again.
        RefreshDatabaseState::$migrated = false;
    }

    protected function appClient(): PendingRequest
    {
        return Http::baseUrl($this->serverUrl)
            ->withOptions(['allow_redirects' => false])
            ->timeout(60);
    }

    /** A client for the mobile-app API, optionally signed in with a Sanctum token. */
    protected function apiClient(?string $token = null): PendingRequest
    {
        $client = $this->appClient()->acceptJson();

        return $token === null ? $client : $client->withToken($token);
    }

    /** The server's laravel.log, to explain an unexpected response. */
    protected function serverLog(): string
    {
        $log = $this->serverStorage.'/logs/laravel.log';

        return File::exists($log) ? File::get($log) : '';
    }

    /** A throwaway storage directory, marked as installed. */
    private function makeServerStorage(): string
    {
        $dir = sys_get_temp_dir().'/eschool-test-server-'.uniqid();
        foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $sub) {
            File::ensureDirectoryExists("$dir/$sub");
        }
        File::put("$dir/installed", '{}');

        return $dir;
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) Str::afterLast(stream_socket_get_name($socket, false), ':');
        fclose($socket);

        return $port;
    }
}
