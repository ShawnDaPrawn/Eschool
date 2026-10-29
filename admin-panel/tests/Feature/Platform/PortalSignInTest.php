<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\VerifyCsrfToken;
use App\Listeners\RotateBrowserSessionIdentity;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * The web portals sign in with Sanctum's cookie mode (defect D1): from a
 * portal origin, GET /sanctum/csrf-cookie, then the app's sign-in endpoint
 * with X-XSRF-TOKEN starts a web session (no bearer token), and the session
 * cookie then works on v1 and v2 routes. The phone apps (no Origin) sign in
 * with tokens as before. The CSRF check runs for real here (Laravel skips it
 * in tests by default).
 */
class PortalSignInTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    private const APP_PORTAL = 'https://app.navigateed.co.za';

    private const TEACH_PORTAL = 'https://teach.navigateed.co.za';

    private const SESSION_COOKIE = 'e_school_session';

    /** The browser's cookies for the API host. @var array<string, string> */
    private array $jar = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
        config([
            'session.cookie' => self::SESSION_COOKIE,
            'sanctum.stateful' => ['app.navigateed.co.za', 'teach.navigateed.co.za'],
            'cors.allowed_origins' => ['http://localhost', self::APP_PORTAL, self::TEACH_PORTAL],
        ]);
        $this->app->bind(VerifyCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_each_portal_signs_in_with_a_session_that_works_on_v1_and_v2(): void
    {
        foreach ([
            [self::APP_PORTAL, 'student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123'], 'learner', '/api/student/get-profile-data'],
            [self::APP_PORTAL, 'parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'], 'parent', '/api/parent/get-profile-data'],
            [self::TEACH_PORTAL, 'teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'], 'teacher', '/api/teacher/get-profile-details'],
        ] as [$portal, $login, $credentials, $person, $v1Read]) {
            $this->jar = [];
            $this->browser('GET', '/sanctum/csrf-cookie', [], $portal)->assertNoContent();
            $this->assertArrayHasKey('XSRF-TOKEN', $this->jar);
            $before = $this->sessionId();

            $this->browser('POST', '/api/'.$login, $credentials, $portal)
                ->assertOk()
                ->assertJson(['error' => false, 'token' => '', 'password_change_required' => false])
                ->assertHeader('Access-Control-Allow-Origin', $portal)
                ->assertHeader('Access-Control-Allow-Credentials', 'true');
            $this->assertNotSame($before, $this->sessionId(), "$login regenerates the session");
            $this->assertSame([], $this->person($person)->tokens()->pluck('name')->all(), "$login issues no token to a portal");

            $this->browser('GET', '/api/v2/me/context', [], $portal)
                ->assertOk()
                ->assertJsonPath('data.user.id', $this->people[$person]);
            $this->browser('GET', $v1Read, [], $portal)->assertOk()->assertJson(['error' => false]);
        }
    }

    public function test_the_role_groups_and_check_child_apply_to_a_portal_session(): void
    {
        $this->signInAt(self::APP_PORTAL, 'parent/login', ['email' => 'pam@example.com', 'password' => 'secret123']);

        $this->browser('GET', '/api/parent/timetable', ['child_id' => $this->people['student']])->assertOk()->assertJson(['error' => false]);
        $other = SchoolTable::query('students')->insertGetId([
            'user_id' => $this->person('teacher')->id, 'class_id' => $this->people['class'], 'class_section_id' => $this->people['class_section'],
            'admission_no' => 'GR-OTHER', 'roll_number' => 2, 'admission_date' => now()->toDateString(),
        ]);
        $this->assertSame(true, $this->browser('GET', '/api/parent/timetable', ['child_id' => $other])->json('error'), "another family's child");
        $this->assertContains($this->browser('GET', '/api/student/get-profile-data')->status(), [401, 403], 'a learner route');
        $this->assertContains($this->browser('GET', '/api/teacher/get-profile-details')->status(), [401, 403], 'a teacher route');
    }

    public function test_signing_out_of_a_portal_ends_the_session_and_leaves_the_phone_signed_in(): void
    {
        $learner = $this->person('learner');
        $learner->forceFill(['fcm_id' => 'phone-fcm-id'])->save();
        $phone = $learner->createToken(AppTokens::STUDENT)->plainTextToken;

        $this->signInAt(self::APP_PORTAL, 'student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123']);
        $this->assertNotNull($learner->tokens()->first(), 'a portal sign-in leaves the phone signed in');
        $signedIn = $this->sessionId();

        $this->browser('POST', '/api/logout')->assertOk()->assertJson(['error' => false]);

        $this->assertNotSame($signedIn, $this->sessionId(), 'the session is replaced');
        $this->browser('GET', '/api/v2/me/context')->assertUnauthorized();
        $this->browser('GET', '/api/student/get-profile-data')->assertUnauthorized();
        $this->assertSame('phone-fcm-id', $learner->fresh()->fcm_id, "the phone's push id is kept");
        $this->anotherDevice();
        $this->withToken($phone)->getJson('/api/student/get-profile-data')->assertOk();
    }

    public function test_a_refused_portal_sign_in_leaves_no_session(): void
    {
        DB::table('users')->where('id', $this->people['teacher'])->update(['status' => 0]);

        foreach ([
            [self::APP_PORTAL, 'student/login', ['gr_number' => 'pam@example.com', 'password' => 'secret123']],
            [self::APP_PORTAL, 'parent/login', ['email' => 'pam@example.com', 'password' => 'wrong-password']],
            [self::TEACH_PORTAL, 'teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123']],
        ] as [$portal, $login, $credentials]) {
            $this->jar = [];
            $this->browser('GET', '/sanctum/csrf-cookie', [], $portal);
            $this->browser('POST', '/api/'.$login, $credentials, $portal)->assertJson(['error' => true]);
            $this->browser('GET', '/api/v2/me/context', [], $portal)->assertUnauthorized();
        }
    }

    public function test_a_password_change_elsewhere_ends_the_portal_session(): void
    {
        $this->signInAt(self::TEACH_PORTAL, 'teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123']);
        $this->browser('GET', '/api/v2/me/context', [], self::TEACH_PORTAL)->assertOk();

        DB::table('users')->where('id', $this->people['teacher'])->update(['password' => Hash::make('a-new-password-2026')]);

        $this->browser('GET', '/api/v2/me/context', [], self::TEACH_PORTAL)->assertUnauthorized();
    }

    public function test_browser_identity_changes_only_with_sign_in_or_sign_out(): void
    {
        config(['session.domain' => '.navigateed.co.za', 'session.secure' => true]);
        $this->signInAt(self::APP_PORTAL, 'parent/login', ['email' => 'pam@example.com', 'password' => 'secret123']);
        $first = $this->jar[RotateBrowserSessionIdentity::COOKIE];

        $read = $this->browser('GET', '/api/v2/me/context')->assertOk();
        $this->assertSame($first, $this->jar[RotateBrowserSessionIdentity::COOKIE]);
        $this->assertNotContains(RotateBrowserSessionIdentity::COOKIE, array_map(fn ($cookie) => $cookie->getName(), $read->headers->getCookies()));

        // The same browser replaces its account in another portal, without
        // changing the first tab's in-memory state or local selected school.
        $signIn = $this->browser('POST', '/api/teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'], self::TEACH_PORTAL)->assertOk();
        $second = $this->jar[RotateBrowserSessionIdentity::COOKIE];
        $this->assertNotSame($first, $second);
        $cookie = collect($signIn->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === RotateBrowserSessionIdentity::COOKIE);
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('.navigateed.co.za', $cookie->getDomain());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->browser('GET', '/api/v2/me/context', [], self::TEACH_PORTAL)->assertJsonPath('data.user.id', $this->people['teacher']);

        $this->browser('POST', '/api/logout', [], self::TEACH_PORTAL)->assertOk();
        $signedOut = $this->jar[RotateBrowserSessionIdentity::COOKIE];
        $this->assertNotSame($second, $signedOut);
        $this->browser('GET', '/api/v2/me/context', [], self::TEACH_PORTAL)->assertUnauthorized();
        $this->assertSame($signedOut, $this->jar[RotateBrowserSessionIdentity::COOKIE], 'a current 401 must not change its request identity');
    }

    public function test_portal_writes_need_the_csrf_token(): void
    {
        $this->browser('GET', '/sanctum/csrf-cookie');

        $this->browser('POST', '/api/student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123'], self::APP_PORTAL, false)
            ->assertStatus(419);
        $this->browser('POST', '/api/student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123'])
            ->assertOk()->assertJson(['error' => false]);
        $this->browser('POST', '/api/logout', [], self::APP_PORTAL, false)->assertStatus(419);
        $this->browser('GET', '/api/v2/me/context')->assertOk();
    }

    public function test_the_phone_apps_sign_in_with_tokens_as_before(): void
    {
        foreach ([
            ['student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123'], 'learner', AppTokens::STUDENT],
            ['parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'], 'parent', AppTokens::PARENT],
            ['teacher/login', ['email' => 'tom@example.com', 'password' => 'secret123'], 'teacher', AppTokens::TEACHER],
        ] as [$login, $credentials, $person, $app]) {
            $this->anotherDevice();
            $response = $this->postJson('/api/'.$login, $credentials)->assertOk()->assertJson(['error' => false]);

            $this->assertNotSame('', (string) $response->json('token'));
            $this->assertSame([$app], $this->person($person)->tokens()->pluck('name')->all());
            $this->assertSame([], $response->headers->getCookies(), "$login sets no cookie for an app");

            $this->anotherDevice();
            $this->withToken((string) $response->json('token'))->getJson('/api/v2/me/context')->assertOk();
            $this->anotherDevice();
            $this->withToken((string) $response->json('token'))->postJson('/api/logout')->assertOk();
            $this->assertSame([], $this->person($person)->tokens()->pluck('name')->all(), "logout revokes the $app token");
        }
    }

    public function test_an_unknown_origin_is_not_a_portal(): void
    {
        $this->signInAt(self::APP_PORTAL, 'student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123']);

        foreach (['https://evil.example', 'https://app.navigateed.co.za.evil.example', 'null'] as $origin) {
            $this->browser('GET', '/api/v2/me/context', [], $origin)
                ->assertUnauthorized()
                ->assertHeaderMissing('Access-Control-Allow-Origin');

            // Without the stateful check there is no session and no CSRF check:
            // the endpoint treats the caller as an app (and the browser cannot
            // read the answer, which carries no CORS header).
            $this->anotherDevice();
            $response = $this->withHeaders(['Origin' => $origin])->postJson('/api/student/login', ['gr_number' => 'GR-LEO', 'password' => 'secret123']);
            $this->assertSame([], $response->headers->getCookies(), "$origin gets no session");
            $response->assertHeaderMissing('Access-Control-Allow-Origin');
        }
    }

    /** Signs in from a portal the way the portal does. */
    private function signInAt(string $portal, string $login, array $credentials): void
    {
        $this->jar = [];
        $this->browser('GET', '/sanctum/csrf-cookie', [], $portal);
        $this->browser('POST', '/api/'.$login, $credentials, $portal)->assertOk()->assertJson(['error' => false]);
    }

    /**
     * One request from the portal's JavaScript: the browser sends Origin and
     * Referer and the API host's cookies, and a write echoes the XSRF-TOKEN
     * cookie in X-XSRF-TOKEN. The cookies it answers with go into the jar.
     */
    private function browser(string $method, string $uri, array $data = [], string $origin = self::APP_PORTAL, bool $xsrf = true): TestResponse
    {
        $this->anotherDevice();
        $headers = ['Origin' => $origin, 'Referer' => $origin.'/'];
        if ($xsrf && $method !== 'GET' && isset($this->jar['XSRF-TOKEN'])) {
            $headers['X-XSRF-TOKEN'] = $this->jar['XSRF-TOKEN'];
        }
        $this->unencryptedCookies = $this->jar;

        $response = $method === 'GET'
            ? $this->withHeaders($headers)->withCredentials()->getJson($uri.($data ? '?'.http_build_query($data) : ''))
            : $this->withHeaders($headers)->withCredentials()->json($method, $uri, $data);
        foreach ($response->headers->getCookies() as $cookie) {
            $this->jar[$cookie->getName()] = (string) $cookie->getValue();
        }
        // A real HTTP request has a fresh cookie queue; this test reuses its container.
        Cookie::unqueue(RotateBrowserSessionIdentity::COOKIE, config('session.path', '/'));
        $this->defaultHeaders = [];

        return $response;
    }

    private function sessionId(): string
    {
        $value = $this->app['encrypter']->decrypt($this->jar[self::SESSION_COOKIE], false);

        return CookieValuePrefix::remove($value);
    }

    private function anotherDevice(): void
    {
        // auth:sanctum switches the default guard for the rest of the test.
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->defaultHeaders = [];
        $this->unencryptedCookies = [];
    }
}
