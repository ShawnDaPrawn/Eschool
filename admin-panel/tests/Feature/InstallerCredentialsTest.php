<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AddSuperAdminSeeder;
use Database\Seeders\InstallationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstallerCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InstallationSeeder::class);
        // Exercise the production installation path directly.
        $this->app->instance('env', 'production');
    }

    public function test_installation_requires_operator_configured_credentials(): void
    {
        config(['installer.admin_area.user' => ['email' => '', 'password' => '']]);
        foreach (['testing', 'production'] as $environment) {
            $this->app->instance('env', $environment);
            try {
                $this->app->make(AddSuperAdminSeeder::class)->run();
                $this->fail('Missing administrator credentials were accepted.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('email', $e->errors());
                $this->assertArrayHasKey('password', $e->errors());
                $this->assertSame(0, User::count());
            }
        }
    }

    public function test_the_historical_public_password_cannot_be_configured(): void
    {
        config(['installer.admin_area.user' => ['email' => 'admin@example.com', 'password' => 'superadmin']]);
        $this->expectException(ValidationException::class);
        $this->app->make(AddSuperAdminSeeder::class)->run();
    }

    public function test_installation_uses_the_chosen_credentials_and_hashes_the_password(): void
    {
        config(['installer.admin_area.user' => ['email' => 'admin@example.com', 'password' => 'Configured-admin-password-26']]);
        $this->app->make(AddSuperAdminSeeder::class)->run();

        $admin = User::findOrFail(1);
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue(Hash::check('Configured-admin-password-26', $admin->password));
        $this->assertTrue($admin->hasRole('Super Admin'));
    }
}
