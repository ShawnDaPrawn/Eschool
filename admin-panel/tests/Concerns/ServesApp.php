<?php

namespace Tests\Concerns;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Serves the app from a separate `php -S` process. Many endpoints
 * (ResponseService, /js/lang) call exit() after sending their response, which
 * would end an in-process test run.
 *
 * The server can't see data inside a test transaction, so the database is
 * migrated and seeded for real (TestCase only allows *_test databases).
 */
trait ServesApp
{
    private Process $server;

    private string $storage;

    private string $baseUrl;

    protected function startApp(array $env = []): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();

        $this->storage = $this->makeStorage();
        $this->baseUrl = 'http://127.0.0.1:'.$this->freePort();
        $this->server = $this->startServer($env);
    }

    protected function stopApp(): void
    {
        $this->server->stop();
        File::deleteDirectory($this->storage);
        // Make the next RefreshDatabase test start from an empty database again.
        RefreshDatabaseState::$migrated = false;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withOptions(['cookies' => new CookieJar, 'allow_redirects' => false])
            ->timeout(60);
    }

    protected function loggedInClient(string $email, string $password): PendingRequest
    {
        $client = $this->client();

        $form = $client->get('/login');
        $this->assertSame(200, $form->status(), 'Login page did not load');
        $login = $client->asForm()->post('/login', [
            '_token' => $this->csrfToken($form->body()),
            'email' => $email,
            'password' => $password,
        ]);
        $this->assertStringEndsWith('/home', (string) $login->header('Location'), "Login failed for $email");

        // Later requests send the token in a header, like the panel's AJAX calls.
        return $client->asMultipart()->withHeaders([
            'X-CSRF-TOKEN' => $this->csrfToken($client->get('/home')->body()),
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    private function csrfToken(string $html): string
    {
        preg_match('/name="_token" value="([^"]+)"|name="csrf-token" content="([^"]+)"/', $html, $token);
        $this->assertNotEmpty($token, 'No CSRF token on the page');

        return $token[1] ?: $token[2];
    }

    private function startServer(array $env): Process
    {
        $db = config('database.connections.'.config('database.default'));
        $server = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', Str::after($this->baseUrl, 'http://'),
                base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            public_path(),
            array_merge([
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => config('app.key'),
                'APP_URL' => $this->baseUrl,
                'LARAVEL_STORAGE_PATH' => $this->storage,
                'DB_CONNECTION' => config('database.default'),
                'DB_HOST' => $db['host'],
                'DB_PORT' => (string) $db['port'],
                'DB_DATABASE' => $db['database'],
                'DB_USERNAME' => $db['username'],
                'DB_PASSWORD' => (string) $db['password'],
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'array',
                'CACHE_DRIVER' => 'array',
                'MAIL_MAILER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'LOG_CHANNEL' => 'single',
            ], $env),
        );
        $server->start();

        for ($i = 0; $i < 100; $i++) {
            if (@fsockopen('127.0.0.1', (int) Str::afterLast($this->baseUrl, ':'))) {
                return $server;
            }
            usleep(100_000);
        }
        $this->fail('Test server did not start: '.$server->getErrorOutput());
    }

    /** A throwaway storage directory, marked as installed. */
    private function makeStorage(): string
    {
        $dir = sys_get_temp_dir().'/eschool-app-test-'.uniqid();
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
