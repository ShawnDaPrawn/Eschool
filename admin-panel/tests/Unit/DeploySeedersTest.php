<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The container seeds the database on start (docker/entrypoint.sh and
 * docker/provision-admin.php). Class names are matched case-sensitively when
 * they are autoloaded on Linux, so a seeder named in the wrong case
 * (provision-admin.php once called "emailseeder") failed every fresh install.
 */
class DeploySeedersTest extends TestCase
{
    public function test_every_seeder_the_container_runs_exists_under_that_exact_name(): void
    {
        $root = dirname(__DIR__, 2);
        $seeders = [];
        preg_match_all("/'Database\\\\\\\\Seeders\\\\\\\\(\\w+)'/", (string) file_get_contents($root.'/docker/provision-admin.php'), $matches);
        $seeders = array_merge($seeders, $matches[1]);
        preg_match_all('/--class=(\w+)/', (string) file_get_contents($root.'/docker/entrypoint.sh'), $matches);
        $seeders = array_merge($seeders, $matches[1]);

        $this->assertContains('EmailSeeder', $seeders);
        $this->assertContains('InstallationSeeder', $seeders);
        $files = array_map('basename', glob($root.'/database/seeders/*.php'));
        foreach (array_unique($seeders) as $seeder) {
            $this->assertContains($seeder.'.php', $files, "No seeder class named exactly $seeder");
        }
    }
}
