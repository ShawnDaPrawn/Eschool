<?php

namespace Tests;

use App\Models\User;
use dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware;
use Database\Seeders\AddSuperAdminSeeder;
use Database\Seeders\EmailSeeder;
use Database\Seeders\InstallationSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The installer redirects every page to /install until storage/installed
        // exists; tests run against an already-installed app.
        $this->withoutMiddleware(ToInstallMiddleware::class);
    }

    /**
     * Tests drop every table, so never let them touch a real database. This runs
     * before any trait (RefreshDatabase) can connect.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $database = (string) $config->get('database.connections.'.$config->get('database.default').'.database');
        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException("Refusing to run tests against database '$database'; use a database whose name ends in _test.");
        }

        return $app;
    }

    /**
     * AuthenticateSession signs a session out when its user's password no longer
     * matches the one it remembered, and the session lasts the whole test. A test
     * that switches users isn't someone else's stale session.
     */
    public function be($user, $guard = null)
    {
        $this->app['session.store']->forget('password_hash_'.($guard ?? config('auth.defaults.guard')));

        return parent::be($user, $guard);
    }

    /**
     * Seed the database the same way the web installer does (config/installer.php).
     */
    protected function seedLikeInstaller(): void
    {
        $this->seed([InstallationSeeder::class, AddSuperAdminSeeder::class, EmailSeeder::class]);
    }

    protected function superAdmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }
}
