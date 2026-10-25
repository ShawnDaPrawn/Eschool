<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Growth\LearnerNote;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Growth\LearnerNoteOperation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Teacher;
use App\Models\Term;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\LearnerNoteOperations;
use App\Services\Growth\LearnerNotes;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ProgressNotesApiTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    private string $prefix = '/api/v2/growth/progress';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        config(['cache.default' => 'array']);
    }

    private function createInput(string $visibility = 'staff'): array
    {
        return ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'subject_id' => $this->subject,
            'visibility' => $visibility, 'body' => 'Private progress note from the authorized author'];
    }

    private function createUrl(?int $learner = null): string
    {
        return $this->prefix.'/learners/'.($learner ?? $this->schoolA->ids['asha']).'/notes';
    }

    private function clinical(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
    }

    private function approve(): void
    {
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
    }

    public function test_private_mutations_refuse_query_inputs_before_merging_or_replay(): void
    {
        $this->actingAs($this->actor())->postJson($this->createUrl().'?body=URL_PRIVATE_NOTE_VALUE', $this->createInput())
            ->assertStatus(422)->assertDontSee('URL_PRIVATE_NOTE_VALUE');
        $this->assertSame(0, LearnerNote::count());
        $this->assertSame(0, LearnerNoteOperation::count());
    }

    public function test_note_creation_replay_read_update_history_and_metadata_listing_are_private(): void
    {
        $data = $this->createInput();
        $created = $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $id = $created->json('data.id');
        $this->postJson($this->createUrl(), $data)->assertOk()->assertExactJson($created->json());
        $this->assertSame(1, LearnerNote::count());
        $this->assertSame(1, LearnerNoteOperation::count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
        $this->assertStringNotContainsString($data['body'], DB::table('learner_note_operations')->value('receipt'));
        $this->getJson($this->prefix.'/notes/'.$id)->assertOk()->assertJsonPath('data.body', $data['body']);
        $query = http_build_query(['session_year_id' => $this->year, 'subject_id' => $this->subject, 'visibility' => 'staff']);
        $this->getJson($this->createUrl().'?'.$query)->assertOk()->assertJsonMissingPath('data.notes.0.body')->assertJsonPath('data.notes.0.id', $id);
        $update = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'body' => 'New private revision'];
        $saved = $this->putJson($this->prefix.'/notes/'.$id, $update)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson($this->prefix.'/notes/'.$id, $update)->assertOk()->assertExactJson($saved->json());
        $this->getJson($this->prefix.'/notes/'.$id.'/history')->assertOk()->assertJsonPath('data.revisions.0.body', 'New private revision')->assertJsonCount(2, 'data.revisions');
        $this->putJson($this->prefix.'/notes/'.$id, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'body' => 'Stale text'])->assertConflict()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_intent_mismatch_and_maximum_multibyte_receipt_are_not_truncated(): void
    {
        $data = $this->createInput();
        $data['body'] = str_repeat('é', 20000);
        $created = $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk();
        $this->postJson($this->createUrl(), $data)->assertOk()->assertExactJson($created->json());
        $this->assertGreaterThan(65535, strlen(DB::table('learner_note_operations')->value('receipt')));
        $data['body'] = 'Different private intent';
        $this->postJson($this->createUrl(), $data)->assertConflict()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(1, LearnerNote::count());
    }

    public function test_wrong_role_owner_school_and_cookie_account_are_refused_before_private_reads_or_replay(): void
    {
        $data = $this->createInput();
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk()->json('data.id');
        foreach (['farid', 'asha', 'theo'] as $actor) {
            $response = $this->actingAs($this->actor($actor))->getJson($this->prefix.'/notes/'.$id);
            $this->assertContains($response->status(), [403, 404]);
            $response->assertJsonMissing(['body' => $data['body']])->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->actingAs($this->actor())->postJson($this->createUrl($this->schoolB->ids['asha']), $this->createInput())->assertNotFound();
        $this->postJson($this->createUrl($this->schoolA->ids['ben']), $this->createInput())->assertNotFound();
        $this->getJson($this->prefix.'/notes/'.$id, ['X-Account-Id' => (string) $this->actor('farid')->id])->assertForbidden();
        $this->getJson($this->prefix.'/notes/'.$id, ['X-Account-Id' => (string) $this->actor()->id])->assertOk();
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->actor()->id)->update(['status' => 'ended']);
        $this->postJson($this->createUrl(), $data)->assertForbidden();
    }

    public function test_clinical_assignment_is_separate_and_principal_metadata_never_exposes_a_body(): void
    {
        $this->clinical();
        $data = $this->createInput('counselling');
        $id = $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk()->json('data.id');
        $this->getJson($this->prefix.'/caseload')->assertOk()->assertJsonPath('data.learners.0.learner_id', $this->schoolA->ids['asha']);
        $this->actingAs($this->superAdmin())->getJson($this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/assignments')->assertOk()->assertJsonMissing(['body' => $data['body']]);
        $this->getJson($this->prefix.'/notes/'.$id)->assertForbidden();
        $this->actingAs($this->actor())->putJson($this->prefix.'/notes/'.$id, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'body' => 'Attempt exposure', 'visibility' => 'tutor'])->assertUnprocessable();
        $this->assertSame('counselling', LearnerNote::findOrFail($id)->visibility);
        LearnerNoteAssignment::query()->update(['ends_on' => '2026-02-09']);
        $this->postJson($this->createUrl(), $data)->assertNotFound();
        $this->getJson($this->prefix.'/notes/'.$id.'/history')->assertNotFound();
        $this->getJson($this->prefix.'/caseload')->assertOk()->assertJsonPath('data.total', 0);
    }

    public function test_platform_operator_exclusion_applies_to_clinical_receipt_replay(): void
    {
        $this->clinical();
        $data = $this->createInput('counselling');
        $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk();
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->postJson($this->createUrl(), $data)->assertForbidden();
        $this->getJson($this->prefix.'/caseload')->assertForbidden();
    }

    public function test_configuration_roster_and_running_bands_require_current_scoped_staff(): void
    {
        $this->capture('8.00', '8.00');
        $this->actingAs($this->actor())->getJson($this->prefix.'/configuration')->assertOk()->assertJsonPath('data.groups.0.id', $this->group)->assertJsonPath('data.can_manage_bands', false);
        $this->getJson($this->prefix.'/groups/'.$this->group.'/learners')->assertOk()->assertJsonPath('data.learners.0.learner_id', $this->schoolA->ids['asha']);
        $this->getJson($this->prefix.'/groups/'.$this->group.'/learners/'.$this->schoolA->ids['asha'].'?term_id='.$this->term)->assertOk()->assertJsonPath('data.band', 'high');
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/groups/'.$this->group.'/learners')->assertNotFound();
        $this->actingAs($this->actor('farid'))->getJson($this->prefix.'/configuration')->assertForbidden();
    }

    public function test_band_settings_and_principal_caseload_writes_use_encrypted_reauthorized_receipts(): void
    {
        $data = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'high_bp' => 8500, 'secure_bp' => 7000, 'minimum_coverage_bp' => 5000];
        $saved = $this->actingAs($this->superAdmin())->putJson($this->prefix.'/settings', $data)->assertOk()->assertJsonPath('data.revision', 1);
        $this->putJson($this->prefix.'/settings', $data)->assertOk()->assertExactJson($saved->json());
        $this->actingAs($this->actor())->putJson($this->prefix.'/settings', [])->assertForbidden();
        $this->clinical();
        $this->actingAs($this->superAdmin())->getJson($this->prefix.'/assignment-learners')->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->actingAs($this->actor())->getJson($this->prefix.'/assignment-learners')->assertForbidden();
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $url = $this->prefix.'/learners/'.$this->schoolA->ids['asha'].'/assignments/'.$this->actor('theo')->id;
        $assignment = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31'];
        $saved = $this->actingAs($this->superAdmin())->putJson($url, $assignment)->assertOk()->assertJsonPath('data.revision', 1);
        $this->putJson($url, $assignment)->assertOk()->assertExactJson($saved->json());
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/caseload')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($this->actor())->putJson($url, [])->assertForbidden();
    }

    public function test_scope_authorization_precedes_validation_and_teacher_replay_is_revoked(): void
    {
        $this->actingAs($this->actor('theo'))->getJson($this->prefix.'/groups/'.$this->group.'/learners?page=invalid')->assertNotFound();
        $this->actingAs($this->actor('farid'))->postJson($this->createUrl(), [])->assertForbidden();
        $this->getJson($this->prefix.'/caseload?page=invalid')->assertForbidden();
        $data = $this->createInput();
        $this->actingAs($this->actor())->postJson($this->createUrl(), $data)->assertOk();
        $this->actor()->syncRoles([SchoolRoles::FACILITATOR]);
        $this->postJson($this->createUrl(), $data)->assertNotFound();
        $this->assertSame(1, LearnerNoteOperation::count());
    }

    public function test_operation_captures_school_before_authority_callbacks_and_refuses_context_replacement(): void
    {
        $actor = $this->actor();
        try {
            app(LearnerNoteOperations::class)->once($actor, 'test.scope', ['operation_id' => (string) Str::uuid()],
                fn () => app(CurrentSchool::class)->set(2), fn () => ['body' => 'Must not settle in another school']);
            $this->fail('A replacement school context must be refused.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, LearnerNoteOperation::count());
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => LearnerNoteOperation::count()));
    }

    public function test_family_bands_and_periods_require_fresh_approved_guardian_ownership(): void
    {
        $url = $this->prefix.'/learners/'.$this->schoolA->ids['asha'];
        $this->actingAs($this->actor('farid'))->getJson($url.'/periods')->assertForbidden();
        $this->approve();
        $this->capture('8.00', '8.00');
        $this->getJson($url.'/bands?session_year_id='.$this->year.'&term_id='.$this->term)->assertOk()->assertJsonPath('data.results', []);
        $this->release();
        $this->getJson($url.'/periods')->assertOk()->assertJsonPath('data.periods.0.session_year_id', $this->year);
        $this->getJson($url.'/bands?session_year_id='.$this->year.'&term_id='.$this->term)->assertOk()->assertJsonPath('data.results.0.band', 'high');
        $this->actingAs($this->actor('gina'))->getJson($url.'/periods')->assertNotFound();
        $this->actingAs($this->actor('farid'))->getJson($this->prefix.'/learners/'.$this->schoolB->ids['asha'].'/periods')->assertNotFound();
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->getJson($url.'/bands?session_year_id='.$this->year.'&term_id='.$this->term)->assertForbidden();
    }

    public function test_progress_configuration_supplies_grade_valid_terms_for_each_allocated_group(): void
    {
        Term::whereKey($this->term)->update(['start_date' => '2026-01-02', 'grade_from' => 7, 'grade_to' => 7]);
        $other = Term::create(['session_year_id' => $this->year, 'name' => 'Other grade first term', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'grade_from' => 8, 'grade_to' => 8]);
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Other grade allocation', 'subject_id' => $this->subject,
            'grade_level_id' => GradeLevel::where('code', '8')->value('id'), 'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->actor()->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic second grade allocation'], (int) $group->id);
        $data = $this->actingAs($this->actor())->getJson($this->prefix.'/configuration')->assertOk()->json('data');
        $this->assertSame((int) $other->id, $data['terms'][0]['id']);
        $first = collect($data['groups'])->firstWhere('id', $this->group);
        $second = collect($data['groups'])->firstWhere('id', (int) $group->id);
        $this->assertContains($this->term, $first['term_ids']);
        $this->assertNotContains((int) $other->id, $first['term_ids']);
        $this->assertContains((int) $other->id, $second['term_ids']);
        $this->assertNotContains($this->term, $second['term_ids']);
    }
}
