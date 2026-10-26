<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\CardEncoderContext;
use App\Http\Middleware\FoundationCredentialContext;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesDemoSchool;
use Tests\Support\ApiRoute;
use Tests\TestCase;

/**
 * The authorisation matrix (Bible 5.2): every API route, v1 and v2, read from
 * the router, against every role. On every route in a role group
 * (role:<Role>, staff or role.any), each profile the group does not admit is
 * refused with 401 or 403 before anything runs, so the requests change
 * nothing. Routes without a role group are the public and any-profile routes
 * the Tenancy suite lists.
 *
 * Runs in-process on one DemoSchool; the rate limits are off, since one
 * profile makes a few hundred requests here.
 */
class RoleMatrixTest extends TestCase
{
    use UsesDemoSchool;

    /** A role a school creates for its staff, standing for all of them. */
    private const SCHOOL_STAFF_ROLE = 'Office staff';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Every role there is, plus an account with none.
     *
     * @return array<string, array{0: ?string}>
     */
    public static function profiles(): array
    {
        return [
            'student' => ['Student'],
            'parent' => ['Parent'],
            'teacher' => ['Teacher'],
            'facilitator' => ['Facilitator'],
            'counsellor' => ['Counsellor'],
            'safeguarding' => ['Safeguarding'],
            'super admin' => ['Super Admin'],
            'school-created staff role' => [self::SCHOOL_STAFF_ROLE],
            'no role' => [null],
        ];
    }

    #[DataProvider('profiles')]
    public function test_the_wrong_profile_is_refused_on_every_role_grouped_route(?string $role): void
    {
        $token = $this->signIn($role);
        $refusedOn = 0;
        $wronglyServed = [];

        foreach (ApiRoute::all($this->app['router']) as $route) {
            if (! $route->hasRoleGroup() || $route->allowsRole($role)) {
                continue;
            }

            $this->app['auth']->forgetGuards();
            $status = $this->withToken($token)->json($route->method, $route->path())->status();
            if (in_array($status, [401, 403], true)) {
                $refusedOn++;
            } else {
                $wronglyServed[] = $route->label().' ('.$route->roleGroup().") answered $status";
            }
        }

        $this->assertSame([], $wronglyServed, ($role ?? 'An account with no role').' got past these role groups');
        $this->assertGreaterThan(0, $refusedOn, 'Every profile is outside some role group');
    }

    #[DataProvider('profiles')]
    public function test_no_ordinary_account_role_can_borrow_restricted_tablet_or_learner_authority(?string $role): void
    {
        $token = $this->signIn($role);
        $checked = 0;
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if (array_intersect([FoundationCredentialContext::class.':device', FoundationCredentialContext::class.':learner'], $route->middleware) === []) {
                continue;
            }
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->json($route->method, $route->path())->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
            $checked++;
        }
        $this->assertSame(6, $checked);
    }

    #[DataProvider('profiles')]
    public function test_no_ordinary_account_role_can_borrow_encoder_key_delivery_authority(?string $role): void
    {
        $token = $this->signIn($role);
        $checked = 0;
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if (! in_array(CardEncoderContext::class, $route->middleware, true)) {
                continue;
            }
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->json($route->method, $route->path())->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
            $checked++;
        }
        $this->assertSame(2, $checked);
    }

    public function test_the_matrix_knows_every_role(): void
    {
        $known = array_filter(array_column(self::profiles(), 0));
        $roles = Role::query()->where('name', '!=', self::SCHOOL_STAFF_ROLE)->pluck('name')->all();

        $this->assertSame([], array_values(array_diff($roles, $known)), 'Add these roles to RoleMatrixTest::profiles()');
    }

    public function test_the_groups_admit_the_right_roles(): void
    {
        $route = fn (string $group) => new ApiRoute('GET', 'api/x', ['api', 'auth:sanctum', $group]);

        $this->assertTrue($route('role:Parent')->allowsRole('Parent'));
        $this->assertFalse($route('role:Parent')->allowsRole('Student'));
        $this->assertTrue($route('role:Teacher|Super Admin')->allowsRole('Super Admin'));
        $this->assertTrue($route('staff')->allowsRole('Teacher'));
        $this->assertTrue($route('staff')->allowsRole(self::SCHOOL_STAFF_ROLE));
        $this->assertFalse($route('staff')->allowsRole('Parent'));
        $this->assertTrue($route('role.any')->allowsRole('Student'));
        $this->assertFalse($route('role.any')->allowsRole(null));
    }

    private function signIn(?string $role): string
    {
        $user = match ($role) {
            'Student' => User::findOrFail($this->school->ids['asha_user']),
            'Parent' => User::findOrFail($this->school->ids['farid_user']),
            'Teacher' => User::findOrFail($this->school->ids['tara_user']),
            'Super Admin' => $this->superAdmin(),
            default => $this->newUser($role),
        };

        return $user->createToken('matrix')->plainTextToken;
    }

    private function newUser(?string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Robin', 'last_name' => 'Office', 'email' => 'robin'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        if ($role !== null) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }

        return $user;
    }
}
