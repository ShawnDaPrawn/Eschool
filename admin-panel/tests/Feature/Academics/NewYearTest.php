<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\NewYearService;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The new-year wizard: creating next year leaves the current year untouched;
 * the switch runs only the chosen transfers, never twice, applies promotions
 * and makes the year current; only people who may manage years can use it.
 */
class NewYearTest extends TestCase
{
    use RefreshDatabase;

    private const YEAR_TABLES = ['class_subjects', 'elective_subject_groups', 'class_teachers', 'subject_teachers', 'timetables', 'grades', 'fees_classes', 'leave_masters', 'terms', 'student_sessions', 'class_session_configs'];

    private SessionYear $current;

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->current = SessionYear::findOrFail(getSettings('session_year')['session_year']);
        $this->current->update(['name' => '2026', 'start_date' => now()->subMonths(9)->toDateString(), 'end_date' => now()->addMonths(1)->toDateString(), 'fee_due_charges' => 5]);
        $this->superAdmin()->update(['password' => Hash::make('new-year-test')]);
        $this->setUpCurrentYear();
    }

    public function test_the_wizard_suggests_next_year_from_the_current_one(): void
    {
        $page = $this->actingAs($this->superAdmin())->get(route('new-year.index'))->assertOk();

        $page->assertSee('value="2027"', false);
        $page->assertSee('value="'.now()->subMonths(9)->addYear()->toDateString().'"', false);
        $page->assertSee('value="Term 1"', false);
        $page->assertSee('data-wizard-step="1"', false);
        $page->assertSee('data-start-step="1"', false);
    }

    public function test_creating_next_year_leaves_the_current_year_untouched(): void
    {
        $before = $this->snapshot();

        $response = $this->actingAs($this->superAdmin())->postJson(route('new-year.store'), $this->nextYear())->assertOk();
        $response->assertJson(['error' => false, 'data' => ['name' => '2027']]);
        $next = SessionYear::where('name', '2027')->firstOrFail();

        $this->assertSame($before, $this->snapshot(), 'Creating next year changed the current year');
        $this->assertSame((string) $this->current->id, (string) getSettings('session_year')['session_year']);
        $this->assertNull(session('session_year'), 'The panel must keep showing the current year');
        $this->assertSame(2, DB::table('terms')->where('session_year_id', $next->id)->count(), 'The terms with dates are created');
        foreach (['class_subjects', 'class_teachers', 'timetables', 'grades', 'fees_classes', 'leave_masters'] as $table) {
            $this->assertSame(0, DB::table($table)->where('session_year_id', $next->id)->count(), "Nothing is carried into $table before the switch");
        }

        // The wizard now continues from the carry-forward step.
        $this->get(route('new-year.index'))->assertOk()->assertSee('data-start-step="2"', false)->assertSee('data-next-year="'.$next->id.'"', false);
    }

    public function test_the_switch_runs_the_chosen_transfers_and_makes_the_year_current(): void
    {
        $next = $this->createNextYear();
        SchoolTable::query('student_sessions')->insert(['student_id' => $this->ids['student'], 'class_section_id' => $this->ids['homeroom_b'], 'session_year_id' => $next->id, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($this->superAdmin())->postJson(route('new-year.make-current'), [
            'year_id' => $next->id,
            'carry' => ['subjects', 'grading', 'teachers'],
        ])->assertOk();

        $response->assertJson(['error' => false, 'redirect' => route('home'), 'data' => ['copied' => ['subjects', 'teachers', 'grading']]]);
        $this->assertSame((string) $next->id, (string) getSettings('session_year')['session_year']);
        $this->assertSame(1, (int) $next->fresh()->default);
        $this->assertSame(0, (int) $this->current->fresh()->default);

        $this->assertSame(2, DB::table('class_subjects')->where('session_year_id', $next->id)->count());
        $this->assertSame(3, DB::table('grades')->where('session_year_id', $next->id)->count());
        $this->assertSame(1, DB::table('class_teachers')->where('session_year_id', $next->id)->count());
        $this->assertSame(0, DB::table('timetables')->where('session_year_id', $next->id)->count(), 'The timetable was not chosen');
        $this->assertSame(0, DB::table('fees_classes')->where('session_year_id', $next->id)->count(), 'Fees were not chosen');
        // The current year's own rows are still there.
        $this->assertSame(2, DB::table('class_subjects')->where('session_year_id', $this->current->id)->count());
        // Promotions take effect: the learner moves to the homeroom recorded for next year.
        $this->assertSame($this->ids['homeroom_b'], (int) DB::table('students')->where('id', $this->ids['student'])->value('class_section_id'));
        $this->assertSame($next->id, (int) session('session_year'), 'The panel follows the new current year');
    }

    public function test_the_switch_never_copies_what_the_new_year_already_has(): void
    {
        $next = $this->createNextYear();
        SchoolTable::query('grades')->insert(['session_year_id' => $next->id, 'starting_range' => 0, 'ending_range' => 100, 'grade' => 'P', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->actingAs($this->superAdmin())->postJson(route('new-year.make-current'), ['year_id' => $next->id, 'carry' => ['grading', 'timetable']])->assertOk();

        // The timetable needs its subject teachers, so they are carried with it.
        $response->assertJson(['data' => ['copied' => ['teachers', 'timetable'], 'skipped' => ['grading']]]);
        $this->assertSame(1, DB::table('grades')->where('session_year_id', $next->id)->count());
        $this->assertSame(1, DB::table('timetables')->where('session_year_id', $next->id)->count());
        $lesson = DB::table('timetables')->where('session_year_id', $next->id)->first();
        $this->assertSame($next->id, (int) DB::table('subject_teachers')->where('id', $lesson->subject_teacher_id)->value('session_year_id'), 'The lesson points at next year\'s subject teacher');
    }

    public function test_carried_rows_point_at_next_years_terms_and_subject_teachers(): void
    {
        $termOne = (int) DB::table('terms')->where('session_year_id', $this->current->id)->value('id');
        foreach (['class_subjects', 'class_teachers', 'subject_teachers', 'timetables'] as $table) {
            DB::table($table)->where('session_year_id', $this->current->id)->update(['term_id' => $termOne]);
        }
        $next = $this->createNextYear();
        $nextTermOne = (int) DB::table('terms')->where('session_year_id', $next->id)->where('name', 'Term 1')->value('id');

        $this->actingAs($this->superAdmin())->postJson(route('new-year.make-current'), [
            'year_id' => $next->id,
            'carry' => ['subjects', 'teachers', 'timetable'],
        ])->assertOk();

        foreach (['class_subjects', 'class_teachers', 'subject_teachers', 'timetables'] as $table) {
            $this->assertSame([$nextTermOne], DB::table($table)->where('session_year_id', $next->id)->distinct()->pluck('term_id')->map(fn ($id) => (int) $id)->all(), "$table points at next year's Term 1");
        }
        $lesson = DB::table('timetables')->where('session_year_id', $next->id)->first();
        $subjectTeacher = DB::table('subject_teachers')->where('id', $lesson->subject_teacher_id)->first();
        $this->assertSame($next->id, (int) $subjectTeacher->session_year_id);
        $this->assertSame($nextTermOne, (int) $subjectTeacher->term_id);
        // The current year's rows keep their own term.
        $this->assertSame([$termOne], DB::table('timetables')->where('session_year_id', $this->current->id)->distinct()->pluck('term_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_promotions_are_checked_per_learner_not_by_count(): void
    {
        $next = $this->createNextYear();
        $service = app(NewYearService::class);
        $promotions = fn () => collect($service->yearEndChecklist($this->current, $next, $this->superAdmin()))->firstWhere('key', 'promotions');

        // A new admission for next year does not stand in for this year's learner.
        $newcomer = SchoolTable::query('students')->insertGetId(['user_id' => User::forceCreate(['first_name' => 'Nia', 'last_name' => 'New', 'email' => 'GR-2', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0'])->id, 'class_id' => DB::table('classes')->value('id'), 'class_section_id' => $this->ids['homeroom_b'], 'category_id' => DB::table('categories')->value('id'), 'admission_no' => 'GR-2', 'roll_number' => 2, 'admission_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        SchoolTable::query('student_sessions')->insert(['student_id' => $newcomer, 'class_section_id' => $this->ids['homeroom_b'], 'session_year_id' => $next->id, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertFalse($promotions()['ok']);

        SchoolTable::query('student_sessions')->insert(['student_id' => $this->ids['student'], 'class_section_id' => $this->ids['homeroom_b'], 'session_year_id' => $next->id, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertTrue($promotions()['ok']);
    }

    public function test_the_preview_says_what_the_switch_will_do(): void
    {
        $next = $this->createNextYear();

        $preview = $this->actingAs($this->superAdmin())
            ->getJson(route('new-year.preview', ['year_id' => $next->id, 'carry' => ['subjects', 'grading']]))
            ->assertOk()->json('data');

        $carry = collect($preview['carry'])->keyBy('key');
        $this->assertTrue($carry['subjects']['copies']);
        $this->assertSame(2, $carry['subjects']['source']);
        $this->assertArrayNotHasKey('fees', $carry, 'Financial plans are configured explicitly, never copied into old tables.');
        $this->assertFalse($carry['timetable']['copies']);
        $this->assertSame(1, $preview['learners']);
        // Previewing changed nothing.
        $this->assertSame(0, DB::table('class_subjects')->where('session_year_id', $next->id)->count());
        $this->assertSame((string) $this->current->id, (string) getSettings('session_year')['session_year']);
    }

    public function test_an_ended_or_current_year_cannot_be_switched_to(): void
    {
        $ended = SessionYear::create(['name' => 'Old', 'start_date' => '2019-01-10', 'end_date' => '2019-12-01']);
        $admin = $this->actingAs($this->superAdmin());

        $admin->postJson(route('new-year.make-current'), ['year_id' => $ended->id])->assertStatus(422)->assertJson(['error' => true]);
        $admin->postJson(route('new-year.make-current'), ['year_id' => $this->current->id])->assertStatus(422)->assertJson(['error' => true]);
        $this->assertSame((string) $this->current->id, (string) getSettings('session_year')['session_year']);
    }

    public function test_next_year_is_validated_field_by_field(): void
    {
        $years = SessionYear::count();

        $response = $this->actingAs($this->superAdmin())->postJson(route('new-year.store'), [
            'name' => '2026',
            'start_date' => now()->subYears(2)->toDateString(),
            'end_date' => now()->subYears(3)->toDateString(),
            'fee_due_date' => '',
            'fee_due_charges' => 150,
            'terms' => [['name' => 'Term 1', 'start_date' => now()->addMonths(3)->toDateString(), 'end_date' => now()->addMonths(2)->toDateString()]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'start_date', 'end_date', 'terms.0.end_date']);
        $this->assertSame($years, SessionYear::count(), 'Nothing is created when the form has errors');
    }

    public function test_only_people_who_manage_years_can_use_the_wizard(): void
    {
        $teacher = User::forceCreate(['first_name' => 'T', 'last_name' => 'Teacher', 'email' => 'teacher@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0']);
        $teacher->assignRole('Teacher');
        $next = $this->createNextYear();

        $this->actingAs($teacher)->get(route('new-year.index'))->assertRedirect(route('home'));
        $this->postJson(route('new-year.store'), $this->nextYear(['name' => '2028']))->assertForbidden();
        $this->getJson(route('new-year.preview', ['year_id' => $next->id]))->assertForbidden();
        $this->postJson(route('new-year.make-current'), ['year_id' => $next->id, 'carry' => ['subjects']])->assertForbidden();

        $this->assertDatabaseMissing('session_years', ['name' => '2028']);
        $this->assertSame((string) $this->current->id, (string) getSettings('session_year')['session_year']);
    }

    /** @return array<string, mixed> */
    private function nextYear(array $overrides = []): array
    {
        $start = now()->addMonths(2)->startOfMonth();

        return $overrides + [
            'name' => '2027',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addMonths(11)->toDateString(),
            'fee_due_date' => $start->copy()->addMonth()->toDateString(),
            'fee_due_charges' => 5,
            'terms' => [
                ['name' => 'Term 1', 'start_date' => $start->toDateString(), 'end_date' => $start->copy()->addMonths(3)->toDateString()],
                ['name' => 'Term 2', 'start_date' => $start->copy()->addMonths(4)->toDateString(), 'end_date' => $start->copy()->addMonths(6)->toDateString()],
                ['name' => 'Term 3', 'start_date' => '', 'end_date' => ''],
            ],
        ];
    }

    private function createNextYear(): SessionYear
    {
        $this->actingAs($this->superAdmin())->postJson(route('new-year.store'), $this->nextYear())->assertOk();

        return SessionYear::where('name', '2027')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $rows = [];
        foreach (self::YEAR_TABLES as $table) {
            $rows[$table] = DB::table($table)->where('session_year_id', $this->current->id)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        $rows['year'] = (array) DB::table('session_years')->where('id', $this->current->id)->first();
        $rows['setting'] = getSettings('session_year')['session_year'];
        $rows['students'] = DB::table('students')->orderBy('id')->get(['id', 'class_section_id'])->map(fn ($row) => (array) $row)->all();

        return $rows;
    }

    private function setUpCurrentYear(): void
    {
        $now = ['created_at' => now(), 'updated_at' => now()];
        $year = $this->current->id;
        SchoolTable::query('terms')->insert(['name' => 'Term 1', 'start_date' => $this->current->start_date, 'end_date' => $this->current->end_date, 'session_year_id' => $year] + $now);
        $medium = SchoolTable::query('mediums')->insertGetId(['name' => 'English'] + $now);
        $section = SchoolTable::query('sections')->insertGetId(['name' => 'A'] + $now);
        $classA = SchoolTable::query('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $medium] + $now);
        $classB = SchoolTable::query('classes')->insertGetId(['name' => 'Grade 6', 'medium_id' => $medium] + $now);
        $this->ids['homeroom_a'] = SchoolTable::query('class_sections')->insertGetId(['class_id' => $classA, 'section_id' => $section] + $now);
        $this->ids['homeroom_b'] = SchoolTable::query('class_sections')->insertGetId(['class_id' => $classB, 'section_id' => $section] + $now);
        SchoolTable::query('class_session_configs')->insert(['class_id' => $classA, 'session_year_id' => $year, 'include_terms' => 0] + $now);
        foreach (['Maths', 'English'] as $name) {
            $subject = SchoolTable::query('subjects')->insertGetId(['name' => $name, 'code' => strtoupper(substr($name, 0, 3)), 'bg_color' => '#5c6bc0', 'image' => 's.png', 'medium_id' => $medium, 'type' => 'Theory'] + $now);
            SchoolTable::query('class_subjects')->insert(['class_id' => $classA, 'subject_id' => $subject, 'type' => 'Compulsory', 'session_year_id' => $year] + $now);
            $this->ids['subject'] = $subject;
        }
        $teacherUser = User::forceCreate(['first_name' => 'Tara', 'last_name' => 'Teacher', 'email' => 'tara@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0']);
        $teacherUser->assignRole('Teacher');
        $teacher = SchoolTable::query('teachers')->insertGetId(['user_id' => $teacherUser->id, 'qualification' => 'B.Ed'] + $now);
        SchoolTable::query('class_teachers')->insert(['class_section_id' => $this->ids['homeroom_a'], 'class_teacher_id' => $teacher, 'session_year_id' => $year] + $now);
        $subjectTeacher = SchoolTable::query('subject_teachers')->insertGetId(['class_section_id' => $this->ids['homeroom_a'], 'subject_id' => $this->ids['subject'], 'teacher_id' => $teacher, 'session_year_id' => $year] + $now);
        SchoolTable::query('timetables')->insert(['session_year_id' => $year, 'subject_teacher_id' => $subjectTeacher, 'class_section_id' => $this->ids['homeroom_a'], 'start_time' => '08:00:00', 'end_time' => '08:45:00', 'day' => 1, 'day_name' => 'Monday'] + $now);
        foreach ([[0, 49, 'C'], [50, 79, 'B'], [80, 100, 'A']] as [$from, $to, $grade]) {
            SchoolTable::query('grades')->insert(['session_year_id' => $year, 'starting_range' => $from, 'ending_range' => $to, 'grade' => $grade] + $now);
        }
        $type = SchoolTable::query('fees_types')->insertGetId(['name' => 'Tuition', 'description' => 'Tuition'] + $now);
        SchoolTable::query('fees_classes')->insert(['session_year_id' => $year, 'class_id' => $classA, 'fees_type_id' => $type, 'choiceable' => 0, 'amount' => 1000] + $now);
        SchoolTable::query('leave_masters')->insert(['total_leave' => '12', 'holiday_days' => '0', 'session_year_id' => $year] + $now);

        $learnerUser = User::forceCreate(['first_name' => 'Asha', 'last_name' => 'Learner', 'email' => 'GR-1', 'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0']);
        $learnerUser->assignRole('Student');
        $category = SchoolTable::query('categories')->insertGetId(['name' => 'General', 'status' => 1] + $now);
        $this->ids['student'] = SchoolTable::query('students')->insertGetId(['user_id' => $learnerUser->id, 'class_id' => $classA, 'class_section_id' => $this->ids['homeroom_a'], 'category_id' => $category, 'admission_no' => 'GR-1', 'roll_number' => 1, 'admission_date' => now()->toDateString()] + $now);
        SchoolTable::query('student_sessions')->insert(['student_id' => $this->ids['student'], 'class_section_id' => $this->ids['homeroom_a'], 'session_year_id' => $year, 'status' => 1] + $now);
    }
}
