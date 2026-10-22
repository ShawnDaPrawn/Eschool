<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\SessionYear;
use App\Models\User;
use App\Services\Panel\SetupChecklist;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Home: the setup checklist is worked out from the school's data and links to
 * the page that completes each step; today's numbers follow the selected year;
 * the dashboard widgets are still there.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    private SessionYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->year = SessionYear::findOrFail(getSettings('session_year')['session_year']);
        $this->year->update(['start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(8)->toDateString()]);
        $this->superAdmin()->update(['password' => Hash::make('home-test-1')]);
    }

    public function test_a_new_school_sees_every_step_still_to_do_with_a_link_to_do_it(): void
    {
        DB::table('settings')->whereIn('type', ['school_name', 'school_email', 'school_phone', 'school_address'])->delete();

        $page = $this->actingAs($this->superAdmin())->get('/home')->assertOk();

        $page->assertSee('data-setup-checklist', false);
        foreach (['school_profile', 'year_terms', 'grades_homerooms', 'subjects', 'staff', 'learners_parents', 'fees'] as $step) {
            $this->assertMatchesRegularExpression('/data-checklist-item="'.$step.'"\s+data-done="false"/', $page->getContent());
        }
        $page->assertSee(url('settings'), false);
        $page->assertSee(route('semester.index'), false);
        $page->assertSee(route('class.index'), false);
        $page->assertSee(route('subject.index'), false);
        $page->assertSee(route('teachers.index'), false);
        $page->assertSee(route('students.create'), false);
        $page->assertSee(route('fees-type.index'), false);
        $page->assertSee(__('checklist_progress', ['done' => 0, 'total' => 7]));
        // What the dashboard always showed is still there.
        $page->assertSee('id="noticeboard-card"', false);
    }

    public function test_each_step_is_done_once_the_school_has_the_data(): void
    {
        $checklist = app(SetupChecklist::class);
        $before = collect($checklist->items($this->superAdmin()))->keyBy('key');
        foreach (['year_terms', 'grades_homerooms', 'subjects', 'staff', 'learners_parents', 'fees'] as $step) {
            $this->assertFalse($before[$step]['done'], "$step is done on a fresh install");
        }

        $this->buildSchool();

        $result = $checklist->forUser($this->superAdmin());
        $this->assertSame(7, $result['done'], 'Not done: '.implode(', ', array_column(array_filter($result['items'], fn ($i) => ! $i['done']), 'key')));
        $this->assertTrue($result['complete']);

        $this->actingAs($this->superAdmin())->get('/home')->assertOk()
            ->assertSee(__('checklist_ready_title'));
    }

    public function test_an_ended_year_sends_the_year_step_to_the_new_year(): void
    {
        $this->year->update(['end_date' => now()->subDay()->toDateString()]);

        $items = collect(app(SetupChecklist::class)->items($this->superAdmin()))->keyBy('key');

        $this->assertFalse($items['year_terms']['done']);
        $this->assertStringContainsString($this->year->name, $items['year_terms']['detail']);
    }

    public function test_people_who_cannot_complete_any_step_do_not_see_the_checklist(): void
    {
        $teacher = User::forceCreate([
            'first_name' => 'Tess', 'last_name' => 'Teacher', 'email' => 'tess@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        $teacher->assignRole('Teacher');
        SchoolTable::query('teachers')->insert(['user_id' => $teacher->id, 'qualification' => 'B.Ed', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertNull(app(SetupChecklist::class)->forUser($teacher));
        $this->actingAs($teacher)->get('/home')->assertOk()->assertDontSee('data-setup-checklist', false);
    }

    public function test_todays_numbers_follow_the_selected_year(): void
    {
        $this->buildSchool();
        $past = SessionYear::create(['name' => 'Last year', 'start_date' => now()->subYear()->subMonths(2)->toDateString(), 'end_date' => now()->subMonths(3)->toDateString()]);

        $current = $this->actingAs($this->superAdmin())->get('/home')->assertOk();
        $current->assertSee('data-today="learners"', false);
        $this->assertMatchesRegularExpression('/data-today="learners"[^>]*>\s*<span class="page-stat-label">[^<]+<\/span>\s*<span class="page-stat-value">1<\/span>/', $current->getContent());
        $current->assertSee('data-today="attendance"', false);
        $current->assertSee('data-today="notices"', false);

        $last = $this->withSession(['session_year' => $past->id])->get('/home')->assertOk();
        $this->assertMatchesRegularExpression('/data-today="learners"[^>]*>\s*<span class="page-stat-label">[^<]+<\/span>\s*<span class="page-stat-value">0<\/span>/', $last->getContent());
    }

    public function test_staff_present_counts_staff_and_teachers_once_in_the_selected_year(): void
    {
        $this->buildSchool();
        $past = SessionYear::create(['name' => 'Last year', 'start_date' => now()->subYear()->subMonths(2)->toDateString(), 'end_date' => now()->subMonths(3)->toDateString()]);
        $today = now(schoolSetting('time_zone', config('app.timezone')))->toDateString();
        $teacherUserId = (int) DB::table('teachers')->value('user_id');
        $now = ['created_at' => now(), 'updated_at' => now()];
        $staffUser = User::forceCreate(['first_name' => 'Sipho', 'last_name' => 'Staff', 'email' => 'sipho@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0123456789']);
        SchoolTable::query('staffs')->insert(['user_id' => $staffUser->id] + $now);
        // The register lists the teacher (only in teachers) and the staff member;
        // only the teacher is marked for today in the selected year.
        SchoolTable::query('staff_attendances')->insert(['user_id' => $teacherUserId, 'session_year_id' => $this->year->id, 'date' => $today, 'type' => 'present'] + $now);
        // A mark for the same day in another year does not count in this one.
        SchoolTable::query('staff_attendances')->insert(['user_id' => $staffUser->id, 'session_year_id' => $past->id, 'date' => $today, 'type' => 'present'] + $now);

        $page = $this->actingAs($this->superAdmin())->get('/home')->assertOk();

        $this->assertMatchesRegularExpression('/data-today="staff"[^>]*>\s*<span class="page-stat-label">[^<]+<\/span>\s*<span class="page-stat-value">1 of 2<\/span>/', $page->getContent());
    }

    private function buildSchool(): void
    {
        foreach (['school_name' => 'Demo School', 'school_email' => 'office@example.com', 'school_phone' => '0123456789', 'school_address' => '1 Main Road'] as $type => $message) {
            DB::table('settings')->updateOrInsert(['type' => $type], ['message' => $message]);
        }
        $now = ['created_at' => now(), 'updated_at' => now()];
        SchoolTable::query('semesters')->insert(['name' => 'Term 1', 'start_date' => $this->year->start_date, 'end_date' => $this->year->end_date, 'session_year_id' => $this->year->id] + $now);
        $medium = SchoolTable::query('mediums')->insertGetId(['name' => 'English'] + $now);
        $class = SchoolTable::query('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $medium] + $now);
        $section = SchoolTable::query('sections')->insertGetId(['name' => 'A'] + $now);
        $homeroom = SchoolTable::query('class_sections')->insertGetId(['class_id' => $class, 'section_id' => $section] + $now);
        $subject = SchoolTable::query('subjects')->insertGetId(['name' => 'Maths', 'code' => 'MAT', 'bg_color' => '#5c6bc0', 'image' => 'm.png', 'medium_id' => $medium, 'type' => 'Theory'] + $now);
        SchoolTable::query('class_subjects')->insert(['class_id' => $class, 'subject_id' => $subject, 'type' => 'Compulsory', 'session_year_id' => $this->year->id] + $now);

        $teacherUser = User::forceCreate(['first_name' => 'Tara', 'last_name' => 'Teacher', 'email' => 'tara@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789']);
        $teacherUser->assignRole('Teacher');
        $teacher = SchoolTable::query('teachers')->insertGetId(['user_id' => $teacherUser->id, 'qualification' => 'B.Ed'] + $now);
        SchoolTable::query('class_teachers')->insert(['class_section_id' => $homeroom, 'class_teacher_id' => $teacher, 'session_year_id' => $this->year->id] + $now);

        $parentUser = User::forceCreate(['first_name' => 'Farid', 'last_name' => 'Parent', 'email' => 'farid@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0123456789']);
        $parentUser->assignRole('Parent');
        $parent = SchoolTable::query('parents')->insertGetId(['user_id' => $parentUser->id, 'first_name' => 'Farid', 'last_name' => 'Parent', 'gender' => 'Male', 'email' => 'farid@example.com', 'mobile' => '0123456789', 'occupation' => 'Engineer'] + $now);
        $learnerUser = User::forceCreate(['first_name' => 'Asha', 'last_name' => 'Learner', 'email' => 'GR-1', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789']);
        $learnerUser->assignRole('Student');
        $category = SchoolTable::query('categories')->insertGetId(['name' => 'General', 'status' => 1] + $now);
        $student = SchoolTable::query('students')->insertGetId(['user_id' => $learnerUser->id, 'class_id' => $class, 'class_section_id' => $homeroom, 'category_id' => $category, 'admission_no' => 'GR-1', 'roll_number' => 1, 'admission_date' => now()->toDateString(), 'father_id' => $parent] + $now);
        SchoolTable::query('student_sessions')->insert(['student_id' => $student, 'class_section_id' => $homeroom, 'session_year_id' => $this->year->id, 'status' => 1] + $now);

        $type = SchoolTable::query('fees_types')->insertGetId(['name' => 'Tuition', 'description' => 'Tuition fee'] + $now);
        SchoolTable::query('fees_classes')->insert(['session_year_id' => $this->year->id, 'class_id' => $class, 'fees_type_id' => $type, 'choiceable' => 0, 'amount' => 1000] + $now);
    }
}
