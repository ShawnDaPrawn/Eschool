<?php

namespace Tests\Feature;

use App\Http\Middleware\LimitRequestInput;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Request limits and the client address they are counted against.
 */
class RateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_client-ip', fn (Request $request) => $request->ip());
        Route::middleware('api')->match(['get', 'post'], 'api/_page', fn (Request $request) => response()->json([
            'limit' => $request->input('limit'),
            'offset' => $request->input('offset'),
            'get_limit' => $_GET['limit'] ?? null,
        ]));
    }

    public function test_a_visitor_cannot_choose_their_address_with_forwarded_headers(): void
    {
        // Not behind a known proxy: X-Forwarded-For is the client's own claim.
        $this->withHeaders(['X-Forwarded-For' => '203.0.113.9'])->get('/_client-ip')->assertSee('127.0.0.1');
    }

    public function test_configured_proxies_are_believed(): void
    {
        config(['trustedproxy.proxies' => '127.0.0.1']);

        $this->withHeaders(['X-Forwarded-For' => '203.0.113.9'])->get('/_client-ip')->assertSee('203.0.113.9');
    }

    public function test_cloud_run_trusts_its_front_end_and_shares_rate_limit_counters(): void
    {
        $this->assertNull((require config_path('trustedproxy.php'))['proxies']);
        $this->assertNull((require config_path('cache.php'))['limiter']);

        $_SERVER['K_SERVICE'] = 'navigateed-admin';
        try {
            $this->assertSame('*', (require config_path('trustedproxy.php'))['proxies']);
            $this->assertSame('database', (require config_path('cache.php'))['limiter']);
        } finally {
            unset($_SERVER['K_SERVICE']);
        }
    }

    public function test_web_requests_are_rate_limited_per_account_or_address(): void
    {
        $guest = RateLimiter::limiter('web')(Request::create('/login'));
        $this->assertSame(300, $guest->maxAttempts);

        $webhook = RateLimiter::limiter('web')(Request::create('/webhook/stripe', 'POST'));
        $this->assertSame(PHP_INT_MAX, $webhook->maxAttempts);

        $this->assertContains('throttle:web', app('router')->getMiddlewareGroups()['web']);
    }

    public function test_page_sizes_and_offsets_are_kept_in_range(): void
    {
        // List endpoints pass these straight to the query; a negative limit meant no LIMIT at all.
        $_GET = ['limit' => '100000000', 'offset' => '-5'];
        try {
            $this->getJson('/api/_page?limit=100000000&offset=-5')
                ->assertExactJson(['limit' => '1000', 'offset' => '0', 'get_limit' => '1000']);
        } finally {
            $_GET = [];
        }

        $this->getJson('/api/_page?limit=-1')->assertJson(['limit' => '1']);
        $this->getJson('/api/_page?limit[]=5')->assertJson(['limit' => null]);
        $this->postJson('/api/_page', ['limit' => 25])->assertJson(['limit' => '25']);
    }

    public function test_large_json_bodies_are_refused_before_they_are_parsed(): void
    {
        $body = json_encode(['answers_data' => array_fill(0, 200000, ['question_id' => 1])]);
        $this->assertGreaterThan(LimitRequestInput::MAX_JSON_BYTES, strlen($body));

        $this->call('POST', '/api/_page', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'CONTENT_LENGTH' => strlen($body)], $body)
            ->assertStatus(413);
    }

    public function test_pdf_and_import_routes_have_their_own_tighter_limit(): void
    {
        $app = RateLimiter::limiter('heavy')(Request::create('/api/student/id-card-pdf'));
        $this->assertSame(10, $app->maxAttempts);
        $this->assertSame(30, RateLimiter::limiter('heavy')(Request::create('/generate-result/1'))->maxAttempts);

        foreach (['api/student/id-card-pdf', 'api/parent/fees-paid-receipt-pdf', 'api/teacher/get-student-result-pdf'] as $uri) {
            $route = Route::getRoutes()->match(Request::create('/'.$uri));
            $this->assertContains('throttle:heavy', $route->gatherMiddleware(), $uri);
        }
        $route = Route::getRoutes()->match(Request::create('/students/store_bulk', 'POST'));
        $this->assertContains('throttle:heavy', $route->gatherMiddleware());
    }

    public function test_too_many_requests_from_one_address_are_refused(): void
    {
        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(3)->by('web-ip:'.$request->ip()));

        for ($i = 0; $i < 3; $i++) {
            $this->get('/_client-ip')->assertOk();
        }
        $this->get('/_client-ip')->assertStatus(429);
    }
}
