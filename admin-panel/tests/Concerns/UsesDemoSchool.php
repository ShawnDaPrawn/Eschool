<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DemoSchool;

/**
 * One DemoSchool per test class, for in-process API tests.
 *
 * DemoSchool's ids assume a freshly migrated database (it can't be built
 * inside RefreshDatabase's transaction once auto-increments have moved on), so
 * the first test of the class migrates, seeds like the installer and builds
 * the school for real. Each test then runs inside a transaction that is
 * rolled back, so what one test changes the next never sees. After the class
 * the next RefreshDatabase test migrates again, from an empty database.
 *
 * Call setUpDemoSchool() in setUp(), after parent::setUp().
 */
trait UsesDemoSchool
{
    private static ?DemoSchool $demoSchool = null;

    protected DemoSchool $school;

    protected function setUpDemoSchool(): void
    {
        if (self::$demoSchool === null) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->seedLikeInstaller();
            self::$demoSchool = DemoSchool::build();
        }
        $this->school = self::$demoSchool;

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
    }

    public static function tearDownAfterClass(): void
    {
        self::$demoSchool = null;
        RefreshDatabaseState::$migrated = false;

        parent::tearDownAfterClass();
    }
}
