<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SignIn\SignInProviderChallenge;
use App\Models\SignIn\SignInProviderLink;
use App\Models\SignIn\SignInProviderSetting;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\SignIn\ProviderKeys;
use App\Services\Platform\SignIn\ProviderSettings;
use App\Services\Platform\SignIn\ProviderSignInRefused;
use App\Services\Platform\Tenancy\CurrentSchool;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Google and Microsoft sign-in (Action Plan 5.9, part 2).
 * engineering/specs/platform/google-microsoft-sign-in.md.
 *
 * Nothing reaches Google or Microsoft: the providers' JWKS and token
 * endpoints are faked over HTTP, and every ID token is signed here with RSA
 * keys generated for this test class.
 */
class ProviderSignInTest extends TestCase
{
    use UsesTwoSchools;

    private const GOOGLE_WEB = 'test-google-web.apps.example';

    private const GOOGLE_ANDROID = 'test-google-android.apps.example';

    private const MICROSOFT_CLIENT = '11111111-2222-4333-8444-555555555555';

    private const TENANT = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    private const OTHER_TENANT = 'ffffffff-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    private const GOOGLE_JWKS = 'https://www.googleapis.com/oauth2/v3/certs';

    private const MICROSOFT_JWKS = 'https://login.microsoftonline.com/common/discovery/v2.0/keys';

    /** @var array<string, array{pem: string, jwk: array<string, string>}> */
    private static array $keys = [];

    /** Codes the fake token endpoints have already exchanged. */
    private array $usedCodes = [];

    /** The id_token each fake code exchanges for. */
    private array $codeTokens = [];

    /** The PKCE challenge of the last authorize address. */
    private ?string $codeChallenge = null;

    private int $jwksFetches = 0;

    /** How the fake Google JWKS answers: normal, down or oversized. */
    private string $jwksMode = 'normal';

    private User $tara;

    private User $theo;

