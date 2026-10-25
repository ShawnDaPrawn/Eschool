<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdmissionsFixtureEnvironmentTest extends TestCase
{
    public function test_fixture_guard_allows_reserved_ci_database_without_allowing_production_data(): void
    {
        $safe = require dirname(__DIR__, 2).'/e2e/support/admissions-environment.php';
        $this->assertTrue($safe('local', 'eschool_gallery_test', false));
        $this->assertTrue($safe('testing', 'eschool_e2e', true));
        $this->assertTrue($safe('local', 'eschool_e2e', true));
        $this->assertFalse($safe('local', 'eschool_e2e', false));
        $this->assertFalse($safe('local', 'eschool_live', true));
        $this->assertFalse($safe('production', 'eschool_e2e', true));
        $this->assertFalse($safe('production', 'eschool_gallery_test', true));
    }
}
