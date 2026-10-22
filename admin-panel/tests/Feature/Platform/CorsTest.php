<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Browsers may call the API only from the panel and the web portals. The
 * native apps send no Origin header and are not affected.
 */
class CorsTest extends TestCase
{
    use RefreshDatabase;

    private const PORTAL = 'https://app.navigateed.co.za';

    private const TEACHER_PORTAL = 'https://teach.navigateed.co.za';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        // What a production install loads.
        config(['cors' => $this->configFile('cors', ['APP_ENV' => 'production', 'APP_URL' => 'https://admin.navigateed.co.za'])]);
    }

    public function test_production_allows_the_panel_and_the_portals_only(): void
    {
        $cors = $this->configFile('cors', ['APP_ENV' => 'production', 'APP_URL' => 'https://admin.navigateed.co.za']);

        $this->assertSame(['https://admin.navigateed.co.za', self::PORTAL, self::TEACHER_PORTAL], $cors['allowed_origins']);
        $this->assertSame([], $cors['allowed_origins_patterns']);
        $this->assertTrue($cors['supports_credentials']);
    }

    public function test_staging_allows_the_staging_portals(): void
    {
        $cors = $this->configFile('cors', ['APP_ENV' => 'production', 'APP_URL' => 'https://staging-admin.navigateed.co.za']);

        $this->assertSame([
            'https://staging-admin.navigateed.co.za', 'https://staging-app.navigateed.co.za', 'https://staging-teach.navigateed.co.za',
        ], $cors['allowed_origins']);
    }

    public function test_cors_allowed_origins_replaces_the_portal_defaults(): void
    {
        $cors = $this->configFile('cors', [
            'APP_ENV' => 'production', 'APP_URL' => 'https://school.example/panel',
            'CORS_ALLOWED_ORIGINS' => 'https://learn.school.example, https://staff.school.example:8443/',
        ]);

        $this->assertSame(['https://school.example', 'https://learn.school.example', 'https://staff.school.example:8443'], $cors['allowed_origins']);
    }

    public function test_local_installs_allow_localhost_on_any_port(): void
    {
        $cors = $this->configFile('cors', ['APP_ENV' => 'local', 'APP_URL' => 'http://localhost:8000']);

        $this->assertSame(['http://localhost:8000'], $cors['allowed_origins']);
        $this->assertMatchesRegularExpression($cors['allowed_origins_patterns'][0], 'http://localhost:53771');
        $this->assertDoesNotMatchRegularExpression($cors['allowed_origins_patterns'][0], 'http://localhost.evil.example');
    }

    public function test_the_portals_are_sanctum_stateful_domains(): void
    {
        $sanctum = $this->configFile('sanctum', ['APP_ENV' => 'production']);

        $this->assertContains('app.navigateed.co.za', $sanctum['stateful']);
        $this->assertContains('teach.navigateed.co.za', $sanctum['stateful']);
        $this->assertContains('admin.navigateed.co.za', $sanctum['stateful']);
        $this->assertNotContains('localhost:*', $sanctum['stateful']);
    }

    public function test_a_portal_origin_gets_the_cors_headers(): void
    {
        foreach ([self::PORTAL, self::TEACHER_PORTAL] as $portal) {
            $this->getJson('/api/settings?type=app_settings', ['Origin' => $portal])
                ->assertOk()
                ->assertHeader('Access-Control-Allow-Origin', $portal)
                ->assertHeader('Access-Control-Allow-Credentials', 'true');

            $this->call('OPTIONS', '/api/teacher/login', server: [
                'HTTP_ORIGIN' => $portal, 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', $portal);
        }
    }

    public function test_an_unknown_origin_gets_no_cors_headers(): void
    {
        foreach (['https://evil.example', 'https://app.navigateed.co.za.evil.example', 'http://app.navigateed.co.za', 'null'] as $origin) {
            $response = $this->getJson('/api/settings?type=app_settings', ['Origin' => $origin]);
            $response->assertHeaderMissing('Access-Control-Allow-Origin');
            $response->assertHeaderMissing('Access-Control-Allow-Credentials');

            $this->call('OPTIONS', '/api/teacher/login', server: [
                'HTTP_ORIGIN' => $origin, 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            ])->assertHeaderMissing('Access-Control-Allow-Origin');
        }
    }

    public function test_the_native_apps_send_no_origin_and_keep_working(): void
    {
        $this->getJson('/api/settings?type=app_settings')
            ->assertOk()
            ->assertJson(['error' => false])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * Loads a config file as it would be with the given environment.
     *
     * @param  array<string, string>  $env
     */
    private function configFile(string $name, array $env): array
    {
        $env += ['CORS_ALLOWED_ORIGINS' => null, 'SANCTUM_STATEFUL_DOMAINS' => null];
        $saved = [];
        foreach ($env as $key => $value) {
            $saved[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            $this->setEnv($key, $value);
        }

        try {
            return require base_path("config/$name.php");
        } finally {
            foreach ($saved as $key => [$env, $server, $process]) {
                $this->setEnv($key, $env ?? $server ?? ($process === false ? null : $process));
            }
        }
    }

    private function setEnv(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);

            return;
        }
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}
