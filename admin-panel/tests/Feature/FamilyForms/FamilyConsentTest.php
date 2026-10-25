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
use App\Models\Students;
use App\Models\User;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\HealthForms;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FamilyConsentTest extends TestCase
{
    use UsesTwoSchools;

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
}
