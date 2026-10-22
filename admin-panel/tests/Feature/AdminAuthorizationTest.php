<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Parents;
use App\Models\Slider;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Per-method access control of the admin controllers. Every web route only
 * requires a login, so these checks are what keeps a Teacher or a custom-role
 * staff member out of admin-only actions.
 *
 * Requested in-process. (ResponseService used to call exit(), so paths through
 * it were left to AdminPagesTest; it returns its response now.)
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    // ---- RoleController -------------------------------------------------

    public function test_teacher_cannot_edit_roles(): void
    {
        $teacherRole = Role::findByName('Teacher');

        $this->actingAs($this->teacher())->put('/roles/'.$teacherRole->id, [
            'name' => 'Teacher',
            'permission' => [Permission::findByName('role-edit')->id],
        ])->assertForbidden();

        $this->assertFalse($teacherRole->fresh()->hasPermissionTo('role-edit'));
    }

    public function test_built_in_roles_cannot_be_edited_or_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $superAdminRole = Role::findByName('Super Admin');
        $teacherRole = Role::findByName('Teacher');
        $before = $superAdminRole->permissions()->pluck('id')->sort()->values()->all();

        $this->actingAs($superAdmin)->put('/roles/'.$superAdminRole->id, [
            'name' => 'Super Admin',
            'permission' => [Permission::findByName('role-list')->id],
        ])->assertRedirect(route('roles.index'))->assertSessionHasErrors('message');
        $this->assertSame($before, $superAdminRole->fresh()->permissions()->pluck('id')->sort()->values()->all());

        $this->actingAs($superAdmin)->put('/roles/'.$teacherRole->id, [
            'name' => 'Teacher',
            'permission' => [Permission::findByName('role-edit')->id],
        ])->assertSessionHasErrors('message');
        $this->assertFalse($teacherRole->fresh()->hasPermissionTo('role-edit'));
        $this->assertSame(0, (int) $teacherRole->fresh()->custom_role);

        $this->actingAs($superAdmin)->get('/roles/'.$teacherRole->id.'/edit')->assertRedirect(route('roles.index'));

        $this->actingAs($superAdmin)->deleteJson('/roles/'.$teacherRole->id)->assertJson(['error' => true]);
        $this->assertNotNull(Role::find($teacherRole->id));
    }

    public function test_custom_roles_only_get_assignable_permissions(): void
    {
        $superAdmin = $this->superAdmin();
        // Teacher-only permission, not offered on the role forms.
        $teacherOnly = Permission::findByName('attendance-create');
        $staffList = Permission::findByName('staff-list');

        $this->actingAs($superAdmin)->post('/roles', [
            'name' => 'Clerk',
            'permission' => [$staffList->id, $teacherOnly->id],
        ])->assertRedirect(route('roles.index'));
        $clerk = Role::findByName('Clerk');
        $this->assertSame(['staff-list'], $clerk->permissions()->pluck('name')->all());

        $staffEdit = Permission::findByName('staff-edit');
        $this->actingAs($superAdmin)->put('/roles/'.$clerk->id, [
            'name' => 'Clerk',
            'permission' => [$staffEdit->id, $teacherOnly->id, 999999],
        ])->assertRedirect(route('roles.index'))->assertSessionHasNoErrors();
        $this->assertSame(['staff-edit'], $clerk->fresh()->permissions()->pluck('name')->all());

        // A custom role can't be renamed to a built-in one.
        $this->actingAs($superAdmin)->put('/roles/'.$clerk->id, [
            'name' => 'Super Admin',
            'permission' => [$staffEdit->id],
        ])->assertSessionHasErrors('name');
        $this->assertSame('Clerk', $clerk->fresh()->name);
    }

    // ---- StaffController ------------------------------------------------

    public function test_staff_cannot_be_given_the_super_admin_role(): void
    {
        $role = $this->customRole('Clerk', ['staff-edit']);
        [$staffUser] = $this->staffMember('clerk@example.com', $role);

        $this->actingAs($this->superAdmin())->post('/staff/'.$staffUser->id, [
            '_method' => 'PUT',
            'user_id' => $staffUser->id,
            'role_id' => Role::findByName('Super Admin')->id,
            'first_name' => 'Clerk',
            'last_name' => 'User',
            'email' => 'clerk@example.com',
            'gender' => 'Male',
            'mobile' => '1234567890',
            'dob' => '2000-01-01',
        ])->assertJson(['error' => true]);

        $this->assertFalse($staffUser->fresh()->hasRole('Super Admin'));
    }

    public function test_staff_manager_cannot_edit_or_delete_the_super_admin(): void
    {
        $role = $this->customRole('Manager', ['staff-list', 'staff-edit', 'staff-delete']);
        [$manager] = $this->staffMember('manager@example.com', $role);
        $superAdmin = $this->superAdmin();
        // Even with a staff record, the Super Admin is off limits.
        Staff::forceCreate(['user_id' => $superAdmin->id]);

        $this->actingAs($manager)->post('/staff/'.$superAdmin->id, [
            '_method' => 'PUT',
            'user_id' => $superAdmin->id,
            'role_id' => $role->id,
            'first_name' => 'Owned',
            'last_name' => 'Admin',
            'email' => 'owned@example.com',
            'gender' => 'Male',
            'mobile' => '1234567890',
            'dob' => '2000-01-01',
        ])->assertJson(['error' => true, 'message' => trans('no_permission_message')]);

        $this->actingAs($manager)->deleteJson('/staff/'.$superAdmin->id)
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);

        $superAdmin = $superAdmin->fresh();
        $this->assertNotNull($superAdmin);
        $this->assertSame('superadmin@gmail.com', $superAdmin->email);
        $this->assertTrue($superAdmin->hasRole('Super Admin'));
    }

    public function test_teacher_cannot_use_staff_attendance(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->get('/staff-attendance')->assertRedirect(route('home'));
        $this->actingAs($teacher)->getJson('/staff-attendance/list')->assertJson(['error' => true]);
        $this->actingAs($teacher)->getJson('/staff-attendance/get-attendance')->assertJson(['error' => true]);
        $this->actingAs($teacher)->postJson('/staff-attendance/store', [
            'staff_id' => $teacher->id,
            'session_year_id' => 1,
            'date' => now()->format('Y-m-d'),
            'type' => 'present',
        ])->assertForbidden();

        $this->assertDatabaseMissing('staff_attendances', ['user_id' => $teacher->id]);
    }

    // ---- LanguageController ---------------------------------------------

    public function test_language_code_cannot_escape_the_lang_directory(): void
    {
        $superAdmin = $this->superAdmin();
        $file = UploadedFile::fake()->createWithContent('lang.json', '{"hello":"world"}');

        foreach (['../../public/evil', '../evil', 'en/../../x', 'e.php', ''] as $code) {
            $this->actingAs($superAdmin)->post('/language', [
                'name' => 'Evil',
                'code' => $code,
                'file' => $file,
            ])->assertSessionHasErrors('code');
        }

        $this->assertFileDoesNotExist(base_path('public/evil.json'));
        $this->assertFileDoesNotExist(base_path('resources/evil.json'));
        $this->assertFileDoesNotExist(base_path('resources/lang/evil.json'));
        $this->assertFileDoesNotExist(base_path('resources/lang/x.json'));
        $this->assertDatabaseMissing('languages', ['name' => 'Evil']);

        $english = Language::where('code', 'en')->firstOrFail();
        $this->actingAs($superAdmin)->put('/language/'.$english->id, [
            'id' => $english->id,
            'name' => 'English',
            'code' => '../../public/evil',
        ])->assertSessionHasErrors('code');
        $this->assertSame('en', $english->fresh()->code);
    }

    // ---- HomeController::changePassword ---------------------------------

    public function test_change_password_requires_the_current_password(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->postJson('/changePassword', [
            'old_password' => 'not-my-password',
            'new_password' => 'brand-new-pass',
            'confirm_password' => 'brand-new-pass',
        ])->assertOk()->assertJson(['error' => true]);
        $this->assertTrue(Hash::check('secret123', $teacher->fresh()->password));

        $this->actingAs($teacher)->postJson('/changePassword', [
            'old_password' => 'secret123',
            'new_password' => 'brand-new-pass',
            'confirm_password' => 'brand-new-pass',
        ])->assertOk()->assertJson(['error' => false]);
        $this->assertTrue(Hash::check('brand-new-pass', $teacher->fresh()->password));
    }

    // ---- SliderController -----------------------------------------------

    public function test_teacher_cannot_manage_sliders(): void
    {
        $teacher = $this->teacher();
        $slider = new Slider;
        $slider->type = 1;
        $slider->image = 'sliders/existing.png';
        $slider->save();

        $this->actingAs($teacher)->get('/sliders')->assertRedirect(route('home'));
        $this->actingAs($teacher)->getJson('/sliders/1')->assertJson(['error' => true]);
        $this->actingAs($teacher)->post('/sliders', [
            'type' => 1,
            'image' => UploadedFile::fake()->image('slide.png'),
        ])->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
        $this->actingAs($teacher)->post('/sliders/'.$slider->id, ['_method' => 'PUT', 'type' => 2])
            ->assertJson(['error' => true]);
        $this->actingAs($teacher)->deleteJson('/sliders/'.$slider->id)->assertJson(['error' => true]);

        $this->assertSame(1, Slider::count());
        $this->assertSame(1, (int) $slider->fresh()->type);
    }

    // ---- ParentsController::search --------------------------------------

    public function test_teacher_cannot_search_parents(): void
    {
        $parentUser = User::create([
            'first_name' => 'Pat',
            'last_name' => 'Parent',
            'email' => 'pat.parent@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '1111111111',
        ]);
        Parents::forceCreate([
            'user_id' => $parentUser->id,
            'first_name' => 'Pat',
            'last_name' => 'Parent',
            'gender' => 'Male',
            'email' => 'pat.parent@example.com',
            'mobile' => '1111111111',
            'occupation' => 'Engineer',
            'dob' => '1980-01-01',
        ]);

        $this->actingAs($this->teacher())->getJson('/parent/search?email=pat')
            ->assertJson(['error' => true])
            ->assertJsonMissing(['email' => 'pat.parent@example.com']);

        $this->actingAs($this->superAdmin())->getJson('/parent/search?email=pat')
            ->assertJson(['error' => false])
            ->assertJsonFragment(['email' => 'pat.parent@example.com']);
    }

    // ---- SettingController ----------------------------------------------

    public function test_setting_uploads_are_validated(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post('/settings', [
            'logo1' => UploadedFile::fake()->createWithContent('logo.txt', 'plain text'),
        ])->assertSessionHasErrors('logo1');

        $this->actingAs($superAdmin)->post('/notification-setting', [
            'sender_id' => '123',
            'project_id' => 'demo',
            'service_account_file' => UploadedFile::fake()->createWithContent('service.json', '{"not":"a key"}'),
        ])->assertSessionHasErrors('service_account_file');
    }

    // ---- helpers --------------------------------------------------------

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

    private function customRole(string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name, 'custom_role' => 1]);
        $role->syncPermissions($permissions);

        return $role;
    }

    /** @return array{0: User, 1: Staff} */
    private function staffMember(string $email, Role $role): array
    {
        $user = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $user->assignRole($role);
        $staff = Staff::forceCreate(['user_id' => $user->id]);

        return [$user, $staff];
    }
}
