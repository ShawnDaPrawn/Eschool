<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * What every API v2 route has in common: where the routes are loaded, the
 * role groups, the error envelope with real HTTP statuses, and the auth and
 * money rate limits. Uses test-only routes on the real api group.
 */
class ApiV2GroundRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();

        Route::middleware('api')->prefix('api/v2/_test')->group(function () {
            Route::middleware(['auth:sanctum', 'school.context', 'staff'])->get('staff', fn () => response()->json(['ok' => true]));
            Route::middleware(['auth:sanctum', 'school.context', 'role.any'])->get('anyone', fn () => response()->json(['ok' => true]));
            Route::post('validate', fn (Request $request) => $request->validate(['name' => 'required', 'age' => 'required|integer']));
            Route::get('broken', fn () => throw new RuntimeException('database password is hunter2'));
            Route::get('thrown', fn () => throw new HttpResponseException(response()->json(['error' => true, 'code' => 105], 200)));
            Route::middleware('throttle:auth')->post('sign-in', fn () => response()->json(['ok' => true]));
            Route::middleware(['auth:sanctum', 'school.context', 'throttle:money'])->post('pay', fn () => response()->json(['ok' => true]));
        });
    }

    public function test_v2_routes_are_loaded_under_api_v2_with_the_api_group(): void
    {
        $route = Route::getRoutes()->getByName('api.v2.me.context');

        $this->assertSame('api/v2/me/context', $route->uri());
        $this->assertSame(['api', 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'idempotent'], $route->gatherMiddleware());
    }

    public function test_the_staff_group_admits_every_role_except_parent_and_student(): void
    {
        Role::create(['name' => 'Bursar', 'guard_name' => 'web']);

        foreach (['Student' => 403, 'Parent' => 403, 'Teacher' => 200, 'Super Admin' => 200, 'Bursar' => 200] as $role => $status) {
            $this->as($this->userWithRole($role))->getJson('/api/v2/_test/staff')->assertStatus($status);
        }

        $this->as($this->userWithRole('Parent'))->getJson('/api/v2/_test/staff')
            ->assertExactJson(['error' => true, 'message' => trans('api_forbidden'), 'data' => null, 'code' => 'forbidden']);
    }

    public function test_routes_for_every_profile_still_need_a_role(): void
    {
        $this->as($this->userWithRole('Student'))->getJson('/api/v2/_test/anyone')->assertOk();
        $this->as($this->userWithRole(null))->getJson('/api/v2/_test/anyone')->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_errors_come_in_the_envelope_with_their_real_status(): void
    {
        $this->getJson('/api/v2/_test/staff')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
        $this->getJson('/api/v2/nothing-here')->assertStatus(404)
            ->assertExactJson(['error' => true, 'message' => trans('api_not_found'), 'data' => null, 'code' => 'not_found']);
        $this->deleteJson('/api/v2/_test/validate')->assertStatus(405)->assertJsonPath('code', 'method_not_allowed');

        $this->postJson('/api/v2/_test/validate', ['age' => 'old'])->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('message', 'The name field is required.')
            ->assertJsonPath('data.errors.age.0', 'The age must be an integer.');
    }

    public function test_an_unexpected_error_says_nothing_about_its_cause(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/v2/_test/broken')->assertStatus(500)
            ->assertExactJson(['error' => true, 'message' => trans('api_server_error'), 'data' => null, 'code' => 'server_error']);
        $this->assertStringNotContainsString('hunter2', $response->getContent());
    }

    public function test_a_response_thrown_on_purpose_and_v1_errors_are_left_alone(): void
    {
        $this->getJson('/api/v2/_test/thrown')->assertOk()->assertExactJson(['error' => true, 'code' => 105]);

        // The installed apps read v1's own 401 body.
        $this->getJson('/api/student/dashboard')->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_sign_in_is_limited_to_five_a_minute_per_account_and_twenty_per_address(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v2/_test/sign-in', ['email' => 'Tara@Example.com'])->assertOk();
        }
        $this->postJson('/api/v2/_test/sign-in', ['email' => 'tara@example.com '])->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertHeader('Retry-After');

        // Another account from the same address still gets in, up to 20 a minute in all.
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/api/v2/_test/sign-in', ['email' => "parent$i@example.com"])->assertOk();
        }
        $this->postJson('/api/v2/_test/sign-in', ['email' => 'someone-new@example.com'])->assertStatus(429);
    }

    public function test_money_writes_are_limited_to_thirty_a_minute_per_token(): void
    {
        $parent = $this->userWithRole('Parent');
        $phone = $parent->createToken('phone')->plainTextToken;
        $tablet = $parent->createToken('tablet')->plainTextToken;

        for ($i = 0; $i < 30; $i++) {
            $this->withToken($phone)->postJson('/api/v2/_test/pay')->assertOk();
            $this->app['auth']->forgetGuards();
        }
        $this->withToken($phone)->postJson('/api/v2/_test/pay')->assertStatus(429)->assertJsonPath('code', 'too_many_requests');
        $this->app['auth']->forgetGuards();

        $this->withToken($tablet)->postJson('/api/v2/_test/pay')->assertOk();
    }

    private function as(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    private function userWithRole(?string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Sam', 'last_name' => 'Test', 'email' => 'sam'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
