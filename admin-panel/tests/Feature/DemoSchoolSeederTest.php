<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Parents;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Panel\SetupChecklist;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Database\Seeders\DemoSchoolSeeder;
use Database\Seeders\InstallationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * DemoSchoolSeeder builds two demo schools with a user per profile, signs in
 * with the documented password, can run any number of times, and never
 * replaces a school year that is still running.
 */
class DemoSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAIN = '@demo.navigateed.test';

    public function test_it_seeds_a_user_per_profile_on_an_installed_database(): void
    {
        $this->seed(InstallationSeeder::class);
        $this->seed(DemoSchoolSeeder::class);

        $current = SessionYear::findOrFail(getSettings('session_year')['session_year']);
        $this->assertSame(now()->format('Y'), $current->name);
        $this->assertSame(4, $current->terms()->count());
        $past = SessionYear::where('name', (string) (now()->year - 1))->firstOrFail();

        $admin = $this->user('admin.a');
        $this->assertTrue($admin->hasRole('Super Admin'));
        $this->assertTrue(Hash::check(DemoSchoolSeeder::PASSWORD, $admin->password));

        // The teacher is both a homeroom teacher and a subject teacher this year.
        $teacher = $this->user('teacher.a');
        $this->assertTrue($teacher->hasRole('Teacher'));
        $this->assertSame(1, DB::table('class_teachers')->where('class_teacher_id', $teacher->teacher->id)->where('session_year_id', $current->id)->count());
        $this->assertGreaterThan(0, DB::table('subject_teachers')->where('teacher_id', $teacher->teacher->id)->where('session_year_id', $current->id)->count());

        // A parent with two children, and an approved learner who signs in with their admission number.
        $parent = $this->user('parent.a');
        $this->assertTrue($parent->hasRole('Parent'));
        $this->assertCount(2, DB::table('students')->where('father_id', $parent->parent->id)->get());
        $learner = User::where('email', 'DEMO-A-0001')->firstOrFail();
        $this->assertTrue($learner->hasRole('Student'));
        $this->assertSame(1, (int) $learner->status);
        $this->assertSame(1, DB::table('student_sessions')->where('student_id', $learner->student->id)->where('session_year_id', $current->id)->where('status', 1)->count());
        $this->assertSame(0, (int) User::where('email', 'DEMO-A-0100')->value('status'), 'The online applicant waits for approval');

        // Staff with the custom Finance role.
        $finance = $this->user('finance.a');
        $this->assertTrue($finance->hasRole('Finance'));
        $this->assertTrue($finance->can('fees-paid'));
        $this->assertFalse($finance->can('student-create'));
        $this->assertNotNull($finance->staff);

        // Last year has its own learners, so the year switcher shows a difference.
        $this->assertSame(3, DB::table('student_sessions')->where('session_year_id', $past->id)->count());
        $this->assertSame(6, DB::table('student_sessions')->where('session_year_id', $current->id)->count());

        // An exam paper to capture marks for (yesterday, unpublished) with the
        // demo learner's mark, and an assignment for 5 A.
        $exam = DB::table('exams')->where('session_year_id', $current->id)->where('name', 'Term 4 test')->first();
        $this->assertNotNull($exam);
        $this->assertSame(0, (int) $exam->publish);
        $paper = DB::table('exam_timetables')->where('exam_id', $exam->id)->where('class_id', $learner->student->class_id)->first();
        $this->assertNotNull($paper);
        $this->assertSame(now()->subDay()->toDateString(), $paper->date);
        $this->assertSame(72, (int) DB::table('exam_marks')->where('exam_timetable_id', $paper->id)->where('student_id', $learner->student->id)->value('obtained_marks'));
        $this->assertSame(2, DB::table('exam_classes')->where('exam_id', $exam->id)->count());
        $this->assertSame(1, DB::table('assignments')->where('session_year_id', $current->id)->where('class_section_id', $learner->student->class_section_id)->count());

        // Home's setup checklist is complete for the demo school.
        $this->assertTrue(app(SetupChecklist::class)->forUser($admin)['complete']);
    }

    public function test_it_seeds_a_second_school_and_people_at_both(): void
    {
        $this->seed(InstallationSeeder::class);
        $this->seed(DemoSchoolSeeder::class);

        $schoolB = School::query()->where('slug', DemoSchoolSeeder::SCHOOL_B_SLUG)->sole();
        $this->assertSame('Baobab Demo High', $schoolB->name);
        $this->assertSame('NavigateEd Demo School', School::query()->find(1)->name);
        $current = app(CurrentSchool::class);

        // School B's people belong to School B only.
        $adminB = $this->user('admin.b');
        $this->assertFalse($adminB->hasRole('Super Admin'));
        $current->run($schoolB, function () use ($adminB) {
            $this->assertTrue($adminB->fresh()->hasRole('Super Admin'));
            $this->assertSame(6, DB::table('student_sessions')->join('students', 'students.id', '=', 'student_id')
                ->where('students.school_id', app(CurrentSchool::class)->id())
                ->where('session_year_id', getSettings('session_year')['session_year'])->count());
        });
        $this->assertSame([$schoolB->id], app(MembershipService::class)->active($adminB)->pluck('school_id')->unique()->values()->all());
        $this->assertNull(Students::query()->where('admission_no', 'DEMO-B-0001')->first(), "School B's learner is not School A's");
        $this->assertNotNull($current->run($schoolB, fn () => Students::query()->where('admission_no', 'DEMO-B-0001')->first()));

        // The parent with a child at each school, and the admin at both.
        $both = $this->user('parent.both');
        $this->assertSame(['Johan'], Students::query()->whereIn('father_id', Parents::query()->where('user_id', $both->id)->pluck('id'))->get()->map(fn (Students $s) => $s->user->first_name)->all());
        $this->assertSame(['Refilwe'], $current->run($schoolB, fn () => Students::query()->whereIn('father_id', Parents::query()->where('user_id', $both->id)->pluck('id'))->get()->map(fn (Students $s) => $s->user->first_name)->all()));
        $this->assertSame([1, $schoolB->id], array_map(fn (array $school) => $school['school']->id, app(MembershipService::class)->schools($this->user('admin.both'))));
        $this->assertTrue($current->run($schoolB, fn () => $this->user('admin.both')->hasRole('Super Admin')));
    }

    public function test_it_can_run_again_without_adding_anything(): void
    {
        $this->seed(InstallationSeeder::class);
        $this->seed(DemoSchoolSeeder::class);
        $tables = ['users', 'students', 'parents', 'teachers', 'staffs', 'session_years', 'terms', 'classes', 'class_sections', 'subjects',
            'class_subjects', 'class_teachers', 'subject_teachers', 'timetables', 'student_sessions', 'fees_classes', 'grades', 'announcements', 'attendances', 'roles',
            'exams', 'exam_classes', 'exam_timetables', 'exam_marks', 'assignments'];
        $before = array_combine($tables, array_map(fn ($table) => DB::table($table)->count(), $tables));

        $this->seed(DemoSchoolSeeder::class);

        $this->assertSame($before, array_combine($tables, array_map(fn ($table) => DB::table($table)->count(), $tables)));
    }

    public function test_it_installs_the_roles_itself_on_an_empty_database(): void
    {
        $this->seed(DemoSchoolSeeder::class);

        $this->assertTrue($this->user('admin.a')->hasRole('Super Admin'));
        $this->assertTrue($this->user('admin.a')->can('student-create'));
    }

    public function test_it_takes_over_an_ended_year_but_never_a_running_one(): void
    {
        $this->seed(InstallationSeeder::class);
        $running = SessionYear::create(['name' => 'Running year', 'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonth()->toDateString()]);
        DB::table('settings')->updateOrInsert(['type' => 'session_year'], ['message' => (string) $running->id]);

        $this->seed(DemoSchoolSeeder::class);

        $this->assertSame((string) $running->id, (string) getSettings('session_year')['session_year']);
    }

    public function test_the_demo_admin_signs_in_to_home(): void
    {
        $this->seed(InstallationSeeder::class);
        $this->seed(DemoSchoolSeeder::class);

        $this->post('/login', ['email' => 'admin.a'.self::DOMAIN, 'password' => DemoSchoolSeeder::PASSWORD])
            ->assertRedirect(url('home'));
        $this->get('/home')->assertOk()->assertSee(__('checklist_ready_title'));
    }

    private function user(string $login): User
    {
        return User::acrossSchools()->where('email', $login.self::DOMAIN)->firstOrFail();
    }
}
