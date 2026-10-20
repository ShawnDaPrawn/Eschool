<?php

namespace Tests\Feature;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

/**
 * Logs in as the Super Admin on a freshly installed app and opens every GET
 * page that takes no parameters, checking that none is forbidden or crashes.
 *
 * The pages are served by a separate `php -S` process (see RunsAppServer).
 */
class AdminPagesTest extends TestCase
{
    use RunsAppServer;

    /** Routes with side effects, or that send mail, are not opened. */
    private const SKIP = [
        'clear', 'storage-link', 'migrate', 'seeder_install',
        'delete-chat-message/cron-job', 'logout',
    ];

    private const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->freshInstall();
        $this->startAppServer();
    }

    protected function tearDown(): void
    {
        $this->stopAppServer();
        parent::tearDown();
    }

    public function test_no_admin_page_is_forbidden_or_broken(): void
    {
        $client = $this->loggedInClient();

        $forbidden = [];
        $broken = [];
        foreach ($this->pageUris() as $uri) {
            $status = $client->get('/'.$uri)->status();
            if ($status === 403) {
                $forbidden[] = $uri;
            } elseif ($status >= 500) {
                $broken[] = "$uri ($status)";
            }
        }

        $this->assertSame([], $forbidden, 'Pages returned 403 Forbidden');
        $this->assertSame([], $broken, 'Pages returned a server error');
    }

    private function loggedInClient(): PendingRequest
    {
        $client = $this->appClient()->withOptions(['cookies' => new CookieJar]);

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
}
