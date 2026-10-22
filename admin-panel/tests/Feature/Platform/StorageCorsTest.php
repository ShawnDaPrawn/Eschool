<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\StorageCors;
use Tests\TestCase;

/**
 * The origins allowed to fetch uploaded files under /storage, which the web
 * server answers itself (docker/storage-cors.conf, server.php; defect D2).
 * docker/tests/storage-cors.sh runs the same expression in a real Apache.
 */
class StorageCorsTest extends TestCase
{
    public function test_the_portals_and_the_panel_are_allowed_exactly(): void
    {
        $pattern = StorageCors::pattern(['https://admin.navigateed.co.za', 'https://app.navigateed.co.za', 'https://teach.navigateed.co.za'], []);

        foreach (['https://admin.navigateed.co.za', 'https://app.navigateed.co.za', 'https://teach.navigateed.co.za'] as $origin) {
            $this->assertTrue(StorageCors::allows($pattern, $origin), $origin);
            $this->assertTrue(StorageCors::allows($pattern, $origin, 'HEAD'), "HEAD from $origin");
        }
        foreach ([
            'https://evil.example', 'https://app.navigateed.co.za.evil.example', 'https://evilapp.navigateed.co.za',
            'http://app.navigateed.co.za', 'https://app.navigateed.co.za:8443', 'https://appXnavigateed.co.za', 'null', '',
        ] as $origin) {
            $this->assertFalse(StorageCors::allows($pattern, $origin), "'$origin'");
        }
    }

    public function test_only_reads_get_the_header(): void
    {
        $pattern = StorageCors::pattern(['https://app.navigateed.co.za'], []);

        foreach (['POST', 'PUT', 'DELETE', 'OPTIONS'] as $method) {
            $this->assertFalse(StorageCors::allows($pattern, 'https://app.navigateed.co.za', $method), $method);
        }
    }

    public function test_patterns_from_config_cors_are_kept(): void
    {
        $pattern = StorageCors::pattern(['http://127.0.0.1:8000'], ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', '#^https://[a-z]+\.example\.org$#i']);

        $this->assertTrue(StorageCors::allows($pattern, 'http://127.0.0.1:8101'));
        $this->assertTrue(StorageCors::allows($pattern, 'http://localhost:53771'));
        $this->assertTrue(StorageCors::allows($pattern, 'https://PORTAL.example.org'), 'the i flag is kept');
        $this->assertFalse(StorageCors::allows($pattern, 'http://localhost.evil.example'));
        $this->assertFalse(StorageCors::allows($pattern, 'https://portal.example.org.evil.example'));
    }

    public function test_nothing_allowed_matches_no_origin(): void
    {
        $this->assertSame(StorageCors::NOTHING, StorageCors::pattern([], []));
        $this->assertSame(StorageCors::NOTHING, StorageCors::pattern(['*', ' '], ['not a pattern']));
        $this->assertFalse(StorageCors::allows(StorageCors::NOTHING, 'https://app.navigateed.co.za'));
    }

    public function test_the_expression_follows_config_cors(): void
    {
        config([
            'cors.allowed_origins' => ['https://admin.navigateed.co.za', 'https://app.navigateed.co.za'],
            'cors.allowed_origins_patterns' => [],
        ]);

        $pattern = StorageCors::fromConfig();

        $this->assertTrue(StorageCors::allows($pattern, 'https://app.navigateed.co.za'));
        $this->assertFalse(StorageCors::allows($pattern, 'https://teach.navigateed.co.za'));
    }
}
