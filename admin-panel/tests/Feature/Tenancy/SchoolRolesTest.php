<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Spatie permission in teams mode, the team being the school: every school
 * has its own roles, a role is granted at one school, and the panel sees
 * only the current school's roles.
 */
class SchoolRolesTest extends TestCase
{
    use RefreshDatabase;

    private CurrentSchool $current;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(2);
        $this->current = app(CurrentSchool::class);
    }

    public function test_a_new_school_gets_its_own_system_roles_with_their_permissions(): void
    {
        $roles = Role::acrossSchoolsQuery()->where('school_id', 2)->orderBy('name')->pluck('name')->all();
        $this->assertSame(['Counsellor', 'Facilitator', 'Parent', 'Safeguarding', 'Student', 'Super Admin', 'Teacher', 'Vendor'], $roles);

        $schoolOne = Role::query()->where('name', 'Super Admin')->sole();
        $schoolTwo = $this->current->run(2, fn () => Role::query()->where('name', 'Super Admin')->sole());
        $this->assertNotSame($schoolOne->id, $schoolTwo->id);
        $this->assertSame($schoolOne->permissions->pluck('name')->sort()->values()->all(), $schoolTwo->permissions->pluck('name')->sort()->values()->all());
        $this->assertSame(count(SchoolRoles::PERMISSIONS['Teacher']), $this->current->run(2, fn () => Role::findByName('Teacher')->permissions()->count()));

        $this->assertSame(0, $this->current->run(2, fn () => Role::findByName('Vendor')->permissions()->count()));

        // Seeding again changes nothing.
        app(SchoolRoles::class)->seedEverySchool();
        $this->assertSame(16, Role::acrossSchoolsQuery()->whereIn('school_id', [1, 2])->count());
    }

    public function test_a_role_is_granted_at_one_school(): void
    {
        $user = User::query()->forceCreate([
            'first_name' => 'Pieter', 'last_name' => 'van Wyk', 'email' => 'pieter@example.com',
            'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Male', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $user->assignRole('Teacher');
        $this->current->run(2, fn () => $user->assignRole('Super Admin'));

        $this->assertTrue($user->fresh()->hasRole('Teacher'));
        $this->assertFalse($user->fresh()->hasRole('Super Admin'));
        $this->assertFalse($user->fresh()->can('role-create'));
        $this->current->run(2, function () use ($user) {
            $this->assertTrue($user->fresh()->hasRole('Super Admin'));
            $this->assertFalse($user->fresh()->hasRole('Teacher'));
            $this->assertTrue($user->fresh()->can('role-create'));
        });
        $this->assertSame([[1, 'Teacher'], [2, 'Super Admin']], DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'role_id')
            ->where('model_id', $user->id)->orderBy('model_has_roles.school_id')->get(['model_has_roles.school_id', 'roles.name'])
            ->map(fn ($row) => [(int) $row->school_id, $row->name])->all());
        $this->assertSame([1, 2], array_map(fn ($school) => $school['school']->id, app(MembershipService::class)->schools($user)));

        // Permission 8 renamed these events. Detaching must end only the
        // current school's membership and invalidate the cached school list.
        $user->removeRole('Teacher');
        $this->assertSame([2], array_map(fn ($school) => $school['school']->id, app(MembershipService::class)->schools($user)));
        $this->current->run(2, function () use ($user) {
            $this->assertTrue($user->fresh()->hasRole('Super Admin'));
            $this->assertTrue($user->fresh()->can('role-create'));
        });
        $user->assignRole('Teacher');
        $this->assertSame([1, 2], array_map(fn ($school) => $school['school']->id, app(MembershipService::class)->schools($user)));
    }

    public function test_the_roles_page_lists_and_opens_only_the_current_schools_roles(): void
    {
        $admin = $this->superAdmin();
        $other = $this->current->run(2, fn () => Role::query()->create(['name' => 'Bursar B', 'custom_role' => 1]));
        $mine = Role::query()->create(['name' => 'Bursar A', 'custom_role' => 1]);

        $list = $this->actingAs($admin)->getJson('/roles-data?limit=50&offset=0')->assertOk()->json('rows');
        $names = collect($list)->pluck('name')->all();
        $this->assertContains('Bursar A', $names);
        $this->assertNotContains('Bursar B', $names);

        $this->actingAs($admin)->get("/roles/{$mine->id}/edit")->assertOk();
        $this->actingAs($admin)->get("/roles/{$other->id}/edit")->assertRedirect(route('roles.index'));
        $this->actingAs($admin)->get("/roles-list/{$other->id}")->assertNotFound();
        // Another school may use a role name this school uses.
        $this->assertNotNull($this->current->run(2, fn () => Role::query()->create(['name' => 'Bursar A', 'custom_role' => 1])));
    }

    public function test_retired_fee_permissions_are_not_offered_or_mapped_to_new_financial_authority(): void
    {
        $legacy = Permission::findByName('fees-paid', 'web');
        $current = Permission::findByName('fee-payments-manage', 'web');
        $page = $this->actingAs($this->superAdmin())->get('/roles/create')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($page->getContent());
        $values = [];
        foreach ((new \DOMXPath($document))->query('//input[@name="permission[]"]/@value') as $attribute) {
            $values[] = $attribute->nodeValue;
        }
        $this->assertNotContains((string) $legacy->id, $values);
        $this->assertContains((string) $current->id, $values);
        $this->post('/roles', ['name' => 'Current receipt team', 'permission' => [$legacy->id, $current->id]])
            ->assertRedirect(route('roles.index'));
        $role = Role::where('name', 'Current receipt team')->firstOrFail();
        $this->assertSame(['fee-payments-manage'], $role->permissions->pluck('name')->all());
        $this->assertTrue(Permission::whereKey($legacy->id)->exists(), 'Retirement does not destructively rewrite rollback metadata.');
    }
}
