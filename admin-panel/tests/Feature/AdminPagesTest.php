<?php

namespace Tests\Feature;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Logs in as the Super Admin on a freshly installed app and opens every GET
 * page that takes no parameters, checking that none is forbidden or crashes.
 *
 * The pages are served by a separate `php -S` process rather than in-process
 * test requests, because many endpoints (ResponseService, /js/lang) call
 * exit() after sending their response, which would end the test run.
 */
class AdminPagesTest extends TestCase
{
    /** Routes with side effects, or that send mail, are not opened. */
    private const SKIP = [
        'clear', 'storage-link', 'migrate', 'seeder_install',
        'delete-chat-message/cron-job', 'sendtest', 'logout',
    ];

    private const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/'];

    /**
     * Pages that already returned 500 on a fresh install of the original
     * 3.5.0 code. Remove entries as they are fixed; the test fails if a listed
     * page starts working, so the list stays accurate.
     */
    private const KNOWN_BROKEN = [
        'announcement-list',
        'announcement/create',
        'assignment/create',
        'attendance/create',
        'category/create',
        'class-list',
        'class-teacher-list',
        'class/create',
        'content-list',
        'exams/create',
        'fees-type/create',
        'fees/classes/list',
        'fees/fees-receipt',
        'fees/transaction-logs/list',
        'get-timetable-by-subject-teacher-class',
        'holiday-list',
        'holiday/create',
        'language/create',
        'leave/create',
        'lesson-topic/create',
        'lesson/create',
        'medium/create',
        'notifications/create',
        'online-exam-question/create',
        'online-exam/create',
        'parents/create',
        'privacy-policy',
        'promote-student/create',
        'section/create',
        'semester/create',
        'session-years/create',
        'show-grades',
        'sliders/create',
        'subject-teachers-list',
        'subject-teachers/create',
        'subject/create',
        'teachers/create',
        'terms-conditions',
        'timetable-list',
        'timetable/create',
        'video-gallery',
    ];

    private Process $server;

    private string $storage;

    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // The server process can't see data inside a test transaction, so this
        // test migrates and seeds for real (TestCase only allows *_test databases).
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();

        $this->storage = $this->makeStorage();
        $this->baseUrl = 'http://127.0.0.1:'.$this->freePort();
        $this->server = $this->startServer();
    }

    protected function tearDown(): void
    {
        $this->server->stop();
        File::deleteDirectory($this->storage);
        // Make the next RefreshDatabase test start from an empty database again.
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

    public function test_no_admin_page_is_forbidden_or_newly_broken(): void
    {
        $client = $this->loggedInClient();

        $forbidden = [];
        $broken = [];
        $fixed = [];
        foreach ($this->pageUris() as $uri) {
            $status = $client->get('/'.$uri)->status();
            if ($status === 403) {
                $forbidden[] = $uri;
            }
            $isBroken = $status >= 500;
            $isKnown = in_array($uri, self::KNOWN_BROKEN, true);
            if ($isBroken && ! $isKnown) {
                $broken[] = "$uri ($status)";
            } elseif (! $isBroken && $isKnown) {
                $fixed[] = $uri;
            }
        }

        $this->assertSame([], $forbidden, 'Pages returned 403 Forbidden');
        $this->assertSame([], $broken, 'Pages returned a server error');
        $this->assertSame([], $fixed, 'These pages work now; remove them from KNOWN_BROKEN');
    }

    private function loggedInClient(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->withOptions(['cookies' => new CookieJar, 'allow_redirects' => false])
            ->timeout(60);

        $form = $client->get('/login');
        $this->assertSame(200, $form->status(), 'Login page did not load');
        preg_match('/name="_token" value="([^"]+)"/', $form->body(), $token);
        $this->assertNotEmpty($token, 'No CSRF token on the login page');

        $login = $client->asForm()->post('/login', [
            '_token' => $token[1],
            'email' => 'superadmin@gmail.com',
            'password' => 'superadmin',
        ]);
        $this->assertStringEndsWith('/home', (string) $login->header('Location'), 'Super Admin login failed');

        return $client;
    }

    /** @return list<string> */
    private function pageUris(): array
    {
        $uris = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods(), true))
            ->map(fn (Route $route) => $route->uri())
            ->reject(fn (string $uri) => Str::contains($uri, '{')
                || in_array($uri, self::SKIP, true)
                || Str::startsWith($uri, self::SKIP_PREFIXES))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertNotEmpty($uris);

        return $uris;
    }

    private function startServer(): Process
    {
        $db = config('database.connections.'.config('database.default'));
        $server = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', Str::after($this->baseUrl, 'http://'),
                base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            public_path(),
            [
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
                'MAIL_MAILER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'LOG_CHANNEL' => 'single',
            ],
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
        $dir = sys_get_temp_dir().'/eschool-pages-test-'.uniqid();
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
