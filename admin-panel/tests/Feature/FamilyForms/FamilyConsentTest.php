<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyForms;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\FamilyForms\ConsentResponse;
use App\Models\FamilyForms\FamilyFormOperation;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\HealthForms;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FamilyConsentTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $teacher;

    private int $learner;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->learner = $this->schoolA->ids['asha'];
        $year = $this->schoolA->ids['session_year'];
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic consent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
    }

    private function create(array $changes = [], ?User $actor = null): array
    {
        return app(Consents::class)->create($actor ?? $this->teacher, $changes + ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $this->group->id,
            'title' => 'Synthetic private trip', 'wording' => 'Synthetic exact consent wording <script>unsafe()</script>', 'event_starts_at' => '2026-02-20T10:00:00+02:00', 'event_ends_at' => '2026-02-20T12:00:00+02:00', 'respond_by' => '2026-02-19T12:00:00+02:00', 'learner_ids' => [$this->learner]]);
    }

    private function response(array $request, array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'fingerprint' => $request['fingerprint'], 'decision' => 'yes', 'acknowledged' => true, 'signed_name' => $this->parent->full_name];
    }

    public function test_signed_consent_is_fingerprint_bound_encrypted_append_only_and_retry_safe(): void
    {
        $request = $this->create();
        $input = $this->response($request);
        $service = app(Consents::class);
        $first = $service->respond($this->parent, $request['id'], $this->learner, $input);
        $this->assertSame($first, $service->respond($this->parent, $request['id'], $this->learner, $input));
        $this->assertTrue($service->effective($this->teacher, $request['id'], $this->learner));
        $this->assertSame(1, ConsentResponse::count());
        $read = $service->read($this->parent, $request['id'], $this->learner);
        $this->assertSame('yes', $read['decision']);
        $this->assertSame('2026-02-20T08:00:00+00:00', $read['event_starts_at']);
        $this->assertStringNotContainsString('Synthetic exact consent', DB::table('consent_requests')->value('wording'));
        $this->assertStringNotContainsString($this->parent->full_name, DB::table('consent_responses')->value('signed_name'));
        $this->assertStringNotContainsString('Synthetic private trip', DB::table('family_form_operations')->value('receipt'));
        $service->respond($this->parent, $request['id'], $this->learner, $this->response($request, ['revision' => 1, 'decision' => 'no']));
        $this->assertSame(2, ConsentResponse::count());
        $this->assertFalse($service->effective($this->teacher, $request['id'], $this->learner));
    }

    public function test_changed_wording_fingerprint_or_operation_body_cannot_reuse_a_signature(): void
    {
        $request = $this->create();
        $input = $this->response($request);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input);
        foreach ([$this->response($request, ['fingerprint' => str_repeat('0', 64)]), array_merge($input, ['decision' => 'no'])] as $tampered) {
            try {
                app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $tampered);
                $this->fail('Changed consent reused an attestation.');
            } catch (HttpException $error) {
                $this->assertSame(409, $error->getStatusCode());
            }
        }
        ConsentResponse::query()->update(['signature' => str_repeat('0', 64)]);
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
    }

    public function test_wrong_guardian_teacher_learner_and_other_school_cannot_read_or_sign(): void
    {
        $request = $this->create();
        foreach ([$this->teacher, User::findOrFail($this->schoolA->ids['asha_user']), User::findOrFail($this->schoolA->ids['gina_user']), app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user']))] as $actor) {
            try {
                app(Consents::class)->read($actor, $request['id'], $this->learner);
                $this->fail('Private consent leaked.');
            } catch (HttpException|ModelNotFoundException $error) {
                $this->assertContains($error instanceof HttpException ? $error->getStatusCode() : 404, [403, 404]);
            }
        }
        $this->assertSame(0, ConsentResponse::count());
    }

    public function test_withdrawn_approval_group_or_guardian_grant_stops_effective_consent_and_replay(): void
    {
        $request = $this->create();
        $input = $this->response($request);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input);
        ActivityMembership::where('learner_id', $this->learner)->update(['active' => false]);
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        ActivityMembership::where('learner_id', $this->learner)->update(['active' => true]);
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        $this->expectException(HttpException::class);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input);
    }

    public function test_negative_response_can_withdraw_after_deadline_without_losing_prior_attestation(): void
    {
        $request = $this->create();
        $input = $this->response($request);
        $first = app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input);
        $this->travelTo(now()->setDate(2026, 2, 20));
        $this->assertSame($first, app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input));
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request, ['revision' => 1, 'decision' => 'no']));
        $this->assertSame(2, ConsentResponse::count());
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
    }

    public function test_unassigned_staff_and_short_future_membership_cannot_publish_a_trip(): void
    {
        try {
            $this->create(actor: User::findOrFail($this->schoolA->ids['theo_user']));
            $this->fail('Unassigned teacher published consent.');
        } catch (HttpException $error) {
            $this->assertSame(404, $error->getStatusCode());
        }
        ActivityMembership::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-15']);
        $this->expectException(HttpException::class);
        $this->create();
    }

    public function test_platform_impersonation_and_converted_parent_cannot_open_health_or_consent(): void
    {
        $request = $this->create();
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        try {
            app(Consents::class)->read($this->parent, $request['id'], $this->learner);
            $this->fail('Platform identity opened a consent body.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        PlatformStaffIdentity::where('user_id', $this->parent->id)->delete();
        SchoolMembership::where('user_id', $this->parent->id)->where('role', 'Parent')->update(['status' => 'ended']);
        SchoolMembership::create(['user_id' => $this->parent->id, 'school_id' => 1, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->expectException(HttpException::class);
        app(HealthForms::class)->read($this->parent, $this->learner);
    }

    public function test_health_reuses_only_encrypted_health_fields_and_never_updates_identity_fields(): void
    {
        $health = app(HealthForms::class);
        $result = $health->save($this->parent, $this->learner, ['medical_alerts' => 'Synthetic private alert', 'allergies' => 'Synthetic allergy']);
        $this->assertSame(['health'], array_column($result['categories'], 'key'));
        $this->assertCount(2, $result['categories'][0]['fields']);
        $this->assertStringNotContainsString('Synthetic private alert', DB::table('profile_field_values')->value('value'));
        $this->assertSame(2, ProfileFieldValue::where('user_id', $this->schoolA->ids['asha_user'])->count());
        $this->expectException(HttpException::class);
        $health->save($this->parent, $this->learner, ['first_name' => 'Unauthorized identity change']);
    }

    public function test_cancellation_revokes_effective_consent_but_retains_history_and_rollback_is_refused(): void
    {
        $request = $this->create();
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request));
        $cancel = ['operation_id' => (string) Str::uuid(), 'revision' => 1];
        $first = app(Consents::class)->cancel($this->teacher, $request['id'], $cancel);
        $this->assertSame($first, app(Consents::class)->cancel($this->teacher, $request['id'], $cancel));
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        $this->assertSame('yes', app(Consents::class)->read($this->parent, $request['id'], $this->learner)['decision']);
        $this->assertSame('cancelled', ConsentRequest::findOrFail($request['id'])->status);
        $this->assertSame(1, ConsentResponse::count());
        $this->expectException(\RuntimeException::class);
        (require database_path('migrations/2026_10_26_001000_create_family_consents.php'))->down();
    }

    public function test_second_guardian_stale_revision_cannot_silently_overwrite_a_signed_decision(): void
    {
        $request = $this->create();
        $firstInput = $this->response($request);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $firstInput);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::create(['learner_id' => $this->learner, 'guardian_user_id' => $other->id, 'status' => 'active', 'can_approve' => true, 'relationship' => 'guardian']);
        try {
            app(Consents::class)->respond($other, $request['id'], $this->learner, $this->response($request, ['signed_name' => $other->full_name, 'decision' => 'no']));
            $this->fail('A stale second guardian silently replaced consent.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        app(Consents::class)->respond($other, $request['id'], $this->learner, $this->response($request, ['signed_name' => $other->full_name, 'decision' => 'no', 'revision' => 1]));
        $this->assertSame(2, ConsentResponse::count());
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
    }

    public function test_stored_wording_tampering_fails_closed_before_private_read_or_operational_use(): void
    {
        $request = $this->create();
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request));
        ConsentRequest::findOrFail($request['id'])->update(['wording' => 'Changed after signature']);
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        $this->expectException(HttpException::class);
        app(Consents::class)->read($this->parent, $request['id'], $this->learner);
    }

    public function test_health_category_does_not_decrypt_unrelated_profile_values(): void
    {
        $field = ProfileFieldDefinition::where('profile_type', 'learner')->where('key', 'identity_number')->firstOrFail();
        ProfileFieldValue::create(['user_id' => $this->schoolA->ids['asha_user'], 'definition_id' => $field->id, 'value' => 'synthetic-invalid-ciphertext', 'encrypted' => true]);
        $this->assertSame(['health'], array_column(app(HealthForms::class)->read($this->parent, $this->learner)['categories'], 'key'));
    }

    public function test_future_school_membership_must_cover_the_event_even_if_allocation_remains(): void
    {
        SchoolMembership::where('school_id', 1)->where('user_id', $this->teacher->id)->where('role', 'Teacher')->update(['ends_on' => '2026-02-15']);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->teacher->id, 'role' => 'Teacher', 'status' => 'active', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        $this->expectException(HttpException::class);
        $this->create();
    }

    public function test_outer_transaction_failure_rolls_back_consent_audience_and_receipt(): void
    {
        try {
            DB::transaction(function () {
                $this->create();
                throw new \RuntimeException('Synthetic outer rollback');
            });
        } catch (\RuntimeException) {
            $this->assertSame(0, ConsentRequest::count());
            $this->assertSame(0, ConsentRecipient::count());
            $this->assertSame(0, FamilyFormOperation::count());
        }
    }

    public function test_parent_linked_at_both_schools_still_reads_only_the_selected_schools_consents(): void
    {
        $request = $this->create();
        $other = app(CurrentSchool::class)->run(2, function () {
            $learner = $this->schoolB->ids['asha'];
            $teacher = User::findOrFail($this->schoolB->ids['tara_user']);
            SchoolMembership::create(['school_id' => 2, 'user_id' => $this->parent->id, 'role' => 'Parent', 'status' => 'active', 'starts_on' => '2026-01-01']);
            GuardianLink::create(['learner_id' => $learner, 'guardian_user_id' => $this->parent->id, 'status' => 'active', 'can_approve' => true, 'relationship' => 'guardian']);
            Enrolment::create(['learner_id' => $learner, 'session_year_id' => $this->schoolB->ids['session_year'], 'class_section_id' => $this->schoolB->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
            app(ApprovalService::class)->decide($this->parent, Students::findOrFail($learner), 'approve', config('identity.consent_version'));
            $group = ActivityGroup::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab private trip', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
            ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic Baobab allocation', 'revision' => 1]);
            ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic Baobab group', 'revision' => 1]);
            $result = $this->create(['activity_group_id' => $group->id, 'learner_ids' => [$learner], 'title' => 'Baobab private consent'], $teacher);
            $this->assertSame('Baobab private consent', app(Consents::class)->read($this->parent, $result['id'], $learner)['title']);

            return ['request' => $result, 'learner' => $learner];
        });
        $this->assertSame('Synthetic private trip', app(Consents::class)->read($this->parent, $request['id'], $this->learner)['title']);
        $this->expectException(ModelNotFoundException::class);
        app(Consents::class)->read($this->parent, $other['request']['id'], $other['learner']);
    }

    public function test_database_rejects_a_request_pointing_to_another_schools_activity_group(): void
    {
        $this->expectException(QueryException::class);
        DB::table('consent_requests')->insert(['id' => (string) Str::uuid(), 'school_id' => 2, 'session_year_id' => $this->schoolB->ids['session_year'],
            'activity_group_id' => $this->group->id, 'created_by' => $this->teacher->id, 'title' => 'Synthetic cross-school attempt', 'wording' => 'Synthetic',
            'fingerprint' => str_repeat('0', 64), 'event_starts_at' => '2026-02-20 08:00:00', 'event_ends_at' => '2026-02-20 10:00:00', 'respond_by' => '2026-02-19 10:00:00']);
    }

    public function test_legacy_profile_reader_cannot_bypass_health_exclusion_for_a_platform_identity(): void
    {
        app(HealthForms::class)->save($this->parent, $this->learner, ['medical_alerts' => 'Synthetic restricted medical value']);
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        $schema = app(ProfileForms::class)->schema($this->parent, User::findOrFail($this->schoolA->ids['asha_user']), 'learner');
        $this->assertSame([], array_values(array_filter($schema['categories'], fn ($category) => $category['key'] === 'health')));
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('family-form-api')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_private_http_connects_configuration_publication_signing_managed_roster_and_health(): void
    {
        $this->api($this->parent)->getJson('/api/v2/family-forms/configuration')->assertOk()->assertJsonPath('data.children.0.id', $this->learner)->assertJsonPath('data.children.0.person_id', $this->schoolA->ids['asha_user'])->assertJsonPath('data.school_id', 1)->assertJsonPath('data.reader_id', (int) $this->parent->id)->assertJsonCount(0, 'data.groups');
        $this->api($this->teacher)->getJson('/api/v2/family-forms/configuration')->assertOk()->assertJsonPath('data.groups.0.id', $this->group->id);
        $input = ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $this->group->id, 'title' => 'Synthetic API trip', 'wording' => 'Exact API consent wording',
            'event_starts_at' => '2026-02-20T08:00:00.000Z', 'event_ends_at' => '2026-02-20T10:00:00.000000Z', 'respond_by' => '2026-02-19T10:00:00Z', 'learner_ids' => [$this->learner]];
        $request = $this->api($this->teacher)->postJson('/api/v2/family-forms/consents', $input)->assertOk()->json('data');
        $url = '/api/v2/family-forms/consents/'.$request['id'];
        $this->api($this->parent)->getJson('/api/v2/family-forms/consents?learner_id='.$this->learner)->assertOk()->assertJsonPath('data.items.0.title', 'Synthetic API trip');
        $this->getJson($url.'?learner_id='.$this->learner)->assertOk()->assertJsonPath('data.wording', 'Exact API consent wording');
        $this->postJson($url.'/learners/'.$this->learner.'/response', $this->response($request))->assertOk()->assertJsonPath('data.decision', 'yes');
        $this->getJson($url.'/managed')->assertForbidden()->assertDontSee('Exact API consent wording');
        $this->api($this->teacher)->getJson($url.'/managed')->assertOk()->assertJsonPath('data.recipients.0.effective', true);
        $this->api($this->parent)->putJson('/api/v2/family-forms/learners/'.$this->learner.'/health', ['values' => ['medical_alerts' => ['text' => 'Synthetic private HTTP medical value']]])->assertOk()->assertJsonPath('data.categories.0.key', 'health');
        $this->getJson('/api/v2/family-forms/learners/'.$this->learner.'/health')->assertOk()->assertSee('Synthetic private HTTP medical value')->assertHeader('Cache-Control', 'no-store, private');
        $this->api($this->teacher)->getJson('/api/v2/family-forms/learners/'.$this->learner.'/health')->assertForbidden()->assertDontSee('Synthetic private HTTP medical value');
    }

    public function test_family_http_refusals_and_validation_are_private_before_auth_without_flash(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/v2/family-forms/configuration')->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->api($this->parent)->withHeader('X-Account-Id', (string) $this->teacher->id)->getJson('/api/v2/family-forms/configuration')->assertForbidden();
        $this->api($this->teacher)->withHeader('Accept', 'text/html')->post('/api/v2/family-forms/consents', ['title' => 'Synthetic private unsaved wording'])->assertUnprocessable()->assertJsonPath('error', true)->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $this->api($this->parent)->putJson('/api/v2/family-forms/learners/'.$this->learner.'/health?values=private', ['values' => ['medical_alerts' => ['text' => 'Synthetic query bypass']]])->assertUnprocessable()->assertSessionMissing('_old_input');
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        $this->api($this->parent)->getJson('/api/v2/family-forms/configuration')->assertForbidden();
    }

    public function test_exact_signed_receipt_survives_account_name_change_and_read_only_without_new_writes(): void
    {
        $request = $this->create();
        $input = $this->response($request);
        $first = app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input);
        $this->parent->update(['first_name' => 'Renamed guardian']);
        config(['modules.family_forms.always_on' => false]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'family_forms'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->assertSame($first, app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $input));
        $this->assertTrue(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        $this->expectException(HttpException::class);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request, ['revision' => 1, 'signed_name' => $this->parent->full_name]));
    }

    public function test_signed_history_survives_application_key_rotation_with_retained_verification_keys(): void
    {
        $request = $this->create();
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request));
        $old = config('app.key');
        config(['app.previous_keys' => [$old], 'app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');
        $this->assertTrue(app(Consents::class)->effective($this->teacher, $request['id'], $this->learner));
        $this->assertSame('yes', app(Consents::class)->read($this->parent, $request['id'], $this->learner)['decision']);
    }

    private function panelData(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->teacher->id, 'expected_school_id' => 1,
            'activity_group_id' => $this->group->id, 'title' => 'Synthetic panel trip', 'wording' => 'Synthetic complete wording <script>unsafe()</script>',
            'event_starts_at_local' => '2026-02-20T10:00', 'event_ends_at_local' => '2026-02-20T12:00', 'respond_by_local' => '2026-02-19T12:00', 'learner_ids' => [$this->learner]];
    }

    public function test_panel_publishes_exact_wording_and_records_then_withdraws_permission(): void
    {
        $this->actingAs($this->teacher)->get('/family-forms/new')->assertOk()->assertSee('Publish activity or trip consent')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/family-forms/prepare', $this->panelData())->assertOk()->assertSee('Publish exact consent request')->assertSee(Students::findOrFail($this->learner)->user->full_name);
        $this->post('/family-forms/consents', $this->panelData())->assertRedirect();
        $record = ConsentRequest::sole();
        $this->assertSame('2026-02-20T08:00:00+00:00', $record->event_starts_at->toIso8601String());
        $this->get("/family-forms/consents/{$record->id}/managed")->assertOk()->assertSee('Permission is not current')->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)->assertDontSee('<script>unsafe()</script>', false);
        $request = app(Consents::class)->read($this->parent, $record->id, $this->learner);
        $path = "/api/v2/family-forms/consents/{$record->id}/learners/{$this->learner}/response";
        $this->api($this->parent)->postJson($path, $this->response($request))->assertOk();
        $this->flushHeaders()->actingAs($this->teacher, 'web')->get("/family-forms/consents/{$record->id}/managed")->assertOk()->assertSee('Current signed permission');
        $this->api($this->parent)->postJson($path, $this->response($request, ['decision' => 'no', 'revision' => 1]))->assertOk();
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $record->id, $this->learner));
    }

    public function test_panel_invalid_draft_is_private_escaped_and_never_session_flashed(): void
    {
        $this->actingAs($this->teacher)->post('/family-forms/consents', $this->panelData(['learner_ids' => []]))->assertUnprocessable()
            ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)->assertDontSee('<script>unsafe()</script>', false)
            ->assertSessionMissing('_old_input')->assertSessionMissing('errors')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/family-forms/consents', $this->panelData(['expected_account_id' => $this->parent->id]))->assertForbidden()->assertDontSee('Synthetic complete wording')->assertSessionMissing('_old_input');
        $this->post('/family-forms/consents?title=private-query', $this->panelData())->assertUnprocessable()->assertDontSee('Synthetic complete wording')->assertSessionMissing('_old_input');
        $this->assertSame(0, ConsentRequest::count());
    }

    public function test_panel_refuses_family_only_unassigned_and_foreign_staff_before_private_body(): void
    {
        $record = $this->create();
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->get("/family-forms/consents/{$record['id']}/managed")->assertNotFound()->assertDontSee('Synthetic exact consent');
        $this->actingAs($this->parent)->get("/family-forms/consents/{$record['id']}/managed")->assertForbidden()->assertDontSee('Synthetic exact consent');
        $other = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user']));
        $this->actingAs($other)->withHeaders(['X-School-Id' => '2'])->get("/family-forms/consents/{$record['id']}/managed")->assertForbidden()->assertDontSee('Synthetic exact consent');
    }

    public function test_eligible_roster_filters_stale_enrolment_without_breaking_valid_classmates(): void
    {
        $duplicate = ActivityMembership::where('learner_id', $this->learner)->firstOrFail()->replicate();
        $duplicate->save();
        $second = (int) Students::where('id', '!=', $this->learner)->firstOrFail()->id;
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $second, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic retained participant', 'revision' => 1]);
        $data = ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $this->group->id, 'event_starts_at' => '2026-02-20T10:00:00+02:00', 'event_ends_at' => '2026-02-20T12:00:00+02:00', 'respond_by' => '2026-02-19T12:00:00+02:00'];
        $this->assertSame([$this->learner], array_column(app(Consents::class)->eligible($this->teacher, $data), 'id'));
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-19']);
        $this->assertSame([], app(Consents::class)->eligible($this->teacher, $data));
    }

    public function test_panel_readonly_module_keeps_staff_cancellation_and_retained_history(): void
    {
        $record = $this->create();
        app(Consents::class)->respond($this->parent, $record['id'], $this->learner, $this->response($record));
        config(['modules.family_forms' => ['always_on' => false]]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'family_forms'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $path = "/family-forms/consents/{$record['id']}";
        $this->actingAs($this->teacher)->get($path.'/managed')->assertOk()->assertSee('Current signed permission');
        $this->post($path.'/cancel', ['operation_id' => (string) Str::uuid(), 'revision' => $record['revision'], 'expected_account_id' => $this->teacher->id, 'expected_school_id' => 1])->assertRedirect($path.'/managed');
        $this->assertSame(1, ConsentResponse::count());
        $this->assertFalse(app(Consents::class)->effective($this->teacher, $record['id'], $this->learner));
    }
}
