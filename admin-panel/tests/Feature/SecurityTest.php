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

    public function test_there_is_no_public_sign_up(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    }

    public function test_users_cannot_be_created_outside_the_school_screens(): void
    {
        // The removed tutorial user manager let any logged-in account create a Super Admin.
        $this->actingAs($this->teacher())->post('/users', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password123',
            'confirm-password' => 'password123',
            'roles' => ['Super Admin'],
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
        $this->assertSame(1, User::role('Super Admin')->count());
    }

    public function test_accounts_without_a_role_cannot_log_in(): void
    {
        User::create([
            'first_name' => 'No',
            'last_name' => 'Role',
            'email' => 'no.role@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);

        $this->post('/login', ['email' => 'no.role@example.com', 'password' => 'secret123'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();

        $this->post('/login', ['email' => $this->teacher()->email, 'password' => 'secret123'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_logged_in_user_can_clear_the_cache(): void
    {
        $this->actingAs($this->superAdmin())->get('/clear')->assertRedirect();
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
