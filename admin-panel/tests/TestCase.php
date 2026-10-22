<?php

namespace Tests;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware;
use Database\Seeders\AddSuperAdminSeeder;
use Database\Seeders\EmailSeeder;
use Database\Seeders\InstallationSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Passwords the faked Have I Been Pwned range API reports as found in a
     * breach. Every other password is reported clean.
     *
     * @var list<string>
     */
    protected array $breachedPasswords = [];

    protected function setUp(): void
    {
        parent::setUp();

        // The installer redirects every page to /install until storage/installed
        // exists; tests run against an already-installed app.
        $this->withoutMiddleware(ToInstallMiddleware::class);

        $this->fakePasswordBreachCheck();

        // Test code outside a request works in school 1, as a single-school
        // install's console does; each request chooses its own school
        // (school.context) and this comes back once it has finished.
        $this->app->make(CurrentSchool::class)->set(CurrentSchool::DEFAULT_ID);
    }

    /**
     * The password rule asks api.pwnedpasswords.com about every new password;
     * tests answer for it instead of calling the internet. Only that host is
     * faked, so other HTTP calls behave as before.
     */
    private function fakePasswordBreachCheck(): void
    {
        Http::fake(['api.pwnedpasswords.com/range/*' => function (HttpRequest $request) {
            $prefix = strtoupper(substr($request->url(), -5));
            $lines = [];
            foreach ($this->breachedPasswords as $password) {
                $hash = strtoupper(sha1($password));
                if (str_starts_with($hash, $prefix)) {
                    $lines[] = substr($hash, 5).':42';
                }
            }
            // The real API pads every answer with zero-count lines.
            $lines[] = str_repeat('0', 35).':0';

            return Http::response(implode("\r\n", $lines));
        }]);
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
