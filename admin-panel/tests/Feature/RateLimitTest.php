<?php

namespace Tests\Feature;

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

    public function test_too_many_requests_from_one_address_are_refused(): void
    {
        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(3)->by('web-ip:'.$request->ip()));

        for ($i = 0; $i < 3; $i++) {
            $this->get('/_client-ip')->assertOk();
        }
        $this->get('/_client-ip')->assertStatus(429);
    }
}
