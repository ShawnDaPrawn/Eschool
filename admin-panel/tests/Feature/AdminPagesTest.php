<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\Concerns\ServesApp;
use Tests\TestCase;

/**
 * Logs in as the Super Admin on a freshly installed app and opens every GET
 * page that takes no parameters, checking that none is forbidden or crashes.
 */
class AdminPagesTest extends TestCase
{
    use ServesApp;

    /** Routes with side effects, or that send mail, are not opened. */
    private const SKIP = [
        'clear', 'storage-link', 'migrate', 'seeder_install',
        'delete-chat-message/cron-job', 'logout',
    ];

    private const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->startApp();
    }

    protected function tearDown(): void
    {
        $this->stopApp();
        parent::tearDown();
    }

    public function test_no_admin_page_is_forbidden_or_broken(): void
    {
        $client = $this->loggedInClient('superadmin@gmail.com', 'superadmin');

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
