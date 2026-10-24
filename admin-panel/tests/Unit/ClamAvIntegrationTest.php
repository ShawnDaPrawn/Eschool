<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Files\ClamAv;
use Tests\TestCase;

class ClamAvIntegrationTest extends TestCase
{
    public function test_real_daemon_accepts_clean_bytes_and_rejects_the_standard_eicar_fixture(): void
    {
        if (getenv('CONTENT_CLAMAV_INTEGRATION') !== 'true') {
            $this->markTestSkipped('Real private clamd is configured by the CI integration step.');
        }
        config(['academic_content.clamav_host' => '127.0.0.1', 'academic_content.clamav_port' => 3310]);
        $scanner = app(ClamAv::class);
        $this->assertTrue($scanner->scan("# Fractions\n\nOne half is two quarters.")['clean']);
        $eicar = base64_decode('WDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNULUZJTEUhJEgrSCo=', true);
        $result = $scanner->scan($eicar);
        $this->assertFalse($result['clean']);
        $this->assertNotEmpty($result['signature']);
    }
}
