<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Actions\Transfers\TransferCurriculumPlans;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\AcademicNumbers;
use App\Services\Academics\CurriculumPlans;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class CurriculumPlansTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $grade;

    private int $year;

    private int $term;

    private int $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        $this->year = $this->schoolA->ids['session_year'];
        $this->term = $this->schoolA->ids['term'];
        $this->subject = $this->schoolA->ids['maths'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->term)->update(['start_date' => '2026-01-14', 'end_date' => '2026-03-20']);
        $class = ClassSchool::findOrFail($this->schoolA->ids['class_a']);
        $class->grade_level_id = $this->grade;
        $class->save();
        $other = ClassSchool::findOrFail($this->schoolA->ids['class_c']);
        $other->grade_level_id = GradeLevel::where('code', '8')->value('id');
        $other->save();
    }

    private function input(): array
    {
        return ['subject_id' => $this->subject, 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'title' => 'Grade 7 Mathematics', 'annual_components' => ['sba' => 6000, 'exam' => 4000],
            'rounding' => 'half_up', 'absent_policy' => 'incomplete',
            'topics' => [['key' => '11111111-1111-4111-8111-111111111111', 'term_id' => $this->term, 'name' => 'Fractions', 'week_from' => 1, 'week_to' => 3]],
            'assessments' => [['term_id' => $this->term, 'components' => ['sba' => 10000], 'tasks' => [
                ['key' => '22222222-2222-4222-8222-222222222222', 'name' => 'Task 1', 'type' => 'test', 'component' => 'sba',
                    'annual_component' => 'sba', 'weight_bp' => 10000, 'annual_weight_bp' => 2500, 'max_mark' => '20.00', 'due_on' => '2026-02-10',
                    'topic_keys' => ['11111111-1111-4111-8111-111111111111'], 'questions' => [
                        ['number' => '1', 'max_mark' => '7.50', 'topic_keys' => ['11111111-1111-4111-8111-111111111111']],
                        ['number' => '2', 'max_mark' => '12.50', 'topic_keys' => []],
                    ]],
            ]]]];
    }

    private function apiAs(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('curriculum')->plainTextToken);
    }

    public function test_http_draft_read_update_publish_and_copy_use_the_same_contract(): void
    {
        $this->apiAs($this->superAdmin());
        $path = '/api/v2/academics/curriculum-plans';
        $data = $this->postJson($path, $this->input())->assertOk()->json('data');
        $id = $data['id'];
        $this->getJson($path)->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("$path/$id")->assertOk()->assertJsonPath('data.assessments.0.tasks.0.max_mark', '20.00');
        $input = $this->input();
        $input['revision'] = 1;
        $input['title'] = 'Revised draft';
        $this->putJson("$path/$id", $input)->assertOk()->assertJsonPath('data.revision', 2);
        $this->putJson("$path/$id", $input)->assertConflict();
        $assessment = $this->getJson("$path/$id")->json('data.assessments.0.id');
        $this->postJson("$path/$id/assessments/$assessment/publish", ['revision' => 2])->assertOk()->assertJsonPath('data.state', 'published');
        $this->postJson("$path/$id/copy", ['session_year_id' => $this->year])->assertOk()->assertJsonPath('data.state', 'draft');
        $this->putJson("$path/$id", $input)->assertConflict();
    }

    public function test_http_refuses_profiles_school_contexts_and_revoked_allocation_before_replay(): void
    {
        $path = '/api/v2/academics/curriculum-plans';
        $this->getJson($path)->assertUnauthorized();
        foreach (['asha_user', 'farid_user'] as $key) {
            $this->apiAs(User::findOrFail($this->schoolA->ids[$key]));
            $this->getJson($path)->assertForbidden();
            $this->postJson($path, $this->input())->assertForbidden();
        }
        $this->apiAs(User::findOrFail($this->schoolA->ids['tara_user']))->withHeader('Idempotency-Key', 'curriculum-draft');
        $id = $this->postJson($path, $this->input())->assertOk()->json('data.id');
        $this->postJson($path, $this->input())->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        DB::table('subject_teachers')->where('school_id', 1)->update(['deleted_at' => now()]);
        $this->postJson($path, $this->input())->assertForbidden();
        $this->getJson("$path/$id")->assertForbidden();
        $this->apiAs($this->superAdmin())->withHeader('X-School-Id', '2')->getJson("$path/$id")->assertForbidden();
        $this->apiAs($this->superAdmin());
        $foreign = $this->input();
        $foreign['subject_id'] = $this->schoolB->ids['maths'];
        $this->postJson($path, $foreign)->assertNotFound();
    }

    public function test_empty_draft_component_maps_are_json_objects_and_incomplete_future_terms_are_allowed(): void
    {
        $input = $this->input();
        $input['annual_components'] = [];
        $input['assessments'][0]['components'] = [];
        $input['assessments'][0]['tasks'] = [];
        $this->apiAs($this->superAdmin())->postJson('/api/v2/academics/curriculum-plans', $input)->assertOk();
    }

    public function test_panel_editor_uses_scoped_service_and_copies_definitions_as_drafts_for_year_rollover(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'web')->get(route('curriculum.create'))->assertOk()->assertSee('data-curriculum-editor', false);
        $this->post(route('curriculum.store'), ['definition' => json_encode($this->input())])->assertRedirect();
        $source = CurriculumPlan::query()->sole();
        $this->get(route('curriculum.index'))->assertOk()->assertSee('Grade 7 Mathematics');
        $this->get(route('curriculum.edit', $source->id))->assertOk();
        $target = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'fee_due_date' => '2027-02-01']);
        Term::create(['name' => 'Term 1', 'session_year_id' => $target->id, 'start_date' => '2027-01-14', 'end_date' => '2027-03-20', 'grade_from' => 0, 'grade_to' => 12]);
        app(TransferCurriculumPlans::class)->execute($this->year, $target->id);
        app(TransferCurriculumPlans::class)->execute($this->year, $target->id);
        $this->assertSame(1, CurriculumPlan::where('session_year_id', $target->id)->count());
        $this->assertSame('draft', CurriculumPlan::where('session_year_id', $target->id)->sole()->state);
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']), 'web')->get(route('curriculum.index'))->assertRedirect();
    }

    public function test_allocated_teacher_creates_draft_and_exact_question_maxima_are_preserved(): void
    {
        $actor = User::findOrFail($this->schoolA->ids['tara_user']);
        $plan = app(CurriculumPlans::class)->save($actor, $this->input() + ['school_id' => 2]);
        $this->assertSame(1, (int) $plan->school_id);
        $this->assertSame('draft', $plan->state);
        $this->assertSame(2000, $plan->assessments->first()->tasks->first()->max_mark_units);
        $this->assertSame([750, 1250], $plan->assessments->first()->tasks->first()->questions->pluck('max_mark_units')->all());
        $this->assertSame('20.00', app(CurriculumPlans::class)->data($plan)['assessments'][0]['tasks'][0]['max_mark']);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.curriculum_saved')->count());
    }

    public function test_teacher_only_lists_allocated_subject_grade_and_year_and_cannot_publish(): void
    {
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), $this->input());
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->assertSame(1, app(CurriculumPlans::class)->list($teacher, [])->total());
        $this->assertSame(0, app(CurriculumPlans::class)->list($other, [])->total());
        $this->expectException(HttpException::class);
        app(CurriculumPlans::class)->publish($teacher, $plan->id, $plan->assessments->first()->id, 1);
    }

    public function test_admin_publishes_a_term_without_needing_future_terms_and_published_definitions_are_immutable(): void
    {
        $service = app(CurriculumPlans::class);
        $plan = $service->save($this->superAdmin(), $this->input());
        $published = $service->publish($this->superAdmin(), $plan->id, $plan->assessments->first()->id, 1);
        $this->assertSame('published', $published->state);
        $this->assertSame('published', $published->assessments->first()->state);
        $this->assertSame(2, $published->revision);
        $this->expectException(HttpException::class);
        $service->save($this->superAdmin(), $this->input() + ['revision' => 2], $plan->id);
    }

    public function test_draft_can_be_incomplete_but_publication_checks_weights_and_question_totals(): void
    {
        $input = $this->input();
        $input['assessments'][0]['components'] = ['sba' => 5000];
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), $input);
        try {
            app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->first()->id, 1);
            $this->fail('Incomplete component weights cannot publish.');
        } catch (ValidationException) {
            $this->assertSame('draft', $plan->fresh()->state);
        }
        $input = $this->input();
        $input['assessments'][0]['tasks'][0]['questions'][1]['max_mark'] = '13.00';
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), $input);
        $this->expectException(ValidationException::class);
        app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->first()->id, 1);
    }

    public function test_stale_draft_writes_fail_without_overwriting_the_newer_plan(): void
    {
        $service = app(CurriculumPlans::class);
        $plan = $service->save($this->superAdmin(), $this->input());
        $input = $service->data($plan);
        $input['title'] = 'Changed title';
        $saved = $service->save($this->superAdmin(), $input, $plan->id);
        $this->assertSame(2, $saved->revision);
        try {
            $input['title'] = 'Stale overwrite';
            $service->save($this->superAdmin(), $input, $plan->id);
            $this->fail('A stale write must fail.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('Changed title', $plan->fresh()->title);
        }
        $same = $service->save($this->superAdmin(), $service->data($saved), $plan->id);
        $this->assertSame(2, $same->revision);
    }

    public function test_same_year_copy_preserves_task_provenance_and_never_copies_marks(): void
    {
        $service = app(CurriculumPlans::class);
        $source = $service->save($this->superAdmin(), $this->input());
        $oldTask = $source->assessments->first()->tasks->first();
        $copy = $service->copy($this->superAdmin(), $source->id, $this->year);
        $this->assertSame(2, $copy->version);
        $this->assertSame($source->id, (int) $copy->source_plan_id);
        $this->assertSame($oldTask->id, (int) $copy->assessments->first()->tasks->first()->source_task_id);
        $this->assertSame($copy->id, $service->copy($this->superAdmin(), $source->id, $this->year)->id);
        $payload = $service->data($copy);
        $payload['title'] = 'Revised draft';
        $copy = $service->save($this->superAdmin(), $payload, $copy->id);
        $this->assertSame($oldTask->id, (int) $copy->assessments->first()->tasks->first()->source_task_id);
    }

    public function test_year_copy_maps_terms_and_dates_and_leaves_existing_data_intact(): void
    {
        $service = app(CurriculumPlans::class);
        $source = $service->save($this->superAdmin(), $this->input());
        $year = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'fee_due_date' => '2027-02-01']);
        $term = Term::create(['name' => Term::findOrFail($this->term)->name, 'session_year_id' => $year->id,
            'start_date' => '2027-01-14', 'end_date' => '2027-03-20']);
        $copy = $service->copy($this->superAdmin(), $source->id, $year->id);
        $this->assertSame($term->id, (int) $copy->assessments->first()->term_id);
        $this->assertSame('2027-02-10', $copy->assessments->first()->tasks->first()->due_on->toDateString());
        $this->assertNull($copy->assessments->first()->tasks->first()->source_task_id);
        $this->assertSame($this->year, (int) $source->fresh()->session_year_id);
    }

    public function test_foreign_subject_year_topic_term_and_unallocated_users_are_refused(): void
    {
        foreach (['subject_id' => $this->schoolB->ids['maths'], 'session_year_id' => $this->schoolB->ids['session_year']] as $key => $value) {
            try {
                app(CurriculumPlans::class)->save($this->superAdmin(), array_replace($this->input(), [$key => $value]));
                $this->fail('A foreign record must be refused.');
            } catch (ModelNotFoundException) {
                $this->assertSame(0, CurriculumPlan::count());
            }
        }
        $this->expectException(HttpException::class);
        app(CurriculumPlans::class)->save(User::findOrFail($this->schoolA->ids['theo_user']), $this->input());
    }

    public function test_foreign_term_topic_mismatch_and_binary_float_marks_never_partially_save(): void
    {
        $bad = $this->input();
        $bad['topics'][0]['term_id'] = $this->schoolB->ids['term'];
        try {
            app(CurriculumPlans::class)->save($this->superAdmin(), $bad);
            $this->fail('A foreign term must be refused.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, CurriculumPlan::count());
        }
        $bad = $this->input();
        $bad['assessments'][0]['tasks'][0]['max_mark'] = 20.01;
        $this->expectException(ValidationException::class);
        app(CurriculumPlans::class)->save($this->superAdmin(), $bad);
    }

    public function test_marks_lock_date_rejects_publication_with_conflict(): void
    {
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), $this->input());
        Term::whereKey($this->term)->update(['marks_lock_on' => '2026-01-01']);
        try {
            app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->first()->id, 1);
            $this->fail('A locked term must refuse new definitions.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
    }

    public function test_exact_mark_units_cover_zero_decimals_and_refuse_scientific_notation(): void
    {
        $this->assertSame(0, AcademicNumbers::units('0'));
        $this->assertSame(750, AcademicNumbers::units('7.5'));
        $this->assertSame('7.50', AcademicNumbers::decimal(750));
        $this->expectException(ValidationException::class);
        AcademicNumbers::units('1e2');
    }
}
