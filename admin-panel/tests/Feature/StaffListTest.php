<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The staff table on /staff loads its rows from GET /staff/{any}. A staff
 * member saved without a date of birth used to break the whole list with a
 * 500, because the controller formatted a null date under strict types.
 */
class StaffListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_staff_list_shows_a_staff_member_without_a_date_of_birth(): void
    {
        $this->staffMember('no-dob@example.com', null);
        $this->staffMember('with-dob@example.com', '1990-05-17');

        $response = $this->actingAs($this->superAdmin())
            ->getJson(route('staff.show', 1).'?limit=50')
            ->assertOk()
            ->assertJsonPath('total', 2);

        $rows = collect($response->json('rows'))->keyBy('email');
        $this->assertSame('', $rows['no-dob@example.com']['dob']);
        $this->assertNotSame('', $rows['with-dob@example.com']['dob']);
    }

    public function test_teacher_cannot_read_the_staff_list(): void
    {
        $this->staffMember('no-dob@example.com', null);
        $teacher = User::create([
            'first_name' => 'Tess',
            'last_name' => 'Teacher',
            'email' => 'tess@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Female',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');

        $this->actingAs($teacher)
            ->get(route('staff.show', 1))
            ->assertRedirect(route('home'));
    }

    private function staffMember(string $email, ?string $dob): Staff
    {
        $role = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web'], ['custom_role' => 1]);
        $user = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
            'dob' => $dob,
        ]);
        $user->assignRole($role);

        return Staff::forceCreate(['user_id' => $user->id]);
    }
}
