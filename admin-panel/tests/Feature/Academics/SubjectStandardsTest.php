<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\SubjectStandard;
use App\Models\Mediums;
use App\Models\Platform\SchoolMembership;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\SubjectStandards;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class SubjectStandardsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function row(array $overrides = []): array
    {
        return $overrides + ['framework' => 'CAPS', 'code' => 'TEST-MATH-7', 'name' => 'Reference mathematics',
            'phase' => 'Senior', 'grade_from' => 7, 'grade_to' => 9, 'version' => 'synthetic-v1',
            'source_url' => 'https://example.test/reviewed-standard', 'effective_on' => '2026-01-01',
            'component_weights' => ['sba' => 6000, 'exam' => 4000]];
    }

    private function standard(array $overrides = [], bool $publish = true): SubjectStandard
    {
        app(SubjectStandards::class)->import([$this->row($overrides)], $publish);

        return SubjectStandard::query()->latest('id')->firstOrFail();
    }

    private function admin(): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->superAdmin()->createToken('standards')->plainTextToken);
    }

    private function path(?int $id = null): string
    {
        return '/api/v2/academics/subjects/'.($id ?? Subject::query()->firstOrFail()->id).'/standard';
    }

    public function test_every_profile_reads_only_published_catalogue_with_grade_and_framework_filters(): void
    {
        $this->standard();
        $this->standard(['code' => 'TEST-IEB', 'framework' => 'IEB']);
        $this->standard(['code' => 'DRAFT'], false);
        foreach (['asha_user', 'tara_user', 'farid_user'] as $key) {
            $this->app['auth']->forgetGuards();
            $this->flushHeaders()->withToken(User::findOrFail($this->schoolA->ids[$key])->createToken('read')->plainTextToken);
            $this->getJson('/api/v2/academics/subject-standards?grade=7&framework=CAPS')->assertOk()->assertJsonPath('data.total', 1)
                ->assertJsonPath('data.standards.0.code', 'TEST-MATH-7');
            $this->getJson('/api/v2/academics/subject-standards?grade=6')->assertOk()->assertJsonCount(0, 'data.standards');
        }
    }

    public function test_operator_import_is_atomic_versioned_repeatable_and_has_explicit_publication(): void
    {
        $service = app(SubjectStandards::class);
        $this->assertSame(['created' => 1, 'published' => 0, 'processed' => 1], $service->import([$this->row()]));
        $this->assertSame(['created' => 0, 'published' => 1, 'processed' => 1], $service->import([$this->row()], true));
        $this->assertSame(['created' => 0, 'published' => 0, 'processed' => 1], $service->import([$this->row()], true));
        try {
            $service->import([$this->row(['code' => 'NEW']), $this->row(['name' => 'Changed revision'])], true);
            $this->fail('A conflicting revision must be refused.');
        } catch (ValidationException) {
            $this->assertSame(1, SubjectStandard::count());
        }
        $service->import([$this->row(['version' => 'synthetic-v2', 'name' => 'New revision'])], true);
        $this->assertSame(2, SubjectStandard::count());
    }

    public function test_invalid_components_grade_bands_and_source_urls_do_not_partially_import(): void
    {
        foreach ([['component_weights' => ['sba' => 5000]], ['component_weights' => [10000]],
            ['grade_to' => 12], ['grade_to' => 6], ['source_url' => 'javascript:alert(1)'], ['effective_on' => '2026-02-30']] as $bad) {
            try {
                app(SubjectStandards::class)->import([$this->row(['code' => 'VALID']), $this->row($bad)], true);
                $this->fail('The full import must validate before writes.');
            } catch (ValidationException) {
                $this->assertSame(0, SubjectStandard::count());
            }
        }
    }

    public function test_admin_maps_and_clears_without_changing_legacy_subject_fields_or_marks(): void
    {
        $standard = $this->standard();
        $subject = Subject::query()->firstOrFail();
        $legacy = $subject->toArray();
        $marks = DB::table('exam_marks')->get()->toJson();
        $this->admin()->putJson($this->path($subject->id), ['standard_id' => $standard->id, 'school_id' => 2])->assertOk()
            ->assertJsonPath('data.standard.code', 'TEST-MATH-7');
        $this->assertSame($legacy, $subject->fresh()->toArray());
        $this->assertSame($marks, DB::table('exam_marks')->get()->toJson());
        $this->putJson($this->path($subject->id), ['standard_id' => null])->assertOk()->assertJsonPath('data.standard', null);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'academics.subject_standard_mapped')->count());
    }

    public function test_school_admin_cannot_map_foreign_deleted_unknown_or_draft_records(): void
    {
        $standard = $this->standard();
        $foreign = DB::table('subjects')->where('school_id', 2)->value('id');
        $this->admin()->getJson($this->path($foreign))->assertNotFound();
        $this->putJson($this->path($foreign), ['standard_id' => $standard->id])->assertNotFound();
        $draft = $this->standard(['code' => 'DRAFT'], false);
        $this->putJson($this->path(), ['standard_id' => $draft->id])->assertNotFound();
        $subject = Subject::query()->firstOrFail();
        $subject->delete();
        $this->putJson($this->path($subject->id), ['standard_id' => $standard->id])->assertNotFound();
        $this->putJson($this->path(999999), ['standard_id' => $standard->id])->assertNotFound();
    }

    public function test_teacher_parent_learner_and_unauthenticated_requests_cannot_manage_school_mappings(): void
    {
        $this->getJson('/api/v2/academics/subject-standards')->assertUnauthorized();
        foreach (['asha_user', 'tara_user', 'farid_user'] as $key) {
            $this->app['auth']->forgetGuards();
            $this->flushHeaders()->withToken(User::findOrFail($this->schoolA->ids[$key])->createToken('write')->plainTextToken);
            $this->getJson($this->path())->assertForbidden();
            $this->putJson($this->path(), ['standard_id' => 1])->assertForbidden();
            $this->postJson('/api/v2/academics/subjects/from-standard', [])->assertForbidden();
        }
    }

    public function test_create_from_standard_is_scoped_repeatable_and_does_not_guess_existing_mapping(): void
    {
        $standard = $this->standard();
        $medium = Mediums::query()->firstOrFail();
        $payload = ['standard_id' => $standard->id, 'medium_id' => $medium->id, 'type' => 'Theory', 'bg_color' => '#22577A'];
        $this->admin()->postJson('/api/v2/academics/subjects/from-standard', $payload + ['school_id' => 2])->assertOk();
        $this->postJson('/api/v2/academics/subjects/from-standard', $payload)->assertOk();
        $this->assertSame(1, Subject::query()->where('standard_id', $standard->id)->count());
        $foreign = DB::table('mediums')->where('school_id', 2)->value('id');
        $this->postJson('/api/v2/academics/subjects/from-standard', array_replace($payload, ['medium_id' => $foreign]))->assertNotFound();
    }

    public function test_creation_preserves_the_existing_editors_school_wide_code_uniqueness(): void
    {
        $standard = $this->standard();
        $medium = Mediums::query()->firstOrFail();
        $payload = ['standard_id' => $standard->id, 'medium_id' => $medium->id, 'type' => 'Theory', 'bg_color' => '#22577A'];
        $this->admin()->postJson('/api/v2/academics/subjects/from-standard', $payload)->assertOk();
        $this->postJson('/api/v2/academics/subjects/from-standard', array_replace($payload, ['type' => 'Practical']))->assertUnprocessable();
        $second = new Mediums;
        $second->name = 'Another medium';
        $second->save();
        $this->postJson('/api/v2/academics/subjects/from-standard', array_replace($payload, ['medium_id' => $second->id]))->assertUnprocessable();
        $this->assertSame(1, Subject::where('code', $standard->code)->count());
    }

    public function test_bundled_historical_catalogue_imports_and_preserves_grade_specific_codes(): void
    {
        $this->artisan('academics:import-subject-standards', ['file' => database_path('data/dbe-ncs-2015-subject-standards.json'), '--publish' => true])->assertSuccessful();
        $this->assertGreaterThan(400, SubjectStandard::count());
        $this->assertSame('19331054', SubjectStandard::where('name', 'Mathematics')->where('grade_from', 12)->sole()->code);
        $this->assertSame('19331462', SubjectStandard::where('name', 'Mathematics')->where('grade_from', 0)->sole()->code);
        $this->assertSame(0, SubjectStandard::whereNotNull('component_weights')->count());
    }

    public function test_panel_mapping_uses_the_same_scoped_service_and_teacher_is_refused(): void
    {
        $standard = $this->standard();
        $id = Subject::query()->firstOrFail()->id;
        $this->actingAs($this->superAdmin(), 'web')->get(route('subject-standards.index'))->assertOk()->assertSee('Reference mathematics');
        $this->put(route('subject-standards.update', $id), ['standard_id' => $standard->id])->assertRedirect();
        $this->assertSame($standard->id, (int) Subject::findOrFail($id)->standard_id);
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get(route('subject-standards.index'))->assertForbidden();
    }

    public function test_revoked_permission_cannot_replay_a_successful_mapping(): void
    {
        $standard = $this->standard();
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $this->admin()->withHeader('Idempotency-Key', 'subject-standard-test')->putJson($this->path(), ['standard_id' => $standard->id])->assertOk();
        $this->putJson($this->path(), ['standard_id' => $standard->id])->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->putJson($this->path(), ['standard_id' => $standard->id])->assertForbidden();
    }
}
