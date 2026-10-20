<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const MAINTENANCE_URLS = ['clear', 'storage-link', 'migrate', 'seeder_install'];

    private const SUPER_ADMIN_URLS = ['storage-link', 'migrate', 'seeder_install'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_maintenance_urls_redirect_guests_to_login(): void
    {
        foreach (self::MAINTENANCE_URLS as $url) {
            $this->get('/'.$url)->assertRedirect(route('login'));
        }
    }

    public function test_maintenance_urls_are_forbidden_for_other_roles(): void
    {
        $teacher = $this->teacher();

        foreach (self::SUPER_ADMIN_URLS as $url) {
            $this->actingAs($teacher)->get('/'.$url)->assertForbidden();
        }
    }

    public function test_logged_in_user_can_clear_the_cache(): void
    {
        $this->actingAs($this->superAdmin())->get('/clear')->assertRedirect();
    }

    public function test_other_roles_cannot_create_accounts_through_users(): void
    {
        // /users was leftover demo code with no permission checks, so any
        // logged-in user could create a Super Admin account.
        $this->actingAs($this->teacher())->post('/users', [
            'name' => 'Test',
            'email' => 'new.admin@example.com',
            'password' => 'secret123',
            'confirm-password' => 'secret123',
            'roles' => ['Super Admin'],
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'new.admin@example.com']);
    }

    public function test_expired_app_tokens_are_rejected(): void
    {
        $teacher = $this->teacher();

        $expired = $teacher->createToken('test');
        $expired->accessToken->forceFill(['created_at' => now()->subMinutes(config('sanctum.expiration') + 1)])->save();
        $this->withToken($expired->plainTextToken)->getJson('/api/teacher/classes')->assertUnauthorized();

        // Checked at the guard: API controllers exit() after responding, which
        // would end the test run (see AdminPagesTest).
        $fresh = $teacher->createToken('test');
        $this->assertTrue($teacher->is($this->sanctumUser($fresh->plainTextToken)));
        $this->assertNull($this->sanctumUser($expired->plainTextToken));
    }

    public function test_protected_api_requires_a_token(): void
    {
        $this->getJson('/api/teacher/classes')->assertUnauthorized();
    }

    private function sanctumUser(string $token): ?User
    {
        $this->app['auth']->forgetGuards();

        return $this->app['auth']->guard('sanctum')
            ->setRequest(Request::create('/api/teacher/classes', 'GET', server: ['HTTP_AUTHORIZATION' => "Bearer $token"]))
            ->user();
    }

    private function teacher(): User
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.test@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);

        return $teacher;
    }
}
