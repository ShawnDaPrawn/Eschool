<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\SchoolStage;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The age stage of each grade and stage-aware features (Action Plan 4.1).
 * engineering/specs/platform/school-stages.md.
 */
class SchoolStagesTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // School B is built after the migrations, as an HQ-created school would be seeded.
        app(SchoolStages::class)->seedDefaults(self::SCHOOL_B_ID);
    }

    private function grade(string $code): int
    {
        return (int) GradeLevel::query()->where('code', $code)->value('id');
    }

    /** Puts a School A homeroom (class_a: Asha and Ben, class_c: Cara) in a grade. */
    private function homeroomGrade(string $class, string $code): void
    {
        ClassSchool::query()->whereKey($this->schoolA->ids[$class])->update(['grade_level_id' => $this->grade($code)]);
    }

    /** @return array<int, string> */
    private function stagesOf(int $school): array
    {
        return DB::table('school_stages')->where('school_id', $school)->pluck('stage', 'grade_level_id')->map(fn ($stage) => (string) $stage)->all();
    }

    private function learnerSignIn(string $grNumber, array $headers = []): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->postJson('/api/student/login', ['gr_number' => $grNumber, 'password' => 'secret123'], $headers);
    }

    private function asLearner(string $name): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1']);
    }

    public function test_every_school_starts_with_the_owners_default_stages(): void
    {
        $expected = [];
        foreach (SchoolStages::DEFAULTS as $code => $stage) {
            $expected[$this->grade((string) $code)] = $stage;
        }
        $this->assertCount(13, $expected);
        $this->assertEqualsCanonicalizing($expected, $this->stagesOf(1));
        $this->assertEqualsCanonicalizing($expected, $this->stagesOf(self::SCHOOL_B_ID));
        $this->assertSame(0, DB::table('school_stages')->whereNotNull('changed_by')->count());
        $this->assertSame(SchoolStages::EARLY_YEARS, $expected[$this->grade('2')]);
        $this->assertSame(SchoolStages::JUNIOR, $expected[$this->grade('7')]);
        $this->assertSame(SchoolStages::HIGH_SCHOOL, $expected[$this->grade('8')]);

        // Seeding again adds nothing; a school without rows still reads the defaults.
        app(SchoolStages::class)->seedDefaults(self::SCHOOL_B_ID);
        $this->assertSame(13, DB::table('school_stages')->where('school_id', self::SCHOOL_B_ID)->count());
        DB::table('school_stages')->where('school_id', 1)->delete();
        app(SchoolStages::class)->forget();
        $this->assertEqualsCanonicalizing($expected, app(SchoolStages::class)->map());
    }

    public function test_a_school_leader_moves_a_grade_and_the_change_is_audited_in_their_school_only(): void
    {
        $admin = $this->superAdmin();
        $schoolB = $this->stagesOf(self::SCHOOL_B_ID);
        $audits = DB::table('audit_log')->count();

        $this->actingAs($admin)->get('/school-stages')->assertOk()
            ->assertSee('School stages')->assertSee('Grade R')->assertSee('Grade 12')->assertSee('Early years')->assertSee('High school')
            ->assertDontSee(self::SCHOOL_B_MARKER);

        $seven = $this->grade('7');
        $this->put('/school-stages', ['stages' => [$seven => SchoolStages::HIGH_SCHOOL, $this->grade('8') => SchoolStages::HIGH_SCHOOL]])
            ->assertRedirect('/school-stages')->assertSessionHas('success', '1 grade moved to a new stage.');

        $row = SchoolStage::query()->where('grade_level_id', $seven)->firstOrFail();
        $this->assertSame(SchoolStages::HIGH_SCHOOL, $row->stage);
        $this->assertSame((int) $admin->id, $row->changed_by);
        // Only the grade that changed is written and audited.
        $this->assertSame($audits + 1, DB::table('audit_log')->count());
        $audit = DB::table('audit_log')->where('subject_type', $row->getMorphClass())->where('subject_id', $row->id)->where('action', 'updated')->sole();
        $this->assertSame(1, (int) $audit->school_id);
        $this->assertSame((int) $admin->id, (int) $audit->actor_id);
        $this->assertSame(SchoolStages::JUNIOR, json_decode((string) $audit->before, true)['stage']);
        $this->assertSame(SchoolStages::HIGH_SCHOOL, json_decode((string) $audit->after, true)['stage']);
        $this->assertTrue(OutboxEvent::query()->where('school_id', 1)->where('type', 'context.changed')->get()
            ->contains(fn (OutboxEvent $event) => ($event->payload['reason'] ?? null) === 'stages'));

        $this->get('/school-stages')->assertSee('changed by the school');
        $this->put('/school-stages', ['stages' => [$seven => SchoolStages::HIGH_SCHOOL]])->assertSessionHas('success', 'No grade changed stage.');
        $this->assertSame($audits + 1, DB::table('audit_log')->count());

        $this->assertSame($schoolB, $this->stagesOf(self::SCHOOL_B_ID));
    }

    public function test_unknown_grades_and_stages_are_refused_and_nothing_is_written(): void
    {
        $before = $this->stagesOf(1);
        $audits = DB::table('audit_log')->count();
        $this->actingAs($this->superAdmin());

        $this->put('/school-stages', ['stages' => [$this->grade('3') => 'toddlers']])->assertSessionHasErrors('stages.'.$this->grade('3'));
        $this->put('/school-stages', ['stages' => [999999 => SchoolStages::JUNIOR]])->assertSessionHasErrors('stages');
        $this->put('/school-stages', ['stages' => []])->assertSessionHasErrors('stages');
        // A school_id in the request is not a way into another school.
        $this->put('/school-stages', ['school_id' => self::SCHOOL_B_ID, 'stages' => [$this->grade('3') => 'nursery']])->assertSessionHasErrors();

        $this->assertSame($before, $this->stagesOf(1));
        $this->assertSame($audits, DB::table('audit_log')->count());
    }

    public function test_teachers_parents_and_learners_cannot_view_or_change_the_stages(): void
    {
        $before = $this->stagesOf(1);
        $schoolB = $this->stagesOf(self::SCHOOL_B_ID);
        $audits = DB::table('audit_log')->count();

        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            $this->app['auth']->forgetGuards();
            $this->actingAs(User::query()->findOrFail($this->schoolA->ids[$person]));
            $this->get('/school-stages')->assertForbidden();
            $this->put('/school-stages', ['stages' => [$this->grade('5') => SchoolStages::EARLY_YEARS]])->assertForbidden();
        }

        $this->assertSame($before, $this->stagesOf(1));
        $this->assertSame($schoolB, $this->stagesOf(self::SCHOOL_B_ID));
        $this->assertSame($audits, DB::table('audit_log')->count());
        $this->assertFalse(SchoolStages::mayManage(User::query()->findOrFail($this->schoolA->ids['tara_user'])));
    }

    public function test_the_service_refuses_a_caller_without_the_right_even_without_the_form(): void
    {
        $this->expectException(HttpException::class);
        try {
            app(SchoolStages::class)->update(User::query()->findOrFail($this->schoolA->ids['tara_user']), [$this->grade('5') => SchoolStages::EARLY_YEARS]);
        } finally {
            $this->assertSame(SchoolStages::JUNIOR, $this->stagesOf(1)[$this->grade('5')]);
        }
    }

    public function test_an_early_years_learner_cannot_sign_in_but_their_guardian_can(): void
    {
        $this->homeroomGrade('class_a', '1');
        $this->homeroomGrade('class_c', '8');
        app(GuardianService::class)->syncLegacy(Students::query()->findOrFail($this->schoolA->ids['asha']));
        $asha = User::query()->findOrFail($this->schoolA->ids['asha_user']);
        $tokens = $asha->tokens()->count();

        $this->learnerSignIn('GR-ASHA')->assertForbidden()->assertJsonPath('message', SchoolStages::SIGN_IN_REFUSED);
        $this->assertSame($tokens, $asha->tokens()->count());
        // From the web portal: refused, and no session is left signed in.
        $this->learnerSignIn('GR-ASHA', ['Origin' => 'http://localhost'])->assertForbidden();
        $this->assertGuest('web');

        // A high school learner at the same school still signs in.
        $this->learnerSignIn('GR-CARA')->assertOk()->assertJsonPath('error', false);

        // The guardian keeps their own sign-in and still sees the child.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $children = $this->postJson('/api/parent/login', ['email' => 'farid@example.com', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('error', false)->json('data.children');
        $this->assertContains($this->schoolA->ids['asha'], array_column($children, 'id'));

        // When the school moves Grade 1 into the junior stage, Asha signs in.
        app(SchoolStages::class)->update($this->superAdmin(), [$this->grade('1') => SchoolStages::JUNIOR]);
        $this->learnerSignIn('GR-ASHA')->assertOk()->assertJsonPath('error', false);
    }

    public function test_each_school_decides_its_own_learners_stage(): void
    {
        // Both schools' Asha are in Grade 5. Only School B makes Grade 5 early years.
        $this->homeroomGrade('class_a', '5');
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            ClassSchool::query()->whereKey($this->schoolB->ids['class_a'])->update(['grade_level_id' => $this->grade('5')]);
            DB::table('school_stages')->where('school_id', self::SCHOOL_B_ID)->where('grade_level_id', $this->grade('5'))->update(['stage' => SchoolStages::EARLY_YEARS]);
        });

        $this->learnerSignIn('GR-ASHA')->assertOk()->assertJsonPath('error', false);
        $this->learnerSignIn('GR-BAOBAB-ASHA')->assertForbidden();
        $this->assertSame(SchoolStages::JUNIOR, $this->stagesOf(1)[$this->grade('5')]);
    }

    public function test_a_stage_limited_module_is_refused_to_a_learner_in_another_stage_and_never_to_guardians(): void
    {
        SchoolModule::query()->updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->homeroomGrade('class_a', '7');
        $this->assertSame(['junior', 'high_school'], config('modules.tutor.stages'));
        $this->assertSame(['junior', 'high_school'], config('modules.daily_practice.stages'));

        // Junior: the tutor is offered.
        $this->asLearner('asha');
        $this->assertContains('tutor', array_column($this->getJson('/api/v2/me/context')->assertOk()->json('data.modules'), 'key'));
        $this->assertNotSame(404, $this->getJson('/api/v2/tutor/configuration')->status());

        // The school moves Grade 7 into early years: Asha's existing app session loses the tutor.
        app(SchoolStages::class)->update($this->superAdmin(), [$this->grade('7') => SchoolStages::EARLY_YEARS]);
        $this->asLearner('asha');
        $this->assertNotContains('tutor', array_column($this->getJson('/api/v2/me/context')->assertOk()->json('data.modules'), 'key'));
        $this->getJson('/api/v2/tutor/configuration')->assertNotFound();
        $this->asLearner('asha');
        $this->getJson('/api/v2/tutor/sessions')->assertNotFound();

        // Her guardian and her teacher are not limited by stage.
        $this->app['auth']->forgetGuards();
        $gate = app(ModuleGate::class);
        $this->assertFalse($gate->allowsStageOf('tutor', User::query()->findOrFail($this->schoolA->ids['asha_user'])));
        $this->assertTrue($gate->allowsStageOf('tutor', User::query()->findOrFail($this->schoolA->ids['farid_user'])));
        $this->assertTrue($gate->allowsStageOf('tutor', User::query()->findOrFail($this->schoolA->ids['tara_user'])));
        $this->asLearner('farid');
        $this->assertContains('tutor', array_column($this->getJson('/api/v2/me/context')->assertOk()->json('data.modules'), 'key'));

        // A module without stages is never limited, and a learner whose homeroom has no grade is not limited.
        $this->assertTrue($gate->allowsStageOf('chat', User::query()->findOrFail($this->schoolA->ids['asha_user'])));
        $this->homeroomGrade('class_a', '7');
        ClassSchool::query()->whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => null]);
        app(SchoolStages::class)->forget();
        $this->assertTrue((new ModuleGate)->allowsStageOf('tutor', User::query()->findOrFail($this->schoolA->ids['asha_user'])));
    }
}
