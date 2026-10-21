<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckChild;
use App\Models\Parents;
use App\Models\Staff;
use App\Models\Students;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const MAINTENANCE_URLS = ['storage-link', 'migrate', 'seeder_install'];

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
        $this->post('/clear')->assertRedirect(route('login'));
    }

    public function test_maintenance_urls_are_forbidden_for_other_roles(): void
    {
        $teacher = $this->teacher();

        foreach (self::MAINTENANCE_URLS as $url) {
            $this->actingAs($teacher)->get('/'.$url)->assertForbidden();
        }
        // Clearing the cache can reset rate limits.
        $this->actingAs($teacher)->post('/clear')->assertForbidden();
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

    public function test_super_admin_can_clear_the_cache_with_a_form_post(): void
    {
        // Not a link, which another site could send a signed-in user to.
        $this->actingAs($this->superAdmin())->get('/clear')->assertStatus(405);
        $this->actingAs($this->superAdmin())->from('/home')->post('/clear')->assertRedirect('/home');
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

    public function test_public_registration_is_gone(): void
    {
        // Anyone could sign up here and was logged straight into the admin panel.
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Test',
            'email' => 'signup@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'signup@example.com']);
    }

    public function test_only_staff_accounts_can_use_the_panel(): void
    {
        $noRole = $this->user('no.role@example.com');
        $this->actingAs($noRole)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();

        $student = $this->user('student.test@example.com');
        $student->assignRole('Student');
        $this->actingAs($student)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_teacher_api_needs_a_teacher_account(): void
    {
        $student = $this->user('student.test@example.com');
        $student->assignRole('Student');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/teacher/classes')
            ->assertForbidden();
    }

    public function test_child_check_only_accepts_the_parents_own_children(): void
    {
        $parent = Parents::forceCreate([
            'user_id' => $this->user('parent.test@example.com')->id,
            'first_name' => 'Test', 'last_name' => 'Parent', 'gender' => 'Female',
            'mobile' => '0000000000', 'occupation' => 'Test',
        ]);
        $ownChild = $this->student('own.child@example.com', ['mother_id' => $parent->id]);
        $otherChild = $this->student('other.child@example.com');

        $this->assertTrue($parent->hasChild($ownChild->id));
        $this->assertFalse($parent->hasChild($otherChild->id));

        // children() is a UNION, so the old where() only filtered the father part
        // and a mother passed the check for any child_id.
        $passes = function (int $childId) use ($parent) {
            $request = Request::create('/api/parent/subjects', 'GET', ['child_id' => $childId]);
            $request->setUserResolver(fn () => $parent->user);

            return (new CheckChild)->handle($request, fn () => 'passed') === 'passed';
        };
        $this->assertTrue($passes($ownChild->id));
        $this->assertFalse($passes($otherChild->id));
    }

    public function test_request_log_hides_credentials(): void
    {
        Log::spy();
        $request = Request::create('/api/change-password', 'POST', [
            'current_password' => 'old-secret', 'new_password' => 'new-secret', 'name' => 'visible',
        ], server: ['HTTP_AUTHORIZATION' => 'Bearer token-secret']);
        $this->app->instance('request', $request);

        ResponseService::logCurlRequest();
        ResponseService::logErrorResponse(new \Exception('Test error'));

        $logged = '';
        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context = []) use (&$logged) {
            $logged .= $message.json_encode($context);

            return true;
        });
        $this->assertStringContainsString('visible', $logged);
        foreach (['old-secret', 'new-secret', 'token-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
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

    public function test_an_expired_app_token_gets_a_401_even_without_an_accept_header(): void
    {
        // The apps don't send Accept: application/json and only sign out on 401; a
        // redirect to the login page left them showing an error instead.
        $this->get('/api/teacher/classes', ['Authorization' => 'Bearer 1|expired-or-unknown'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_the_student_password_reset_only_resets_students_to_their_own_date_of_birth(): void
    {
        $clerk = $this->user('clerk@example.com');
        $clerk->assignRole(Role::create(['name' => 'Clerk'])->givePermissionTo('student-reset-password'));
        $student = $this->user('GR-1');
        $student->forceFill(['dob' => '2015-06-01', 'reset_request' => 1])->save();
        $student->assignRole('Student');

        // Any user id and a date from the request were accepted: a clerk could set the Super Admin's password.
        $this->actingAs($clerk)->postJson('/student-change-password', ['id' => $this->superAdmin()->id, 'dob' => '01-01-1970'])->assertJson(['error' => true]);
        $this->assertFalse(Hash::check('01011970', $this->superAdmin()->password));

        $this->postJson('/student-change-password', ['id' => $student->id, 'dob' => '01-01-1970'])->assertJson(['error' => false]);
        $this->assertTrue(Hash::check('01062015', $student->fresh()->password));
        $this->assertSame(0, (int) $student->fresh()->reset_request);
    }

    private function sanctumUser(string $token): ?User
    {
        $this->app['auth']->forgetGuards();

        return $this->app['auth']->guard('sanctum')
            ->setRequest(Request::create('/api/teacher/classes', 'GET', server: ['HTTP_AUTHORIZATION' => "Bearer $token"]))
            ->user();
    }

    private function user(string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
    }

    private function student(string $email, array $parents = []): Students
    {
        return Students::forceCreate(array_merge([
            'user_id' => $this->user($email)->id,
            'category_id' => 1,
            'admission_no' => $email,
            'admission_date' => now(),
        ], $parents));
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
