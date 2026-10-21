<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Students;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The installer seeds no 'Admin' role, but two checks treated one as Super
 * Admin. A custom role a school happens to call "Admin" must not get extra
 * access from them.
 */
class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private int $sessionYearId;

    private int $classId;

    /** @var array<string, int> */
    private array $sections = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        Role::create(['name' => 'Admin', 'custom_role' => 1]);

        $this->sessionYearId = DB::table('session_years')->insertGetId([
            'name' => 'Current', 'start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(8)->toDateString(),
        ]);
        $mediumId = DB::table('mediums')->insertGetId(['name' => 'English']);
        $this->classId = DB::table('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $mediumId]);
        foreach (['A', 'B'] as $name) {
            $sectionId = DB::table('sections')->insertGetId(['name' => $name]);
            $this->sections[$name] = DB::table('class_sections')->insertGetId(['class_id' => $this->classId, 'section_id' => $sectionId]);
        }
    }

    public function test_a_teacher_with_a_custom_admin_role_sees_only_their_sections_attendance(): void
    {
        $teacher = $this->user('teacher@example.com', ['Teacher', 'Admin']);
        $teacherId = DB::table('teachers')->insertGetId(['user_id' => $teacher->id, 'qualification' => 'B.Ed']);
        DB::table('class_teachers')->insert(['class_section_id' => $this->sections['A'], 'class_teacher_id' => $teacherId, 'session_year_id' => $this->sessionYearId]);

        $url = '/home/widget/attendance?'.http_build_query(['class_id' => $this->classId, 'session_year_id' => $this->sessionYearId]);

        $sections = $this->actingAs($teacher)->getJson($url)->assertOk()->json('sections');
        $this->assertSame([$this->sections['A']], array_column($sections, 'section_id'));

        $sections = $this->actingAs($this->superAdmin())->getJson($url)->assertOk()->json('sections');
        $this->assertEqualsCanonicalizing(array_values($this->sections), array_column($sections, 'section_id'));
    }

    public function test_a_custom_admin_role_does_not_see_every_student(): void
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'General']);
        DB::table('students')->insert([
            'user_id' => $this->user('student@example.com', ['Student'])->id, 'class_section_id' => $this->sections['A'],
            'category_id' => $categoryId, 'admission_no' => 'GR1', 'admission_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user('office@example.com', ['Admin']));
        $this->assertSame(0, Students::query()->ofTeacher()->count());

        $this->actingAs($this->superAdmin());
        $this->assertSame(1, Students::query()->ofTeacher()->count());
    }

    /** @param  list<string>  $roles */
    private function user(string $email, array $roles): User
    {
        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => $roles[0], 'email' => $email, 'password' => Hash::make('secret123'),
            'gender' => 'Female', 'mobile' => '0000000000', 'status' => 1,
        ]);
        $user->assignRole($roles);

        return $user;
    }
}
