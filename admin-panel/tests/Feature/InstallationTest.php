<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRequestIntegrity;
use App\Models\Settings;
use App\Services\SystemIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_seeders_create_the_super_admin(): void
    {
        $this->seedLikeInstaller();

        $admin = $this->superAdmin();
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertSame('3.5.0', Settings::where('type', 'system_version')->value('message'));
    }

    public function test_installer_seed_commands_run_in_production(): void
    {
        // Without --force, db:seed is cancelled when APP_ENV=production and the
        // installer finishes without creating the super admin.
        foreach (config('installer.commands') as $command) {
            if (str_starts_with($command, 'db:seed')) {
                $this->assertStringContainsString('--force', $command);
            }
        }
        $this->assertContains('key:generate --force', config('installer.commands'));
    }

    public function test_old_licence_system_is_gone(): void
    {
        // The installer has exactly these steps, with no licence step between them.
        $installRoutes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn ($uri) => $uri === 'install' || str_starts_with($uri, 'install/'))
            ->unique()->sort()->values()->all();
        $this->assertSame(
            ['install', 'install/database', 'install/finish', 'install/folders', 'install/keys', 'install/migrations', 'install/server'],
            $installRoutes,
        );
        $this->assertFalse(class_exists(SystemIntegrityService::class));
        $this->assertFalse(class_exists(EnsureRequestIntegrity::class));
        $this->assertFalse(Schema::hasTable('service_health_logs'));
    }

    public function test_env_example_defaults_are_safe_for_production(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $env);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $env);
        // A published key would let anyone forge sessions and signed URLs.
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $env);
        $this->assertNotNull(config('sanctum.expiration'));
    }
}
