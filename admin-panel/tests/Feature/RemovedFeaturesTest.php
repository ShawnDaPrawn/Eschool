<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Features the Build Bible removes in Phase 0 stay gone: their old URLs answer
 * 404 for everyone, the Super Admin included.
 */
class RemovedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    /** The in-panel System Update installed an uploaded zip over the app's code. */
    public function test_system_update_is_gone(): void
    {
        foreach ([null, $this->superAdmin()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $this->get('/system-update')->assertNotFound();
            $this->post('/system-update')->assertNotFound();
        }

        $this->assertFalse(Route::has('system-update.index'));
        $this->assertFalse(Route::has('system-update.update'));
    }

    /** Storage link, migrations and the installer seeder run at deploy time (docker/entrypoint.sh). */
    public function test_maintenance_get_urls_are_gone(): void
    {
        foreach ([null, $this->teacher(), $this->superAdmin()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            foreach (['storage-link', 'migrate', 'seeder_install', 'rollback'] as $url) {
                $this->get('/'.$url)->assertNotFound();
            }
        }
    }

    /** Routes come from withRouting() in bootstrap/app.php only; RouteServiceProvider loaded them a second time. */
    public function test_routes_are_registered_once(): void
    {
        $provider = $this->app->getProvider(RouteServiceProvider::class);

        $this->assertNotNull($provider);
        $this->assertNull((fn () => $this->loadRoutesUsing)->call($provider));
        $this->assertFalse(method_exists($provider, 'map'));
    }

    private function teacher(): User
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.removed@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);

        return $teacher;
    }
}
