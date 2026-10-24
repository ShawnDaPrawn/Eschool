<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Actions\Transfers\YearIdMap;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TermBreaks;
use App\Services\Academics\TermCalendar;
use App\Services\SessionYearService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class SchoolTermsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::query()->whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::query()->where('session_year_id', $this->year)->delete();
    }

    private function admin(): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->superAdmin()->createToken('terms')->plainTextToken);
    }

    private function row(array $overrides = []): array
    {
        return $overrides + ['session_year_id' => $this->year, 'name' => 'Term 1', 'starts_on' => '2026-01-14', 'ends_on' => '2026-03-20', 'grade_from' => 0, 'grade_to' => 12];
    }

    private function create(array $overrides = []): int
    {
        return $this->postJson('/api/v2/academics/terms', $this->row($overrides))->assertOk()->json('data.id');
    }

    private function nextYear(int $number = 2027): SessionYear
    {
        return SessionYear::query()->create(['name' => (string) $number, 'start_date' => "$number-01-01", 'end_date' => "$number-12-31", 'fee_due_date' => "$number-02-01"]);
    }

    public function test_admin_manages_terms_with_optional_metadata_and_audits_without_mass_assignment(): void
    {
        $this->admin();
        $id = $this->create(['school_id' => 2]);
        $this->assertSame(1, (int) Term::findOrFail($id)->school_id);
        $this->getJson('/api/v2/academics/terms?session_year_id='.$this->year)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.terms.0.marks_lock_on', null)->assertJsonPath('data.terms.0.grade_from', 0);
        $this->putJson('/api/v2/academics/terms/'.$id, $this->row(['marks_lock_on' => '2026-03-18', 'reports_on' => '2026-03-25']))
            ->assertOk()->assertJsonPath('data.reports_on', '2026-03-25');
        $this->assertSame(2, DB::table('audit_log')->where('action', 'academics.term_saved')->count());
        $this->putJson('/api/v2/academics/terms/'.$id, $this->row(['marks_lock_on' => null, 'reports_on' => null]))
            ->assertOk()->assertJsonPath('data.marks_lock_on', null)->assertJsonPath('data.reports_on', null);
        $this->deleteJson('/api/v2/academics/terms/'.$id)->assertOk()->assertJsonPath('data', null);
        $this->assertSoftDeleted('terms', ['id' => $id, 'school_id' => 1]);
    }

    public function test_grade_bands_can_overlap_in_dates_but_intersecting_grade_and_date_ranges_cannot(): void
    {
        $this->admin();
        $this->create(['grade_to' => 6]);
        $this->create(['grade_from' => 7]);
        $this->postJson('/api/v2/academics/terms', $this->row(['name' => 'Conflict', 'grade_from' => 6, 'grade_to' => 7]))->assertUnprocessable();
        $this->postJson('/api/v2/academics/terms', $this->row(['starts_on' => '2026-03-20', 'ends_on' => '2026-04-10']))->assertUnprocessable();
        $this->create(['name' => 'Term 2', 'starts_on' => '2026-03-21', 'ends_on' => '2026-05-20']);
        $this->getJson('/api/v2/academics/terms?session_year_id='.$this->year.'&grade=0')->assertOk()->assertJsonCount(2, 'data.terms');
        $this->assertSame(3, Term::query()->where('session_year_id', $this->year)->count());
    }

    public function test_dates_ranges_and_lock_report_order_are_validated_without_requiring_optional_dates(): void
    {
        $this->admin();
        foreach ([['starts_on' => '2025-12-31'], ['ends_on' => '2027-01-01'], ['ends_on' => '2026-01-01'],
            ['grade_from' => 9, 'grade_to' => 3], ['grade_from' => -1], ['grade_to' => 13], ['starts_on' => '2026-02-30'],
            ['marks_lock_on' => '2026-01-01'], ['marks_lock_on' => '2026-03-18', 'reports_on' => '2026-03-17']] as $bad) {
            $this->postJson('/api/v2/academics/terms', $this->row($bad))->assertUnprocessable();
        }
        $this->create(['reports_on' => '2027-01-02']); // Report preparation may follow year end.
    }

    public function test_teacher_parent_and_learner_read_calendars_but_cannot_manage_them(): void
    {
        foreach (['tara_user', 'farid_user', 'asha_user'] as $key) {
            $this->app['auth']->forgetGuards();
            $this->flushHeaders()->withToken(User::findOrFail($this->schoolA->ids[$key])->createToken('terms')->plainTextToken);
            $this->getJson('/api/v2/academics/terms?session_year_id='.$this->year)->assertOk();
            $this->postJson('/api/v2/academics/terms', $this->row())->assertForbidden();
            $this->putJson('/api/v2/academics/terms/999999', $this->row())->assertForbidden();
            $this->deleteJson('/api/v2/academics/terms/999999')->assertForbidden();
            $this->getJson('/api/v2/academics/term-templates')->assertForbidden();
            $this->postJson('/api/v2/academics/term-templates', ['name' => 'No', 'session_year_id' => $this->year])->assertForbidden();
        }
    }

    public function test_school_year_term_and_template_ownership_are_checked(): void
    {
        $this->admin();
        $foreign = $this->schoolB->ids['term'];
        $this->getJson('/api/v2/academics/terms?session_year_id='.$this->schoolB->ids['session_year'])->assertNotFound();
        $this->postJson('/api/v2/academics/terms', $this->row(['session_year_id' => $this->schoolB->ids['session_year']]))->assertNotFound();
        $this->putJson('/api/v2/academics/terms/'.$foreign, $this->row())->assertNotFound();
        $this->deleteJson('/api/v2/academics/terms/'.$foreign)->assertNotFound();
        $foreignTemplate = DB::table('term_templates')->insertGetId(['school_id' => 2, 'name' => 'Other calendar', 'base_year' => 2026, 'terms' => '[]']);
        $this->postJson('/api/v2/academics/term-templates/'.$foreignTemplate.'/apply', ['session_year_id' => $this->year, 'apply' => true])->assertNotFound();
        $this->withHeader('X-School-Id', '2')->postJson('/api/v2/academics/terms', $this->row())->assertForbidden();
    }

    public function test_terms_cannot_be_moved_to_another_year(): void
    {
        $this->admin();
        $id = $this->create();
        $next = $this->nextYear();
        $this->putJson('/api/v2/academics/terms/'.$id, $this->row(['session_year_id' => $next->id]))->assertUnprocessable();
        $this->assertSame($this->year, (int) Term::findOrFail($id)->session_year_id);
    }

    public function test_a_term_referenced_by_an_archived_assignment_cannot_be_removed(): void
    {
        $this->admin();
        $id = $this->create();
        DB::table('assignments')->where('school_id', 1)->update(['term_id' => $id, 'deleted_at' => now()]);
        $this->deleteJson('/api/v2/academics/terms/'.$id)->assertUnprocessable();
        $this->assertNotNull(Term::find($id));
    }

    public function test_templates_preview_without_writes_and_apply_atomically_and_repeatably(): void
    {
        $this->admin();
        $this->create(['grade_to' => 6, 'marks_lock_on' => '2026-03-18', 'reports_on' => '2026-03-25']);
        $this->create(['grade_from' => 7]);
        $template = $this->postJson('/api/v2/academics/term-templates', ['name' => 'School calendar', 'session_year_id' => $this->year])->assertOk()->json('data.id');
        $this->getJson('/api/v2/academics/term-templates')->assertOk()->assertJsonCount(1, 'data.templates');
        $next = $this->nextYear();
        $url = '/api/v2/academics/term-templates/'.$template.'/apply';
        $this->postJson($url, ['session_year_id' => $next->id])->assertOk()->assertJsonPath('data.applied', false)
            ->assertJsonPath('data.would_create_count', 2)->assertJsonPath('data.terms.0.starts_on', '2027-01-14');
        $this->assertSame(0, $next->terms()->count());
        $this->assertSame(0, DB::table('audit_log')->where('action', 'academics.term_template_applied')->count());
        $this->postJson($url, ['session_year_id' => $next->id, 'apply' => true])->assertOk()->assertJsonPath('data.created_count', 2);
        $this->postJson($url, ['session_year_id' => $next->id, 'apply' => true])->assertOk()->assertJsonPath('data.created_count', 0)->assertJsonPath('data.skipped_count', 2);
        $this->assertSame(2, $next->terms()->count());
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.term_template_applied')->count());
    }

    public function test_template_conflicts_or_invalid_leap_days_do_not_partially_apply(): void
    {
        $this->admin();
        $this->create();
        $template = $this->postJson('/api/v2/academics/term-templates', ['name' => 'Conflicting', 'session_year_id' => $this->year])->assertOk()->json('data.id');
        $next = $this->nextYear();
        $this->create(['session_year_id' => $next->id, 'name' => 'Different dates', 'starts_on' => '2027-01-15', 'ends_on' => '2027-03-20']);
        $this->postJson('/api/v2/academics/term-templates/'.$template.'/apply', ['session_year_id' => $next->id, 'apply' => true])->assertUnprocessable();
        $this->assertSame(1, $next->terms()->count());
        $leap = $this->nextYear(2028);
        $this->create(['session_year_id' => $leap->id, 'starts_on' => '2028-02-29', 'ends_on' => '2028-03-20']);
        $template = $this->postJson('/api/v2/academics/term-templates', ['name' => 'Leap calendar', 'session_year_id' => $leap->id])->assertOk()->json('data.id');
        $target = $this->nextYear(2029);
        $this->postJson('/api/v2/academics/term-templates/'.$template.'/apply', ['session_year_id' => $target->id, 'apply' => true])->assertUnprocessable();
        $this->assertSame(0, $target->terms()->count());
    }

    public function test_new_year_can_use_a_template_without_changing_current_year(): void
    {
        $this->admin();
        $id = $this->create(['grade_to' => 6, 'marks_lock_on' => '2026-03-18']);
        $template = $this->postJson('/api/v2/academics/term-templates', ['name' => 'Wizard calendar', 'session_year_id' => $this->year])->assertOk()->json('data.id');
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->actingAs($this->superAdmin(), 'web')->postJson(route('new-year.store'), [
            'name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'fee_due_date' => '2027-02-01', 'fee_due_charges' => 0, 'term_template_id' => $template,
        ])->assertOk()->assertJsonPath('error', false);
        $next = SessionYear::query()->where('name', '2027')->sole();
        $this->assertSame('2027-03-18', $next->terms()->sole()->marks_lock_on->toDateString());
        $this->assertSame(6, $next->terms()->sole()->grade_to);
        $this->assertSame((string) $this->year, (string) schoolSetting('session_year'));
        $this->assertSame('2026-01-14', Term::findOrFail($id)->start_date->toDateString());
    }

    public function test_school_wide_breaks_merge_grade_intervals_without_false_holidays(): void
    {
        $this->admin();
        $this->create(['grade_to' => 6, 'ends_on' => '2026-03-20']);
        $this->create(['grade_from' => 7, 'starts_on' => '2026-02-01', 'ends_on' => '2026-03-10']);
        $this->create(['name' => 'Term 2', 'starts_on' => '2026-03-21', 'ends_on' => '2026-05-20']);
        $this->assertSame([], app(TermBreaks::class)->forYear($this->year));
        $this->create(['name' => 'Term 3', 'starts_on' => '2026-06-01', 'ends_on' => '2026-08-20']);
        $this->assertSame([['start' => '2026-05-21', 'end' => '2026-05-31']], app(TermBreaks::class)->forYear($this->year));
    }

    public function test_current_term_and_year_transfer_matching_respect_grade_ranges(): void
    {
        $this->admin();
        $lower = $this->create(['grade_to' => 6]);
        $upper = $this->create(['grade_from' => 7]);
        $class = ClassSchool::query()->findOrFail($this->schoolA->ids['class_a']);
        $class->grade_level_id = GradeLevel::query()->where('code', '7')->value('id');
        $class->save();
        $this->travelTo(Carbon::parse('2026-02-15'));
        $this->assertSame($upper, Term::currentForClass($this->year, $class->id)->id);
        $this->travelBack();
        $next = $this->nextYear();
        $newUpper = $this->create(['session_year_id' => $next->id, 'grade_from' => 7, 'starts_on' => '2027-01-14', 'ends_on' => '2027-03-20']);
        $newLower = $this->create(['session_year_id' => $next->id, 'grade_to' => 6, 'starts_on' => '2027-01-14', 'ends_on' => '2027-03-20']);
        $map = YearIdMap::terms($this->year, $next->id);
        $this->assertSame($newUpper, $map[$upper]);
        $this->assertSame($newLower, $map[$lower]);
    }

    public function test_expired_permission_cannot_write_or_replay_an_idempotent_calendar_response(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $this->admin()->withHeader('Idempotency-Key', 'terms-expiry')->postJson('/api/v2/academics/terms', $this->row())->assertOk();
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->postJson('/api/v2/academics/terms', $this->row())->assertForbidden();
        $this->assertSame(1, Term::query()->where('session_year_id', $this->year)->count());
    }

    public function test_panel_renders_calendar_forms_and_checks_foreign_years(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->get('/term?session_year_id='.$this->year)->assertOk()
            ->assertSee('School terms')->assertSee('Save calendar template')->assertSee('No terms yet.');
        $this->get('/term?session_year_id='.$this->schoolB->ids['session_year'])->assertNotFound();
    }

    public function test_timetable_term_picker_filters_by_class_grade_without_guessing(): void
    {
        $this->admin();
        $this->create(['grade_to' => 6]);
        $upper = $this->create(['grade_from' => 7]);
        $class = ClassSchool::query()->findOrFail($this->schoolA->ids['class_a']);
        $class->grade_level_id = GradeLevel::query()->where('code', '7')->value('id');
        $class->save();
        DB::table('class_session_configs')->updateOrInsert(
            ['class_id' => $class->id, 'session_year_id' => $this->year],
            ['include_terms' => 1, 'school_id' => 1]
        );
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->actingAs($this->superAdmin(), 'web');
        $url = '/get-class-terms?class_id='.$class->id.'&session_year_id='.$this->year;
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'terms')->assertJsonPath('terms.0.id', $upper);
        $class->grade_level_id = null;
        $class->save();
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'terms');
    }

    public function test_class_terms_are_enabled_without_deleting_or_reassigning_academic_records(): void
    {
        $this->admin();
        $this->create();
        $classId = $this->schoolA->ids['class_a'];
        DB::table('class_session_configs')->where('class_id', $classId)->where('session_year_id', $this->year)->delete();
        $before = [];
        foreach (TermCalendar::LINKS as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->actingAs($this->superAdmin(), 'web');
        $payload = ['class_id' => $classId, 'session_year_id' => $this->year];
        $this->post(route('term.classes.enable'), $payload)->assertRedirect(route('class-subject-edit.index', ['id' => $classId, 'session_year_id' => $this->year]));
        $this->assertTrue(ClassSchool::findOrFail($classId)->getIncludeTermsForSession($this->year));
        $this->post(route('term.classes.enable'), $payload)->assertRedirect();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.class_terms_enabled')->count());
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table.' must be preserved');
        }
        $this->postJson(route('term.classes.enable'), $payload + ['school_id' => 2])->assertRedirect();
        $this->postJson(route('term.classes.enable'), ['class_id' => $this->schoolB->ids['class_a'], 'session_year_id' => $this->year])->assertNotFound();
        $this->postJson(route('term.classes.enable'), ['class_id' => $classId, 'session_year_id' => $this->schoolB->ids['session_year']])->assertNotFound();
        $this->postJson(route('term.classes.enable'), ['class_id' => $classId, 'session_year_id' => $this->nextYear()->id])->assertUnprocessable();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')
            ->postJson(route('term.classes.enable'), $payload)->assertForbidden();
    }

    public function test_an_unmapped_term_cannot_silently_become_a_whole_year_allocation(): void
    {
        $this->assertNull(YearIdMap::term(null, []));
        $this->expectException(\DomainException::class);
        YearIdMap::term(123, []);
    }

    public function test_switching_year_preserves_historical_lesson_term_links(): void
    {
        $this->admin();
        $id = $this->create();
        DB::table('lessons')->where('school_id', 1)->update(['term_id' => $id]);
        $count = DB::table('lessons')->where('school_id', 1)->where('term_id', $id)->count();
        $this->assertGreaterThan(0, $count);
        app(SessionYearService::class)->makeCurrent($this->nextYear());
        $this->assertSame($count, DB::table('lessons')->where('school_id', 1)->where('term_id', $id)->count());
    }
}