    /** School A's slug (from the installed app name). */
    private string $slugA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2027-03-10 09:00:00', 'UTC'));
        $this->setUpTwoSchools();
        SchoolMembership::query()->update(['starts_on' => '2027-01-01']);
        Queue::fake();
        Mail::fake();
        Cache::flush();
        foreach (['google-1', 'google-2', 'microsoft-1', 'attacker'] as $kid) {
            self::$keys[$kid] ??= $this->rsaKey($kid);
        }
        config([
            'sign_in_providers.providers.google.client_id' => self::GOOGLE_WEB,
            'sign_in_providers.providers.google.client_secret' => 'test-google-secret-placeholder',
            'sign_in_providers.providers.google.app_client_ids' => [self::GOOGLE_ANDROID],
            'sign_in_providers.providers.microsoft.client_id' => self::MICROSOFT_CLIENT,
            'sign_in_providers.providers.microsoft.client_secret' => 'test-microsoft-secret-placeholder',
        ]);
        Http::fake([
            self::GOOGLE_JWKS => function () {
                $this->jwksFetches++;

                return match ($this->jwksMode) {
                    'down' => Http::response('', 503),
                    'oversized' => Http::response(['keys' => array_fill(0, 40, self::$keys['google-1']['jwk'])]),
                    default => Http::response(['keys' => [self::$keys['google-1']['jwk'], self::$keys['google-2']['jwk']]], 200, ['Cache-Control' => 'public, max-age=20000']),
                };
            },
            self::MICROSOFT_JWKS => fn () => Http::response(['keys' => [array_diff_key(self::$keys['microsoft-1']['jwk'], ['alg' => 1])]], 200),
            'https://oauth2.googleapis.com/token' => fn (HttpRequest $request) => $this->tokenEndpoint($request),
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => fn (HttpRequest $request) => $this->tokenEndpoint($request),
        ]);
        Http::preventStrayRequests();
        $this->tara = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->theo = User::query()->findOrFail($this->schoolA->ids['theo_user']);
        $this->slugA = (string) School::query()->findOrFail(1)->slug;
    }

    // ---- The panel ----------------------------------------------------------

    public function test_a_teacher_links_their_google_account_in_the_panel_then_signs_in_with_it(): void
    {
        $this->enableGoogle();
        $this->get('/login')->assertOk()->assertSee('Sign in with Google');

        // Linking: signed in with a password, the password again, then Google.
        $this->actingAs($this->tara);
        $this->get('/sign-in-links')->assertOk()->assertSee('Link a Google account');
        $this->post('/sign-in-links/google', ['password' => 'wrong'])->assertRedirect(route('sign-in-links.index'));
        $this->assertSame(0, SignInProviderChallenge::query()->count(), 'A wrong password starts nothing');
        $this->callbackFor($this->post('/sign-in-links/google', ['password' => 'secret123']), $this->googleClaims('tara-sub', 'tara@example.com'), 'code-link')
            ->assertRedirect(route('sign-in-links.index'));
        $link = SignInProviderLink::query()->sole();
        $this->assertSame([(int) $this->tara->id, 'google', '', 'tara-sub', 'tara@example.com', 'panel'], [(int) $link->user_id, $link->provider, $link->tenant_id, $link->subject, $link->email_at_link, $link->linked_via]);
        $this->assertTrue(AuditLog::query()->where('action', 'sign_in.provider_linked')->exists());

        // Signing in: the login page's button, Google, back at the callback.
        $this->signOut();
        $start = $this->post('/sign-in/google', ['school' => $this->slugA]);
        $authorize = $this->authorizeQuery($start);
        $this->assertSame(self::GOOGLE_WEB, $authorize['client_id']);
        $this->assertSame('S256', $authorize['code_challenge_method']);
        $this->assertSame('example.com', $authorize['hd']);
        $this->assertSame(route('sign-in-provider.callback', ['provider' => 'google']), $authorize['redirect_uri']);
        $this->codeTokens['code-1'] = $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $authorize['nonce']]));
        $this->get('/sign-in/google/callback?'.http_build_query(['code' => 'code-1', 'state' => $authorize['state']]))->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->tara);
        $this->assertSame(['school' => 1, 'provider' => 'google', 'link' => (int) $link->id], session('sign_in_provider'));
        $this->get('/home')->assertOk();
        $this->assertNotNull($link->fresh()->last_used_at);
        $this->assertTrue(AuditLog::query()->where('action', 'sign_in.provider_signed_in')->where('actor_id', $this->tara->id)->exists());

        // The challenge row keeps only hashes; the verifier is encrypted.
        $row = DB::table('sign_in_provider_challenges')->latest('id')->first();
        $this->assertSame(64, strlen((string) $row->nonce_hash));
        $this->assertStringNotContainsString($authorize['nonce'], json_encode($row));
        $this->assertStringNotContainsString($authorize['state'], json_encode($row));
    }

    public function test_a_replayed_state_or_code_is_refused(): void
    {
        $this->enableGoogle();
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $authorize = $this->authorizeQuery($this->post('/sign-in/google', ['school' => $this->slugA]));
        $this->codeTokens['code-1'] = $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $authorize['nonce']]));
        $callback = '/sign-in/google/callback?'.http_build_query(['code' => 'code-1', 'state' => $authorize['state']]);
        $this->get($callback)->assertRedirect('/home');
        $this->signOut();

        // The same callback again: the state was used.
        $this->get($callback)->assertRedirect(route('login'));
        $this->assertGuest();

        // A fresh state with the used code: the provider refuses it.
        $again = $this->authorizeQuery($this->post('/sign-in/google', ['school' => $this->slugA]));
        $this->get('/sign-in/google/callback?'.http_build_query(['code' => 'code-1', 'state' => $again['state']]))->assertRedirect(route('login'));
        $this->assertGuest();

        // A state from another browser (its session never started it) is refused too.
        $other = $this->authorizeQuery($this->post('/sign-in/google', ['school' => $this->slugA]));
        $this->flushSession();
        $this->codeTokens['code-2'] = $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $other['nonce']]));
        $this->get('/sign-in/google/callback?'.http_build_query(['code' => 'code-2', 'state' => $other['state']]))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(['state_invalid', 'code_rejected', 'state_invalid'], $this->refusals());
    }

    public function test_the_login_button_shows_only_where_the_school_has_the_provider_on(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Sign in with Google')->assertDontSee('Sign in with Microsoft');
        $this->enableGoogle();
        $this->get('/login')->assertOk()->assertSee('Sign in with Google')->assertDontSee('Sign in with Microsoft');
        $this->get('/login?school=baobab-high')->assertOk()->assertDontSee('Sign in with Google');
        // Switched on at the school but not set up on this server: offered nowhere.
        config(['sign_in_providers.providers.google.client_secret' => null]);
        $this->get('/login')->assertOk()->assertDontSee('Sign in with Google');
        $this->post('/sign-in/google', ['school' => $this->slugA])->assertRedirect();
        $this->assertSame(0, SignInProviderChallenge::query()->count());
    }

    // ---- The apps -----------------------------------------------------------

    public function test_a_teacher_signs_in_to_the_teacher_app_with_a_google_id_token(): void
    {
        $this->enableGoogle();
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $nonce = $this->nonce('google');
        $response = $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce, 'aud' => self::GOOGLE_ANDROID])))
            ->assertOk()->assertJsonPath('error', false)->assertJsonPath('data.school.id', 1)->assertJsonPath('data.app', 'teacher')
            ->assertHeader('Cache-Control', 'no-store, private');
        $plain = (string) $response->json('data.token');
        $token = PersonalAccessToken::findToken($plain);
        $this->assertSame(AppTokens::TEACHER, $token->name);
        $this->assertSame(['*', 'sign-in-provider:1:google:'.$link->id], $token->abilities);

        // The same app token as a password sign-in: it opens the app, in this school.
        $this->forget();
        $this->withToken($plain)->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.current_school.id', 1);
        $this->forget();
        $this->withToken($plain)->getJson('/api/teacher/classes')->assertOk()->assertJsonPath('error', false);
        // Signing out everywhere ends it.
        AppTokens::revokeAll($this->tara->fresh());
        $this->forget();
        $this->withToken($plain)->getJson('/api/v2/me/context')->assertUnauthorized();
    }

    public function test_a_replayed_nonce_or_id_token_is_refused(): void
    {
        $this->enableGoogle();
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $nonce = $this->nonce('google');
        $jwt = $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce]));
        $this->appSignIn('google', $nonce, $jwt)->assertOk();
        $this->forget();
        $this->appSignIn('google', $nonce, $jwt)->assertUnauthorized()->assertJsonPath('data', null);
        // A nonce for the student app cannot sign in to the teacher app.
        $studentNonce = $this->nonce('google', 'student');
        $this->forget();
        $this->appSignIn('google', $studentNonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $studentNonce])))->assertUnauthorized();
        $this->assertSame(['nonce_invalid', 'nonce_invalid'], $this->refusals());
    }

    public function test_every_id_token_check_refuses_a_bad_token(): void
    {
        // Twenty-odd attempts from one address: the per-address limit has its own test.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->enableGoogle();
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $cases = [
            'bad_signature' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n]), 'attacker', 'google-1'),
            'wrong_audience' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'aud' => 'someone-elses-client'])),
            'wrong_issuer' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'iss' => 'https://accounts.example.net'])),
            'expired' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'iat' => now()->subMinutes(5)->timestamp, 'exp' => now()->subMinutes(2)->timestamp])),
            'too_old' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'iat' => now()->subMinutes(30)->timestamp])),
            'not_yet_valid' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'iat' => now()->addMinutes(10)->timestamp])),
            'nonce_mismatch' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => 'a-different-nonce'])),
            'email_unverified' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n, 'email_verified' => false])),
            'domain_not_allowed' => fn (string $n) => $this->idToken($this->googleClaims('tara-sub', 'tara@elsewhere.example', ['nonce' => $n, 'hd' => 'elsewhere.example'])),
            'invalid_token' => fn (string $n) => JWT::encode($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n]), str_repeat('shared-secret-not-rsa-', 4), 'HS256', 'google-1'),
        ];
        // A consumer Google account using the school address (no hd) is refused while Workspace accounts are required.
        $cases['domain_not_allowed '] = fn (string $n) => $this->idToken(array_diff_key($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $n]), ['hd' => 1]));
        foreach ($cases as $reason => $token) {
            $nonce = $this->nonce('google');
            $this->forget();
            $response = $this->appSignIn('google', $nonce, $token($nonce));
            $this->assertContains($response->status(), [401, 403], $reason);
            $this->assertNull($response->json('data'), $reason);
            $this->assertSame(trim($reason), $this->refusals()[array_key_last($this->refusals())], $reason);
        }
        $this->assertSame(0, $this->tara->tokens()->where('name', AppTokens::TEACHER)->count(), 'No bad token signed anyone in');
        $this->assertSame(0, AuditLog::query()->where('action', 'sign_in.provider_signed_in')->count());
        // Nothing secret is ever written to the audit log.
        $log = AuditLog::query()->where('action', 'like', 'sign_in.%')->get()->toJson();
        $this->assertStringNotContainsString('test-google-secret-placeholder', $log);
        $this->assertStringNotContainsString('tara-sub', $log);
    }

    public function test_attempts_are_limited_per_address(): void
    {
        $this->enableGoogle();
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v2/auth/providers/google/sign-in', ['school' => $this->slugA, 'app' => 'teacher', 'id_token' => 'x.y.z', 'nonce' => 'n'.$i]);
        }
        $this->postJson('/api/v2/auth/providers/google/nonce', ['school' => $this->slugA, 'app' => 'teacher'])->assertStatus(429);
        $this->post('/sign-in/google', ['school' => $this->slugA])->assertStatus(429);
    }

    public function test_a_microsoft_work_account_signs_in_only_from_an_allowed_tenant_with_its_tenant_issuer(): void
    {
        $this->enableMicrosoft();
        $oid = '0f0f0f0f-1111-4222-8333-444444444444';
        $link = $this->linkDirectly($this->tara, 'microsoft', $oid, 'tara@example.com', self::TENANT);
        $nonce = $this->nonce('microsoft');
        $plain = $this->appSignIn('microsoft', $nonce, $this->idToken($this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $nonce]), 'microsoft-1'))
            ->assertOk()->json('data.token');
        $this->assertSame(['*', 'sign-in-provider:1:microsoft:'.$link->id], PersonalAccessToken::findToken($plain)->abilities);

        $cases = [
            // The common issuer, or another tenant's, is not this tenant's.
            'wrong_issuer' => fn (string $n) => $this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $n, 'iss' => 'https://login.microsoftonline.com/'.self::OTHER_TENANT.'/v2.0']),
            'tenant_not_allowed' => fn (string $n) => $this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $n, 'tid' => self::OTHER_TENANT, 'iss' => 'https://login.microsoftonline.com/'.self::OTHER_TENANT.'/v2.0']),
            'email_unverified' => fn (string $n) => array_diff_key($this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $n]), ['xms_edov' => 1]),
            'guest_account' => fn (string $n) => $this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $n, 'idp' => 'https://sts.windows.net/'.self::OTHER_TENANT.'/']),
            'wrong_audience' => fn (string $n) => $this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $n, 'aud' => self::GOOGLE_WEB]),
        ];
        foreach ($cases as $reason => $claims) {
            $nonce = $this->nonce('microsoft');
            $this->forget();
            $this->assertContains($this->appSignIn('microsoft', $nonce, $this->idToken($claims($nonce), 'microsoft-1'))->status(), [401, 403], $reason);
            $this->assertSame($reason, $this->refusals()[array_key_last($this->refusals())], $reason);
        }
        // The same subject in another tenant is another person: never this link.
        $nonce = $this->nonce('microsoft');
        app(CurrentSchool::class)->run(1, fn () => SignInProviderSetting::query()->where('provider', 'microsoft')->update(['allowed_tenants' => [self::TENANT, self::OTHER_TENANT]]));
        $this->forget();
        $this->appSignIn('microsoft', $nonce, $this->idToken($this->microsoftClaims($oid, 'tara@example.com', ['nonce' => $nonce, 'tid' => self::OTHER_TENANT, 'iss' => 'https://login.microsoftonline.com/'.self::OTHER_TENANT.'/v2.0']), 'microsoft-1'))
            ->assertForbidden();
        $this->assertSame('not_linked', $this->refusals()[array_key_last($this->refusals())]);
    }

    public function test_a_learner_signs_in_only_when_the_school_allows_learners_of_their_stage(): void
    {
        $asha = User::query()->findOrFail($this->schoolA->ids['asha_user']);
        $asha->forceFill(['email' => 'asha@example.com'])->save();
        $this->enableGoogle();
        $this->linkDirectly($asha, 'google', 'asha-sub', 'asha@example.com');
        $claims = fn (string $n) => $this->googleClaims('asha-sub', 'asha@example.com', ['nonce' => $n]);

        // Learners are off by default.
        $nonce = $this->nonce('google', 'student');
        $this->appSignIn('google', $nonce, $this->idToken($claims($nonce)), 'student')->assertForbidden();
        $this->assertSame('role_not_allowed', $this->refusals()[0]);

        // On, for high school (the default): a Grade 5 learner is junior, so still no.
        $this->setGoogle(['learners_allowed' => true]);
        $this->placeClassInGrade('5');
        $nonce = $this->nonce('google', 'student');
        $this->forget();
        $this->appSignIn('google', $nonce, $this->idToken($claims($nonce)), 'student')->assertForbidden();

        // In Grade 9 the learner signs in to the learner app, never the teacher app.
        $this->placeClassInGrade('9');
        $nonce = $this->nonce('google', 'student');
        $this->forget();
        $plain = $this->appSignIn('google', $nonce, $this->idToken($claims($nonce)), 'student')->assertOk()->json('data.token');
        $this->assertSame(AppTokens::STUDENT, PersonalAccessToken::findToken($plain)->name);
        $nonce = $this->nonce('google');
        $this->forget();
        $this->appSignIn('google', $nonce, $this->idToken($claims($nonce)))->assertForbidden();
    }

    // ---- Linking from the apps -----------------------------------------------

    public function test_an_app_links_with_the_password_and_never_to_someone_elses_account(): void
    {
        $this->enableGoogle();
        $taraToken = AppTokens::issue($this->tara, AppTokens::TEACHER);
        $theoToken = AppTokens::issue($this->theo, AppTokens::TEACHER);

        $this->withToken($taraToken)->getJson('/api/v2/me/sign-in-links')->assertOk()
            ->assertJsonPath('data.links', [])->assertJsonPath('data.providers.0.provider', 'google')->assertJsonPath('data.providers.0.may_link', true);

        // Wrong password: refused and counted.
        $nonce = $this->linkNonce($taraToken);
        $this->linkFromApp($taraToken, $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])), 'wrong')
            ->assertStatus(422)->assertJsonPath('data.errors.password.0', 'Your password is not right.');

        // Someone else's email address: never linked to Tara's account.
        $nonce = $this->linkNonce($taraToken);
        $this->linkFromApp($taraToken, $nonce, $this->idToken($this->googleClaims('theo-sub', 'theo@example.com', ['nonce' => $nonce])))->assertForbidden();

        // Theo cannot use a nonce Tara asked for.
        $nonce = $this->linkNonce($taraToken);
        $this->linkFromApp($theoToken, $nonce, $this->idToken($this->googleClaims('theo-sub', 'theo@example.com', ['nonce' => $nonce])))->assertUnauthorized();

        // Tara links her own.
        $nonce = $this->linkNonce($taraToken);
        $this->linkFromApp($taraToken, $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))
            ->assertCreated()->assertJsonPath('data.provider', 'google')->assertJsonPath('data.email', 'tara@example.com');
        $link = SignInProviderLink::query()->sole();
        $this->assertSame('app', $link->linked_via);

        // A Google account already linked to Tara cannot be linked by Theo, even with his own email on it now.
        $nonce = $this->linkNonce($theoToken);
        $this->linkFromApp($theoToken, $nonce, $this->idToken($this->googleClaims('tara-sub', 'theo@example.com', ['nonce' => $nonce])))->assertStatus(409);
        $this->assertSame(['password_wrong', 'email_mismatch', 'nonce_invalid', 'subject_taken'], $this->refusals());

        // Theo cannot see or remove Tara's link; Tara can.
        $this->forget();
        $this->withToken($theoToken)->getJson('/api/v2/me/sign-in-links')->assertOk()->assertJsonPath('data.links', []);
        $this->forget();
        $this->withToken($theoToken)->deleteJson('/api/v2/me/sign-in-links/'.$link->id)->assertNotFound();
        $this->assertTrue(SignInProviderLink::query()->whereKey($link->id)->exists());
        $this->forget();
        $this->withToken($taraToken)->deleteJson('/api/v2/me/sign-in-links/'.$link->id)->assertOk();
        $this->assertFalse(SignInProviderLink::query()->whereKey($link->id)->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'sign_in.provider_unlinked')->exists());
    }

    public function test_a_locked_account_cannot_link_even_with_the_right_password(): void
    {
        $this->enableGoogle();
        $token = AppTokens::issue($this->tara, AppTokens::TEACHER);
        $nonce = $this->linkNonce($token);
        // Ten wrong passwords at the ordinary sign-in lock the account for the hour.
        for ($i = 0; $i < 10; $i++) {
            Auth::guard('web')->attempt(['email' => 'tara@example.com', 'password' => 'guess'.$i]);
        }
        Auth::guard('web')->logout();
        $this->forget();

        // API: the right password, a valid nonce and ID token are still refused, and nothing is linked.
        $this->linkFromApp($token, $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))
            ->assertForbidden()->assertJsonPath('message', 'Too many wrong passwords for this account. Try again later.');
        // The nonce was never used: the refusal came before anything else.
        $this->assertNull(SignInProviderChallenge::query()->where('purpose', SignInProviderChallenge::LINK)->value('consumed_at'));

        // Panel: the same, and no provider sign-in is started.
        $this->app['auth']->shouldUse('web');
        $this->actingAs($this->tara);
        $this->post('/sign-in-links/google', ['password' => 'secret123'])->assertRedirect(route('sign-in-links.index'))
            ->assertSessionHasErrors(['provider' => 'Too many wrong passwords for this account. Try again later.']);
        $this->assertSame(0, SignInProviderChallenge::query()->where('channel', SignInProviderChallenge::WEB)->count());

        $this->assertSame(0, SignInProviderLink::query()->count());
        $this->assertSame(['locked', 'locked'], $this->refusals());

        // Once the hour is up, the same password links.
        $this->travel(61)->minutes();
        $nonce = $this->linkNonce($token);
        $this->linkFromApp($token, $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertCreated();
    }

    // ---- Accounts, schools, roles and owners ---------------------------------

    public function test_no_account_is_created_or_matched_by_email_and_the_subject_decides(): void
    {
        $this->enableGoogle();
        $users = User::acrossSchools()->count();
        // Nobody linked: a verified school email alone opens nothing.
        $nonce = $this->nonce('google');
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertForbidden();
        $nonce = $this->nonce('google');
        $this->forget();
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('stranger-sub', 'new.person@example.com', ['nonce' => $nonce])))->assertForbidden();
        $this->assertSame($users, User::acrossSchools()->count());

        // Tara's Google account changes its address to Theo's: it still opens Tara's account, never Theo's.
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $nonce = $this->nonce('google');
        $this->forget();
        $plain = $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'theo@example.com', ['nonce' => $nonce])))->assertOk()->json('data.token');
        $this->assertSame((int) $this->tara->id, (int) PersonalAccessToken::findToken($plain)->tokenable_id);
        // A new Google account with Tara's address is not Tara's link.
        $nonce = $this->nonce('google');
        $this->forget();
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('impostor-sub', 'tara@example.com', ['nonce' => $nonce])))->assertForbidden();
        $this->assertSame(['not_linked', 'not_linked', 'not_linked'], $this->refusals());
    }

    public function test_every_account_check_of_a_password_sign_in_still_applies(): void
    {
        $this->enableGoogle();
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $attempt = function (): TestResponse {
            $nonce = $this->nonce('google');
            $this->forget();

            return $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])));
        };

        $this->tara->forceFill(['status' => 0])->save();
        $attempt()->assertForbidden();
        $this->tara->forceFill(['status' => 1, 'password_change_required' => true])->save();
        $attempt()->assertForbidden()->assertJsonPath('message', 'Sign in with your password first and choose a new one. Then you can use this sign-in.');
        $this->tara->forceFill(['password_change_required' => false])->save();
        // Locked by too many wrong passwords.
        for ($i = 0; $i < 10; $i++) {
            Auth::guard('web')->attempt(['email' => 'tara@example.com', 'password' => 'guess'.$i]);
        }
        $attempt()->assertForbidden();
        Cache::flush();
        // A membership that has ended.
        app(MembershipService::class)->end($this->tara, 1, 'Teacher');
        $attempt()->assertForbidden();
        $this->assertSame(['account_inactive', 'password_change_required', 'locked', 'role_not_allowed'], $this->refusals());
        $this->assertSame(0, $this->tara->tokens()->where('name', AppTokens::TEACHER)->count());
    }

    public function test_a_session_ends_when_its_link_membership_or_setting_stops_holding(): void
    {
        $this->enableGoogle();
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $signIn = function (): string {
            $nonce = $this->nonce('google');
            $this->forget();

            return (string) $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertOk()->json('data.token');
        };
        $context = function (string $plain): TestResponse {
            $this->forget();

            return $this->withToken($plain)->getJson('/api/v2/me/context');
        };

        // The school switches Google off: the next request is refused.
        $plain = $signIn();
        $context($plain)->assertOk();
        $this->setGoogle(['enabled' => false]);
        $context($plain)->assertForbidden();
        $this->setGoogle(['enabled' => true]);
        $context($plain)->assertOk();
        // The school stops allowing Teachers.
        $this->setGoogle(['staff_roles' => ['Super Admin']]);
        $context($plain)->assertForbidden();
        $this->setGoogle(['staff_roles' => null]);

        // The link is removed.
        $plain = $signIn();
        app(CurrentSchool::class)->run(1, fn () => $link->delete());
        $context($plain)->assertForbidden();

        // The membership ends.
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $plain = $signIn();
        app(MembershipService::class)->end($this->tara, 1, 'Teacher');
        app(MembershipService::class)->forget($this->tara);
        $context($plain)->assertForbidden();
    }

    public function test_a_link_made_at_one_school_never_opens_another(): void
    {
        $this->enableGoogle();
        $this->enableGoogle(2);
        // Tara also teaches at School B.
        app(CurrentSchool::class)->run(2, fn () => $this->tara->assignRole('Teacher'));
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');

        // Through School B's settings, School A's link is not found.
        $nonce = $this->nonce('google', 'teacher', 'baobab-high');
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])), 'teacher', 'baobab-high')->assertForbidden();
        // A School A nonce is not School B's either.
        $nonce = $this->nonce('google');
        $this->forget();
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])), 'teacher', 'baobab-high')->assertUnauthorized();
        $this->assertSame(['not_linked', 'nonce_invalid'], $this->refusals());

        // Signed in through School A, the token works in School A only.
        $nonce = $this->nonce('google');
        $this->forget();
        $plain = $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertOk()->json('data.token');
        $this->forget();
        $this->withToken($plain)->withHeaders(['X-School-Id' => '2'])->getJson('/api/v2/me/context')->assertForbidden();
        $this->forget();
        $this->flushHeaders()->withToken($plain)->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.current_school.id', 1);
        // A password token for the same account still reaches both schools.
        $password = AppTokens::issue($this->tara, AppTokens::TEACHER);
        $this->forget();
        $this->withToken($password)->withHeaders(['X-School-Id' => '2'])->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.current_school.id', 2);

        // A School B teacher with a School B link cannot use it through School A.
        $taraB = User::acrossSchools()->findOrFail($this->schoolB->ids['tara_user']);
        $this->linkDirectly($taraB, 'google', 'tara-b-sub', $taraB->email, '', 2);
        $nonce = $this->nonce('google');
        $this->forget();
        $this->flushHeaders()->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-b-sub', $taraB->email, ['nonce' => $nonce])))->assertForbidden();

        // School B's admin cannot see or remove School A's link.
        $adminB = $this->schoolAdmin(2);
        $this->app['auth']->shouldUse('web');
        $this->actingAs($adminB)->withSession(['school_id' => 2])->get('/sign-in-providers')->assertOk()->assertDontSee('tara@example.com');
        $this->delete('/sign-in-providers/links/'.$link->id)->assertNotFound();
        $this->assertTrue(SignInProviderLink::acrossSchools()->whereKey($link->id)->exists());
    }

    public function test_hq_staff_never_sign_in_or_link_this_way(): void
    {
        $this->enableGoogle();
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        PlatformStaffIdentity::query()->create(['user_id' => $this->tara->id]);
        $nonce = $this->nonce('google');
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertForbidden();
        $authorize = $this->authorizeQuery($this->post('/sign-in/google', ['school' => $this->slugA]));
        $this->codeTokens['code-hq'] = $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $authorize['nonce']]));
        $this->get('/sign-in/google/callback?'.http_build_query(['code' => 'code-hq', 'state' => $authorize['state']]))->assertRedirect(route('login'));
        $this->assertGuest();

        // Nor can they link: the link is refused even with their password.
        app(CurrentSchool::class)->run(1, fn () => $link->delete());
        $token = AppTokens::issue($this->tara, AppTokens::TEACHER);
        $nonce = $this->linkNonce($token);
        $this->linkFromApp($token, $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertForbidden();
        $this->assertSame(['platform_staff', 'platform_staff', 'account_refused'], $this->refusals());
        $this->assertSame(0, SignInProviderLink::query()->count());
    }

    public function test_the_wrong_role_is_refused(): void
    {
        $this->setGoogle(['staff_roles' => ['Super Admin']]);
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $nonce = $this->nonce('google');
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertForbidden();
        // Nor may a teacher who is not allowed start a link.
        $this->actingAs($this->theo);
        $this->get('/sign-in-links')->assertOk()->assertDontSee('Link a Google account');
        $this->post('/sign-in-links/google', ['password' => 'secret123'])->assertRedirect(route('sign-in-links.index'));
        // Parents never use it, whatever the school chooses.
        $farid = User::query()->findOrFail($this->schoolA->ids['farid_user']);
        $this->setGoogle(['staff_roles' => null, 'learners_allowed' => true, 'learner_stages' => ['junior', 'high_school']]);
        $this->assertSame([], app(CurrentSchool::class)->run(1, fn () => app(ProviderSettings::class)->allowedRoles(app(ProviderSettings::class)->for('google'), $farid)));
        $this->assertSame(['role_not_allowed', 'role_not_allowed'], $this->refusals());
    }

    public function test_the_wrong_owner_cannot_remove_someone_elses_link_in_the_panel(): void
    {
        $this->enableGoogle();
        $theirs = $this->linkDirectly($this->theo, 'google', 'theo-sub', 'theo@example.com');
        $this->actingAs($this->tara);
        $this->delete('/sign-in-links/'.$theirs->id)->assertNotFound();
        $this->get('/sign-in-links')->assertOk()->assertDontSee('theo@example.com');
        $this->assertTrue(SignInProviderLink::query()->whereKey($theirs->id)->exists());
        $this->actingAs($this->theo);
        $this->delete('/sign-in-links/'.$theirs->id)->assertRedirect(route('sign-in-links.index'));
        $this->assertFalse(SignInProviderLink::query()->whereKey($theirs->id)->exists());
    }

    // ---- The school's settings ----------------------------------------------

    public function test_only_the_school_admin_changes_the_settings_and_they_are_checked_and_audited(): void
    {
        $admin = $this->superAdmin();
        $form = ['enabled' => '1', 'allowed_domains' => "example.com\n@Example.org", 'require_hosted_domain' => '1', 'staff_access' => 'all', 'learner_stages' => ['high_school']];

        // A teacher cannot see or change them.
        $this->actingAs($this->tara)->get('/sign-in-providers')->assertForbidden();
        $this->put('/sign-in-providers/google', $form)->assertForbidden();
        $this->assertSame(0, SignInProviderSetting::query()->count());

        $this->actingAs($admin)->get('/sign-in-providers')->assertOk()->assertSee('Google and Microsoft sign-in')
            ->assertSee(route('sign-in-provider.callback', ['provider' => 'google']));
        // Off by default; consumer domains, a missing tenant and an unset-up provider are refused.
        $this->assertNull(app(ProviderSettings::class)->usable('google'));
        $this->put('/sign-in-providers/google', ['allowed_domains' => 'gmail.com'] + $form)->assertSessionHasErrors('allowed_domains');
        $this->put('/sign-in-providers/google', ['allowed_domains' => 'not a domain'] + $form)->assertSessionHasErrors('allowed_domains');
        $this->put('/sign-in-providers/microsoft', ['allowed_tenants' => ''] + $form)->assertSessionHasErrors('allowed_tenants');
        $this->put('/sign-in-providers/microsoft', ['allowed_tenants' => 'not-a-guid'] + $form)->assertSessionHasErrors('allowed_tenants');
        config(['sign_in_providers.providers.google.client_id' => '']);
        $this->put('/sign-in-providers/google', $form)->assertSessionHasErrors('enabled');
        config(['sign_in_providers.providers.google.client_id' => self::GOOGLE_WEB]);
        $this->assertSame(0, SignInProviderSetting::query()->count());

        $this->put('/sign-in-providers/google', $form)->assertRedirect(route('sign-in-providers.index'));
        $setting = SignInProviderSetting::query()->sole();
        $this->assertSame(['example.com', 'example.org'], $setting->allowed_domains);
        $this->assertNull($setting->staff_roles);
        $this->assertFalse($setting->learners_allowed);
        $audit = AuditLog::query()->where('action', 'sign_in.provider_settings_updated')->sole();
        $this->assertSame(1, (int) $audit->school_id);
        $this->assertStringNotContainsString('test-google-secret-placeholder', json_encode($audit->after));
        $this->assertNotNull(app(ProviderSettings::class)->usable('google'));
        // The admin removes a link at their school.
        $link = $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $this->get('/sign-in-providers')->assertSee('tara@example.com');
        $this->delete('/sign-in-providers/links/'.$link->id)->assertRedirect(route('sign-in-providers.index'));
        $this->assertSame('school_admin', AuditLog::query()->where('action', 'sign_in.provider_unlinked')->sole()->after['by']);
    }

    // ---- The providers' keys -------------------------------------------------

    public function test_the_key_set_is_cached_bounded_and_fails_closed(): void
    {
        $keys = app(ProviderKeys::class);
        $this->assertSame(['google-1', 'google-2'], array_keys($keys->keys('google', 'google-1')));
        $keys->keys('google', 'google-2');
        $this->assertSame(1, $this->jwksFetches, 'Held keys are not fetched again');
        // An unknown key id fetches once per cool-down, not on every request.
        $keys->keys('google', 'unknown');
        $this->travel(301)->seconds();
        $keys->keys('google', 'unknown');
        $keys->keys('google', 'unknown');
        $this->assertSame(2, $this->jwksFetches);

        // A copy is never kept past maximum_seconds (6 hours), whatever max-age says; then no keys means no sign-in.
        $this->travel(21601)->seconds();
        $this->jwksMode = 'down';
        try {
            $keys->keys('google', 'google-1');
            $this->fail('An expired key set must not be used');
        } catch (ProviderSignInRefused $refused) {
            $this->assertSame('unavailable', $refused->reason);
        }
        $this->enableGoogle();
        $this->linkDirectly($this->tara, 'google', 'tara-sub', 'tara@example.com');
        $nonce = $this->nonce('google');
        $this->appSignIn('google', $nonce, $this->idToken($this->googleClaims('tara-sub', 'tara@example.com', ['nonce' => $nonce])))->assertStatus(503);

        // An oversized or malformed set is refused too.
        Cache::flush();
        $this->jwksMode = 'oversized';
        $this->expectException(ProviderSignInRefused::class);
        $keys->keys('google', 'google-1');
    }

    // ---- Helpers --------------------------------------------------------------

    /** @return array{pem: string, jwk: array<string, string>} */
    private function rsaKey(string $kid): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $details = openssl_pkey_get_details($key);
        $b64 = fn (string $raw) => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        return ['pem' => $pem, 'jwk' => ['kty' => 'RSA', 'kid' => $kid, 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e'])]];
    }

    private function idToken(array $claims, string $key = 'google-1', ?string $kid = null): string
    {
        return JWT::encode($claims, self::$keys[$key]['pem'], 'RS256', $kid ?? $key);
    }

    private function googleClaims(string $sub, string $email, array $over = []): array
    {
        return $over + [
            'iss' => 'https://accounts.google.com', 'aud' => self::GOOGLE_WEB, 'sub' => $sub, 'email' => $email, 'email_verified' => true,
            'hd' => substr((string) strrchr($email, '@'), 1), 'iat' => now()->timestamp, 'exp' => now()->addHour()->timestamp, 'nonce' => 'unset',
        ];
    }

    private function microsoftClaims(string $oid, string $email, array $over = []): array
    {
        return $over + [
            'iss' => 'https://login.microsoftonline.com/'.self::TENANT.'/v2.0', 'aud' => self::MICROSOFT_CLIENT, 'tid' => self::TENANT, 'oid' => $oid,
            'sub' => 'pairwise-'.$oid, 'email' => $email, 'xms_edov' => true, 'iat' => now()->timestamp, 'exp' => now()->addHour()->timestamp, 'nonce' => 'unset',
        ];
    }

    private function tokenEndpoint(HttpRequest $request): mixed
    {
        $code = (string) ($request->data()['code'] ?? '');
        $verifier = (string) ($request->data()['code_verifier'] ?? '');
        $pkce = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        if (isset($this->usedCodes[$code]) || ! isset($this->codeTokens[$code]) || $pkce !== $this->codeChallenge
            || ($request->data()['client_secret'] ?? '') === '') {
            return Http::response(['error' => 'invalid_grant'], 400);
        }
        $this->usedCodes[$code] = true;

        return Http::response(['id_token' => $this->codeTokens[$code], 'access_token' => 'unused', 'token_type' => 'Bearer']);
    }

    /** @return array<string, string> the provider authorize address's query, from a start redirect */
    private function authorizeQuery(TestResponse $start): array
    {
        $start->assertRedirect();
        parse_str((string) parse_url((string) $start->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->codeChallenge = $query['code_challenge'] ?? null;

        return $query;
    }

    private function callbackFor(TestResponse $start, array $claims, string $code): TestResponse
    {
        $authorize = $this->authorizeQuery($start);
        $this->codeTokens[$code] = $this->idToken(['nonce' => $authorize['nonce']] + $claims);

        return $this->get('/sign-in/google/callback?'.http_build_query(['code' => $code, 'state' => $authorize['state']]));
    }

    private function nonce(string $provider, string $app = 'teacher', ?string $school = null): string
    {
        $this->forget();
        $school ??= $this->slugA;

        return (string) $this->postJson('/api/v2/auth/providers/'.$provider.'/nonce', ['school' => $school, 'app' => $app])->assertOk()->json('data.nonce');
    }

    private function appSignIn(string $provider, string $nonce, string $jwt, string $app = 'teacher', ?string $school = null): TestResponse
    {
        $school ??= $this->slugA;

        return $this->postJson('/api/v2/auth/providers/'.$provider.'/sign-in', ['school' => $school, 'app' => $app, 'id_token' => $jwt, 'nonce' => $nonce]);
    }

    private function linkNonce(string $token): string
    {
        $this->forget();

        return (string) $this->withToken($token)->postJson('/api/v2/me/sign-in-links/providers/google/nonce')->assertOk()->json('data.nonce');
    }

    private function linkFromApp(string $token, string $nonce, string $jwt, string $password = 'secret123'): TestResponse
    {
        $this->forget();

        return $this->withToken($token)->postJson('/api/v2/me/sign-in-links/providers/google', ['id_token' => $jwt, 'nonce' => $nonce, 'password' => $password]);
    }

    private function linkDirectly(User $user, string $provider, string $subject, string $email, string $tenant = '', int $school = 1): SignInProviderLink
    {
        return app(CurrentSchool::class)->run($school, fn () => SignInProviderLink::query()->create([
            'user_id' => $user->id, 'provider' => $provider, 'tenant_id' => $tenant, 'subject' => $subject, 'email_at_link' => $email, 'linked_via' => 'panel',
        ]));
    }

    private function enableGoogle(int $school = 1): void
    {
        $this->setGoogle(['enabled' => true], $school);
    }

    private function setGoogle(array $values, int $school = 1): void
    {
        app(CurrentSchool::class)->run($school, function () use ($values) {
            $setting = SignInProviderSetting::query()->firstOrNew(['provider' => 'google']);
            if (! $setting->exists) {
                $setting->fill(['enabled' => true, 'allowed_domains' => ['example.com'], 'require_hosted_domain' => true, 'staff_roles' => null, 'learners_allowed' => false, 'learner_stages' => ['high_school']]);
            }
            $setting->fill($values)->save();
        });
    }

    private function enableMicrosoft(): void
    {
        app(CurrentSchool::class)->run(1, fn () => SignInProviderSetting::query()->create([
            'provider' => 'microsoft', 'enabled' => true, 'allowed_domains' => [], 'allowed_tenants' => [self::TENANT], 'require_hosted_domain' => false,
            'staff_roles' => null, 'learners_allowed' => false, 'learner_stages' => ['high_school'],
        ]));
    }

    private function placeClassInGrade(string $code): void
    {
        ClassSchool::query()->whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => GradeLevel::query()->where('code', $code)->value('id')]);
    }

    private function schoolAdmin(int $school): User
    {
        return app(CurrentSchool::class)->run($school, function () {
            $user = User::query()->forceCreate(['first_name' => 'Bongi', 'last_name' => 'Baobab Admin', 'email' => 'bongi.baobab@example.com', 'password' => bcrypt('secret123'), 'mobile' => '0123456789', 'gender' => 'Female']);
            $user->assignRole(Role::query()->where('name', 'Super Admin')->firstOrFail());

            return $user;
        });
    }

    /** @return list<string> the reasons of the refusals audited so far, oldest first */
    private function refusals(): array
    {
        return AuditLog::query()->where('action', 'sign_in.provider_refused')->orderBy('id')->get()->map(fn (AuditLog $row) => (string) $row->after['reason'])->all();
    }

    private function signOut(): void
    {
        Auth::guard('web')->logout();
        $this->flushSession();
        $this->forget();
    }

    private function forget(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}
