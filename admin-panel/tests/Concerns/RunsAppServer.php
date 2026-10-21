<?php

namespace Tests\Concerns;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Filesystem\Filesystem;
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
 * Many endpoints (every API controller via ResponseService) call exit() after
 * sending their response, which would end the test run if they were called
 * with in-process test requests.
 *
 * The server can't see data inside a test transaction, so tests using this
 * migrate and seed for real (TestCase only allows *_test databases).
 *
 * Start the server in setUp() for each test, or once per class by guarding it
 * with appServerRunning(); it is stopped after the class either way.
 */
trait RunsAppServer
{
    private static ?Process $appServer = null;

    private static string $appServerStorage = '';

    private static string $appServerUrl = '';

    /** Migrate a fresh database and seed it the way the web installer does. */
    protected function freshInstall(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
    }

    protected static function appServerRunning(): bool
    {
        return self::$appServer !== null;
    }

    /** @param  array<string, string>  $env  extra environment variables for the server */
    protected function startAppServer(array $env = []): void
    {
        self::stopTheAppServer();

        self::$appServerStorage = sys_get_temp_dir().'/eschool-test-server-'.uniqid();
        foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $sub) {
            File::ensureDirectoryExists(self::$appServerStorage."/$sub");
        }
        File::put(self::$appServerStorage.'/installed', '{}');

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) Str::afterLast(stream_socket_get_name($socket, false), ':');
        fclose($socket);
        self::$appServerUrl = "http://127.0.0.1:$port";

        $db = config('database.connections.'.config('database.default'));
        self::$appServer = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', "127.0.0.1:$port",
                base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            public_path(),
            array_merge([
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_KEY' => config('app.key'),
                'APP_URL' => self::$appServerUrl,
                'LARAVEL_STORAGE_PATH' => self::$appServerStorage,
                'DB_CONNECTION' => config('database.default'),
                'DB_HOST' => $db['host'],
                'DB_PORT' => (string) $db['port'],
                'DB_DATABASE' => $db['database'],
                'DB_USERNAME' => $db['username'],
                'DB_PASSWORD' => (string) $db['password'],
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'array',
                // config/cache.php reads CACHE_DRIVER, not CACHE_STORE.
                'CACHE_DRIVER' => 'array',
                'MAIL_MAILER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'LOG_CHANNEL' => 'single',
            ], $env),
        );
        self::$appServer->start();

        for ($i = 0; $i < 100; $i++) {
            if (@fsockopen('127.0.0.1', $port)) {
                return;
            }
            usleep(100_000);
        }
        $this->fail('Test server did not start: '.self::$appServer->getErrorOutput());
    }

    protected function stopAppServer(): void
    {
        self::stopTheAppServer();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopTheAppServer();

        parent::tearDownAfterClass();
    }

    private static function stopTheAppServer(): void
    {
        if (self::$appServer === null) {
            return;
        }
        self::$appServer->stop();
        self::$appServer = null;

        // tearDownAfterClass runs after Laravel has flushed the application, so the
        // File facade is gone. PHPUnit records a thrown exception here as an error
        // and exits 2, while every test still shows as passed. The filesystem class
        // itself does not need the container.
        $storage = self::$appServerStorage;
        $log = $storage.'/logs/laravel.log';
        if (($keep = getenv('TEST_SERVER_LOG')) && is_file($log)) {
            copy($log, $keep);
        }
        // Make the next RefreshDatabase test start from an empty database again,
        // even if removing the directory fails.
        RefreshDatabaseState::$migrated = false;
        if ($storage !== '') {
            (new Filesystem)->deleteDirectory($storage);
        }
    }

    protected function appClient(): PendingRequest
    {
        return Http::baseUrl(self::$appServerUrl)
            ->withOptions(['allow_redirects' => false])
            ->timeout(60);
    }

    /**
     * A client signed in to the admin panel, sending the CSRF token in a header
     * like the panel's AJAX calls do.
     */
    protected function adminPanelClient(string $email, string $password): PendingRequest
    {
        $client = $this->appClient()->withOptions(['cookies' => new CookieJar]);

        $form = $client->get('/login');
        $this->assertSame(200, $form->status(), 'Login page did not load');
        $login = $client->asForm()->post('/login', [
            '_token' => $this->csrfToken($form->body()),
            'email' => $email,
            'password' => $password,
        ]);
        $this->assertStringEndsWith('/home', (string) $login->header('Location'), "Login failed for $email");

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

    /** A client for the mobile-app API, optionally signed in with a Sanctum token. */
    protected function apiClient(?string $token = null): PendingRequest
    {
        $client = $this->appClient()->acceptJson();

        return $token === null ? $client : $client->withToken($token);
    }

    /** A path inside the server's storage directory (uploads land in app/public). */
    protected function appServerStoragePath(string $path = ''): string
    {
        return self::$appServerStorage.($path === '' ? '' : '/'.ltrim($path, '/'));
    }

    /** The server's laravel.log, to explain an unexpected response. */
    protected function serverLog(): string
    {
        $log = $this->appServerStoragePath('logs/laravel.log');

        return File::exists($log) ? File::get($log) : '';
    }
}
