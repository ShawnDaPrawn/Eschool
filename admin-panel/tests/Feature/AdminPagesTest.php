<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

/**
 * Logs in as the Super Admin, then as a teacher, on a freshly installed app and
 * opens every GET page that takes no parameters, checking that none crashes
 * (and that ordinary admin pages are not forbidden to the Super Admin).
 *
 * The pages are served by a separate `php -S` process (see RunsAppServer).
 */
class AdminPagesTest extends TestCase
{
    use RunsAppServer;

    /** Routes with side effects, or that send mail, are not opened. */
    private const SKIP = [
        'delete-chat-message/cron-job',
    ];

    private const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/'];

    /** Purpose-restricted pages and source downloads require their own bound context. */
    private const PURPOSE_RESTRICTED = ['vendor-counter', 'ai-flags', 'platform/ai-costs', 'group-conversations', 'school-cards/encoder-runtime', 'saas', 'saas/schools/create', 'saas/password'];

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
        // With the default password every page would send us to change it.
        $this->superAdmin()->update(['password' => Hash::make('admin-pages-test')]);
        $client = $this->loggedInClient('superadmin@gmail.com', 'admin-pages-test');

        $forbidden = [];
        $broken = [];
        foreach ($this->pageUris() as $uri) {
            $status = $client->get('/'.$uri)->status();
            if (in_array($uri, self::PURPOSE_RESTRICTED, true)) {
                $this->assertSame(403, $status, 'School admin acquired a separate purpose: '.$uri);

                continue;
            }
            if ($status === 403) {
                $forbidden[] = $uri;
            } elseif ($status >= 500) {
                $broken[] = "$uri ($status)";
            }
        }

        $this->assertSame([], $forbidden, 'Pages returned 403 Forbidden');
        $this->assertSame([], $broken, 'Pages returned a server error');
    }

    public function test_no_admin_page_crashes_for_a_teacher(): void
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.test@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);
        Teacher::forceCreate(['user_id' => $teacher->id]);
        $client = $this->loggedInClient('teacher.test@example.com', 'secret123');

        // A teacher may be refused admin pages (403 or a redirect), but none may crash.
        $broken = [];
        foreach ($this->pageUris() as $uri) {
            $status = $client->get('/'.$uri)->status();
            if ($status >= 500) {
                $broken[] = "$uri ($status)";
            }
        }

        $this->assertSame([], $broken, 'Pages returned a server error for a teacher');
    }

    private function loggedInClient(string $email, string $password): PendingRequest
    {
        $client = $this->appClient()->withOptions(['cookies' => new CookieJar]);

        $form = $client->get('/login');
        $this->assertSame(200, $form->status(), 'Login page did not load');
        preg_match('/name="_token" value="([^"]+)"/', $form->body(), $token);
        $this->assertNotEmpty($token, 'No CSRF token on the login page');

        $login = $client->asForm()->post('/login', [
            '_token' => $token[1],
            'email' => $email,
            'password' => $password,
        ]);
        $this->assertStringEndsWith('/home', (string) $login->header('Location'), "Login failed for $email");

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
