<?php

declare(strict_types=1);

namespace Tests\Feature\Pathways;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\MarkSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Pathways\AdmissionRequirement;
use App\Models\Pathways\AdmissionRequirementVersion;
use App\Models\Pathways\PathwayOperation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Pathways\PathwayQueries;
use App\Services\Pathways\PathwayReferences;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PathwaysTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        SchoolModule::updateOrCreate(['module' => 'pathway'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor('asha')->forceFill(['password_change_required' => false])->save();
        $this->actor('farid')->forceFill(['password_change_required' => false, 'email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->actor('farid'), Students::findOrFail($this->schoolA->ids['asha']), 'approve', config('identity.consent_version'));
        Http::preventStrayRequests();
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('synthetic-pathway-http')->plainTextToken)
            ->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $user->id]);
    }

    private function input(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'kind' => 'programme', 'status' => 'published',
            'title' => 'Synthetic source-defined programme', 'institution' => 'Synthetic institution', 'source_title' => 'Synthetic 2026 prospectus',
            'source_url' => 'https://institution.example.test/prospectus', 'source_year' => 2026, 'reviewed_on' => '2026-02-10', 'expires_on' => '2026-12-31',
            'guidance' => 'Synthetic source fixture. Meeting the published minimum is not an admission offer.',
            'rules' => ['subjects' => [['subject_id' => $this->subject, 'minimum_bp' => 5000, 'counts_for_score' => true]],
                'bands' => [['minimum_bp' => 8000, 'points' => 7], ['minimum_bp' => 7000, 'points' => 6], ['minimum_bp' => 5000, 'points' => 4], ['minimum_bp' => 0, 'points' => 0]],
                'best_n' => 1, 'minimum_score' => 4]];
    }

    private function source(array $changes = []): array
    {
        return app(PathwayReferences::class)->save($this->superAdmin(), null, $this->input($changes));
    }

    private function projection(array $changes = []): array
    {
        return $changes + ['learner_id' => $this->schoolA->ids['asha'], 'revision' => 1, 'source' => 'scenario',
            'marks' => [['subject_id' => $this->subject, 'percentage_bp' => 7000]]];
    }

    private function refused(callable $call, array $codes = [403, 404, 409, 422]): void
    {
        try {
            $call();
            $this->fail('An unauthorized, stale or invalid pathway request succeeded.');
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            $this->assertContains($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 404, $codes);
        }
    }

    public function test_source_publication_retries_and_revision_history_are_immutable(): void
    {
        $service = app(PathwayReferences::class);
        $input = $this->input();
        $first = $service->save($this->superAdmin(), null, $input);
        $this->assertSame($first, $service->save($this->superAdmin(), null, $input));
        $second = $service->save($this->superAdmin(), $first['id'], $this->input(['revision' => 1, 'title' => 'Reviewed replacement source']));
        $this->assertSame(2, $second['revision']);
        $this->assertSame($first, $service->save($this->superAdmin(), null, $input));
        $this->assertSame(2, AdmissionRequirementVersion::count());
        $this->assertSame(2, PathwayOperation::count());
        $this->refused(fn () => $service->save($this->superAdmin(), null, array_replace($input, ['title' => 'Changed retry'])), [409]);
        $this->refused(fn () => $service->save($this->superAdmin(), $first['id'], $this->input(['revision' => 1])), [409]);
        $this->expectException(\LogicException::class);
        AdmissionRequirementVersion::firstOrFail()->update(['guidance' => 'Rewrite retained history']);
    }

    public function test_family_scenario_is_private_transient_and_cannot_write_official_marks(): void
    {
        $source = $this->source();
        $before = [AssessmentMark::count(), MarkSnapshot::count(), AiUsage::count(), PathwayOperation::count()];
        $result = app(PathwayQueries::class)->project($this->actor('farid'), $source['id'], $this->projection());
        $this->assertSame('scenario', $result['source']);
        $this->assertSame(6, $result['score']['projected_score']);
        $this->assertSame('meets_published_minimum', $result['score']['status']);
        $this->assertSame($before, [AssessmentMark::count(), MarkSnapshot::count(), AiUsage::count(), PathwayOperation::count()]);
        $own = app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $this->projection(['marks' => []]));
        $this->assertNull($own['score']['projected_score']);
        $this->assertSame('unknown', $own['score']['status']);
        $this->assertSame([$this->subject], $own['score']['missing_subject_ids']);
    }

    public function test_only_released_evidence_enters_a_projection_and_new_unreleased_marks_do_not_replace_it(): void
    {
        $source = $this->source();
        $this->capture('6.00', '8.00');
        $input = ['learner_id' => $this->schoolA->ids['asha'], 'revision' => 1, 'source' => 'released', 'session_year_id' => $this->year, 'term_id' => $this->term];
        $service = app(PathwayQueries::class);
        $unknown = $service->project($this->actor('farid'), $source['id'], $input);
        $this->assertNull($unknown['score']['projected_score']);
        $this->assertSame([], $unknown['released_evidence']);
        $this->release();
        $released = $service->project($this->actor('farid'), $source['id'], $input);
        $this->assertSame(6, $released['score']['projected_score']);
        $this->assertSame(7000, $released['score']['subjects'][0]['percentage_bp']);
        $this->assertSame(false, array_values($released['released_evidence'])[0]['provisional']);
        $this->capture('10.00', '10.00', 1);
        $this->assertSame(6, $service->project($this->actor('farid'), $source['id'], $input)['score']['projected_score']);
    }

    public function test_wrong_roles_school_learner_and_withdrawn_guardian_are_refused(): void
    {
        $source = $this->source();
        foreach (['tara', 'farid', 'asha'] as $name) {
            $this->refused(fn () => app(PathwayReferences::class)->save($this->actor($name), null, $this->input()), [403]);
        }
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor(), $source['id'], $this->projection()), [403, 404]);
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor('farid'), $source['id'], $this->projection(['learner_id' => $this->schoolB->ids['asha']])), [403, 404]);
        $this->refused(fn () => app(CurrentSchool::class)->run(2, fn () => app(PathwayQueries::class)->read($this->actor('farid'), $source['id'])), [403, 404]);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->update(['status' => 'revoked']);
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor('farid'), $source['id'], $this->projection()), [403, 404]);
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $this->projection()), [403]);
    }

    public function test_module_readonly_allows_transient_calculation_but_blocks_reference_writes(): void
    {
        $source = $this->source();
        SchoolModule::where('module', 'pathway')->update(['read_only' => true]);
        $this->assertSame(6, app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $this->projection())['score']['projected_score']);
        $this->refused(fn () => $this->source(), [404]);
        SchoolModule::where('module', 'pathway')->update(['enabled' => false]);
        $this->refused(fn () => app(PathwayQueries::class)->configuration($this->actor('asha')), [404]);
    }

    public function test_source_expiry_replacement_and_withdrawal_block_stale_projections(): void
    {
        $source = $this->source();
        app(PathwayReferences::class)->save($this->superAdmin(), $source['id'], $this->input(['revision' => 1]));
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $this->projection()), [409]);
        app(PathwayReferences::class)->save($this->superAdmin(), $source['id'], $this->input(['revision' => 2, 'status' => 'withdrawn']));
        $this->assertSame([], app(PathwayQueries::class)->catalogue($this->actor('asha'))['items']);
        $this->refused(fn () => app(PathwayQueries::class)->read($this->actor('asha'), $source['id']), [409]);
        $fresh = $this->source();
        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->refused(fn () => app(PathwayReferences::class)->current($fresh['id']), [409]);
    }

    public function test_source_requires_https_current_school_subjects_and_current_review(): void
    {
        foreach (['http://institution.example.test', 'https://user:password@institution.example.test', 'https://127.0.0.1/source'] as $url) {
            try {
                $this->source(['source_url' => $url]);
                $this->fail('An unsuitable source URL was published.');
            } catch (ValidationException|HttpExceptionInterface) {
                $this->assertSame(0, AdmissionRequirement::count());
            }
        }
        $this->refused(fn () => $this->source(['reviewed_on' => '2024-01-01']), [422]);
        $bad = $this->input()['rules'];
        $bad['subjects'][0]['subject_id'] = $this->schoolB->ids['maths'];
        $this->refused(fn () => $this->source(['rules' => $bad]), [422]);
        $this->assertSame(0, AdmissionRequirement::count());
    }

    public function test_guide_checks_compulsory_and_elective_counts_without_a_score_claim(): void
    {
        $science = $this->schoolA->ids['science'];
        $source = $this->source(['kind' => 'subject_choice_guide', 'institution' => null, 'rules' => ['compulsory_subject_ids' => [$this->subject],
            'elective_groups' => [['title' => 'Synthetic science choice', 'choose' => 1, 'subject_ids' => [$science]]]]]);
        $input = ['learner_id' => $this->schoolA->ids['asha'], 'revision' => 1, 'source' => 'selection', 'subject_ids' => [$this->subject]];
        $first = app(PathwayQueries::class)->project($this->actor('farid'), $source['id'], $input);
        $this->assertNull($first['score']);
        $this->assertFalse($first['selection']['valid']);
        $input['subject_ids'][] = $science;
        $this->assertTrue(app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $input)['selection']['valid']);
        $input['subject_ids'][] = $this->schoolB->ids['science'];
        $this->refused(fn () => app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $input), [422]);
    }

    public function test_retired_subject_can_be_reviewed_or_withdrawn_but_cannot_still_score(): void
    {
        $source = $this->source();
        Subject::findOrFail($this->subject)->delete();
        $this->refused(fn () => app(PathwayQueries::class)->read($this->actor('asha'), $source['id']), [409]);
        $this->assertSame($source['id'], app(PathwayQueries::class)->read($this->superAdmin(), $source['id'], true)['id']);
        $withdrawn = app(PathwayReferences::class)->save($this->superAdmin(), $source['id'], $this->input(['revision' => 1, 'status' => 'withdrawn']));
        $this->assertSame('withdrawn', $withdrawn['status']);
    }

    public function test_platform_and_impersonation_never_gain_reference_management(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->impersonate(app(CurrentSchool::class)->school());
        $this->refused(fn () => app(PathwayQueries::class)->configuration($admin), [403]);
        app(CurrentSchool::class)->set(1);
        PlatformStaffIdentity::create(['user_id' => $admin->id, 'active' => true]);
        $this->refused(fn () => app(PathwayReferences::class)->save($admin, null, $this->input()), [403]);
    }

    public function test_database_rejects_a_current_version_from_another_source(): void
    {
        $first = $this->source();
        $second = $this->source();
        $version = AdmissionRequirement::findOrFail($second['id'])->current_version_id;
        $this->expectException(QueryException::class);
        DB::table('admission_requirements')->where('id', $first['id'])->update(['current_version_id' => $version]);
    }

    public function test_typed_api_publishes_reads_and_calculates_with_private_scope(): void
    {
        $created = $this->api($this->superAdmin())->postJson('/api/v2/pathways/references', $this->input())->assertOk();
        $source = $created->json('data.reference');
        $this->assertStringContainsString('no-store', $created->headers->get('Cache-Control'));
        $this->api($this->actor('farid'))->getJson('/api/v2/pathways/configuration')->assertOk()->assertJsonPath('data.can_manage', false);
        $this->getJson('/api/v2/pathways/references')->assertOk()->assertJsonPath('data.items.0.id', $source['id']);
        $this->getJson('/api/v2/pathways/references/'.$source['id'])->assertOk()->assertJsonPath('data.reference.revision', 1);
        $this->postJson('/api/v2/pathways/references/'.$source['id'].'/projection', $this->projection())->assertOk()->assertJsonPath('data.score.projected_score', 6);
        $this->api($this->superAdmin())->getJson('/api/v2/pathways/references?manage=true')->assertOk();
        $this->putJson('/api/v2/pathways/references/'.$source['id'], $this->input(['revision' => 1]))->assertOk()->assertJsonPath('data.reference.revision', 2);
    }

    public function test_api_rejects_crossed_account_school_role_owner_and_query_write_fields(): void
    {
        $source = $this->source();
        $url = '/api/v2/pathways/references/'.$source['id'].'/projection';
        $this->api($this->actor('tara'))->postJson($url, $this->projection())->assertForbidden();
        $this->api($this->actor('farid'))->withHeader('X-School-Id', '2')->postJson($url, $this->projection())->assertForbidden();
        $this->api($this->actor('farid'))->withHeader('X-Account-Id', (string) $this->actor('tara')->id)->postJson($url, $this->projection())->assertForbidden();
        $this->api($this->actor('farid'))->postJson($url.'?learner_id='.$this->schoolA->ids['asha'], $this->projection())->assertUnprocessable();
        $this->postJson($url, $this->projection() + ['school_id' => 2])->assertUnprocessable();
        $this->postJson($url, $this->projection(['learner_id' => $this->schoolB->ids['asha']]))->assertNotFound();
        $this->postJson('/api/v2/pathways/references', $this->input())->assertForbidden();
    }

    public function test_api_readonly_computation_has_no_side_effect_or_implicit_zero(): void
    {
        $source = $this->source();
        SchoolModule::where('module', 'pathway')->update(['read_only' => true]);
        $before = [AssessmentMark::count(), MarkSnapshot::count(), PathwayOperation::count()];
        $this->api($this->actor('asha'))->postJson('/api/v2/pathways/references/'.$source['id'].'/projection', $this->projection(['marks' => [['subject_id' => $this->subject, 'percentage_bp' => null]]]))
            ->assertOk()->assertJsonPath('data.score.projected_score', null)->assertJsonPath('data.score.status', 'unknown');
        $this->assertSame($before, [AssessmentMark::count(), MarkSnapshot::count(), PathwayOperation::count()]);
        $this->api($this->superAdmin())->postJson('/api/v2/pathways/references', $this->input())->assertForbidden();
    }

    private function panelBody(array $changes = []): array
    {
        $input = $this->input($changes);
        unset($input['rules']);

        return $input + ['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1,
            'score_subjects' => [['subject_id' => $this->subject, 'minimum_percent' => '50.00', 'counts_for_score' => '1']],
            'score_bands' => [['minimum_percent' => '0', 'points' => '0'], ['minimum_percent' => '50', 'points' => '4'], ['minimum_percent' => '70', 'points' => '6']],
            'best_n' => '1', 'minimum_score' => '4'];
    }

    public function test_panel_structured_source_form_and_revision_flow_are_account_bound(): void
    {
        $this->actingAs($this->superAdmin())->get('/pathways')->assertOk()->assertSee('Add a programme source');
        $this->get('/pathways/new/programme')->assertOk()->assertSee('pathway-subject-0-minimum', false)->assertSee('expected_school_id', false);
        $body = $this->panelBody();
        $this->post('/pathways/references', $body)->assertRedirect('/pathways');
        $source = AdmissionRequirement::sole();
        $this->assertSame(5000, AdmissionRequirementVersion::sole()->rules['subjects'][0]['minimum_bp']);
        $this->get('/pathways/references/'.$source->id.'/edit')->assertOk()->assertSee('Synthetic source-defined programme');
        $this->post('/pathways/references/'.$source->id, $this->panelBody(['revision' => 1, 'title' => 'Revised source title']))->assertRedirect('/pathways');
        $this->assertSame(2, $source->fresh()->revision);
        $this->post('/pathways/references', array_replace($body, ['operation_id' => (string) Str::uuid(), 'expected_school_id' => 2]))->assertForbidden();
        $this->post('/pathways/references', array_replace($body, ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->actor('farid')->id]))->assertForbidden();
        $this->actingAs($this->actor('farid'))->get('/pathways')->assertForbidden();
    }

    public function test_associative_scenario_collections_are_refused_instead_of_coercing_a_mark(): void
    {
        $source = $this->source();
        $this->api($this->actor('asha'))->postJson('/api/v2/pathways/references/'.$source['id'].'/projection', $this->projection(['marks' => ['hidden' => ['subject_id' => $this->subject, 'percentage_bp' => 0]]]))->assertUnprocessable();
        $blank = app(PathwayQueries::class)->project($this->actor('asha'), $source['id'], $this->projection(['marks' => [['subject_id' => $this->subject, 'percentage_bp' => '']]]));
        $this->assertNull($blank['score']['projected_score']);
        $this->assertNull($blank['score']['subjects'][0]['percentage_bp']);
    }
}
