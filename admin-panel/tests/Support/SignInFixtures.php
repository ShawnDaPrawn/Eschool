<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The smallest school the three app sign-ins work against: one class section,
 * a learner in it with a session this year, their parent and a teacher. Works
 * inside RefreshDatabase on an installer-seeded database (unlike DemoSchool,
 * which expects fresh auto-increment ids).
 *
 * Everyone's password is the one given; the learner signs in with GR number
 * GR-LEO, the parent as pam@example.com and the teacher as tom@example.com.
 */
trait SignInFixtures
{
    /** @var array<string, int> */
    protected array $people = [];

    protected function buildSignInSchool(string $password = 'secret123', ?string $learnerDob = '2015-06-01'): void
    {
        $medium = $this->fixtureRow('mediums', ['name' => 'English']);
        $category = $this->fixtureRow('categories', ['name' => 'General', 'status' => 1]);
        $class = $this->fixtureRow('classes', ['name' => 'Grade 5', 'medium_id' => $medium]);
        $section = $this->fixtureRow('class_sections', ['class_id' => $class, 'section_id' => $this->fixtureRow('sections', ['name' => 'A'])]);
        $this->fixtureRow('class_session_configs', ['class_id' => $class, 'session_year_id' => 1, 'include_semesters' => 0]);

        $teacher = $this->fixtureUser('tom', 'Teacher', $password, ['email' => 'tom@example.com', 'dob' => '1990-04-12']);
        $this->fixtureRow('teachers', ['user_id' => $teacher->id, 'qualification' => 'B.Ed']);

        $parent = $this->fixtureUser('pam', 'Parent', $password, ['email' => 'pam@example.com', 'dob' => '1985-02-03']);
        $parentRow = $this->fixtureRow('parents', [
            'user_id' => $parent->id, 'first_name' => 'Pam', 'last_name' => 'Parent', 'gender' => 'Female',
            'email' => 'pam@example.com', 'mobile' => '0123456789', 'occupation' => 'Nurse', 'dob' => '1985-02-03',
        ]);

        $learner = $this->fixtureUser('leo', 'Student', $password, ['email' => 'GR-LEO', 'dob' => $learnerDob]);
        $student = $this->fixtureRow('students', [
            'user_id' => $learner->id, 'class_id' => $class, 'class_section_id' => $section, 'category_id' => $category,
            'admission_no' => 'GR-LEO', 'roll_number' => 1, 'admission_date' => now()->subYear()->toDateString(), 'father_id' => $parentRow,
        ]);
        $this->fixtureRow('student_sessions', ['student_id' => $student, 'class_section_id' => $section, 'session_year_id' => 1, 'status' => 1]);

        $this->people = [
            'teacher' => $teacher->id, 'parent' => $parent->id, 'parent_row' => $parentRow, 'learner' => $learner->id,
            'student' => $student, 'class_section' => $section, 'class' => $class,
        ];
    }

    protected function person(string $key): User
    {
        return User::findOrFail($this->people[$key]);
    }

    private function fixtureUser(string $name, string $role, string $password, array $extra): User
    {
        $user = User::forceCreate($extra + [
            'first_name' => ucfirst($name), 'last_name' => 'Fixture', 'password' => Hash::make($password),
            'gender' => 'Female', 'mobile' => '0123456789', 'status' => 1,
        ]);
        $user->assignRole($role);
        if ($role !== 'Student') {
            ParentPolicyService::acceptCurrent($user);
        }

        return $user;
    }

    private function fixtureRow(string $table, array $values): int
    {
        $query = SchoolOwnedTables::isSchoolOwned($table) ? SchoolTable::query($table) : DB::table($table);

        return $query->insertGetId($values + ['created_at' => now(), 'updated_at' => now()]);
    }
}
