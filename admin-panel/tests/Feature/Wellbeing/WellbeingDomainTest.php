<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Models\Wellbeing\Appointment;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\CaseEntry;
use App\Models\Wellbeing\Event;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\IncidentThread;
use App\Models\Wellbeing\TeamAssignment;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Wellbeing\Casework;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class WellbeingDomainTest extends TestCase
{
    use UsesTwoSchools;

    private User $professional;

    private array $category;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic wellbeing enrolment']);
        $this->professional = $this->professional('one');
        $this->category = app(WellbeingSettings::class)->saveCategory($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Safety concern', 'emergency_guidance' => 'Speak to a trusted adult immediately if unsafe.', 'active' => true]);
        app(WellbeingSettings::class)->assignTeam($this->superAdmin(), $this->category['id'], null, $this->grant($this->professional));
        $this->secret = bin2hex(random_bytes(32));
    }

    private function professional(string $suffix): User
    {
        $user = User::create(['first_name' => 'Synthetic', 'last_name' => 'Counsellor '.$suffix, 'email' => 'wellbeing-'.$suffix.'@example.test', 'password' => Hash::make('SyntheticWellbeing2026'), 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $user->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $user->assignRole('Counsellor');

        return $user;
    }

    private function grant(User $user, array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $user->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Explicit synthetic professional assignment'];
    }

    private function reportInput(array $extra = []): array
    {
        return $extra + ['secret' => $this->secret, 'category_id' => $this->category['id'], 'body' => 'Synthetic private incident body.', 'urgency' => 'urgent', 'anonymous' => true, 'acknowledged_guidance' => true];
    }

    private function careCase(): array
    {
        $row = app(WellbeingSettings::class)->openCase($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha']]);
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $row['id'], null, $this->grant($this->professional));

        return $row;
    }

    private function refuses(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('Restricted wellbeing action was accepted.');
        } catch (ModelNotFoundException $error) {
            $this->assertSame(404, $status);
        } catch (ValidationException $error) {
            $this->assertSame($status, $error->status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_anonymous_submission_and_follow_up_keep_no_identity_or_plaintext_and_replay_exactly(): void
    {
        $service = app(IncidentReporting::class);
        $result = $service->submit($this->reportInput(['reporter_user_id' => $this->superAdmin()->id]), $this->superAdmin());
        $this->assertTrue($result['received']);
        $this->assertTrue($service->followUp($this->secret)['anonymous']);
        $this->assertArrayNotHasKey('id', $result);
        $this->assertSame($result, $service->submit($this->reportInput()));
        $row = IncidentReport::firstOrFail();
        $this->assertNull($row->reporter_user_id);
        $this->assertNull($row->learner_id);
        $this->assertNull($row->identified_at);
        $this->assertNotSame($this->secret, $row->secret_digest);
        $this->assertStringNotContainsString('Synthetic private incident', DB::table('incident_reports')->value('body'));
        $this->assertStringNotContainsString($this->secret, json_encode((array) DB::table('incident_reports')->first()));
        $reply = ['operation_id' => (string) Str::uuid(), 'body' => 'Synthetic private follow-up.'];
        $one = $service->reply($this->secret, $reply);
        $this->assertSame($one, $service->reply($this->secret, $reply));
        $this->assertSame(1, IncidentThread::count());
        $service->respond($this->professional, $row->id, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'body' => 'Private internal review', 'internal' => true, 'status' => 'resolved']);
        $this->assertSame($result, $service->submit($this->reportInput()));
        $this->assertSame($one, $service->reply($this->secret, $reply));
        $this->assertSame('resolved', $service->followUp($this->secret)['status']);
        $this->assertSame(0, Event::whereNotNull('actor_id')->where('subject_type', 'incident_reports')->whereIn('event', ['wellbeing.report_submitted', 'wellbeing.reporter_follow_up'])->count());
        $this->assertStringNotContainsString('Synthetic private follow-up', DB::table('incident_threads')->value('body'));
        $this->refuses(fn () => $service->reply($this->secret, array_replace($reply, ['body' => 'Different retry'])), 409);
        $this->refuses(fn () => $service->followUp(str_repeat('0', 64)), 404);
        $this->refuses(fn () => $service->submit($this->reportInput(['body' => 'Changed initial report'])), 409);
    }

    public function test_emergency_guidance_is_readable_and_explicit_identity_consent_prevents_partial_report(): void
    {
        $service = app(IncidentReporting::class);
        $this->assertStringContainsString('immediate danger', app(WellbeingSettings::class)->categories()['emergency_guidance']);
        $this->refuses(fn () => $service->submit($this->reportInput(['learner_id' => $this->schoolA->ids['asha']])), 422);
        $this->refuses(fn () => $service->submit($this->reportInput(['anonymous' => false]), $this->superAdmin()), 422);
        $this->assertSame(0, IncidentReport::count());
        $service->submit($this->reportInput());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->refuses(fn () => $service->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => false]), 422);
        $identified = $service->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        $this->assertFalse($identified['anonymous']);
        $this->assertSame($parent->id, IncidentReport::firstOrFail()->reporter_user_id);
        $this->refuses(fn () => $service->followUp($this->secret), 403);
        $this->refuses(fn () => $service->followUp($this->secret, $this->professional), 403);
        $this->assertSame('Synthetic private incident body.', $service->followUp($this->secret, $parent)['body']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'guardian', 'status' => 'ended']);
        $this->refuses(fn () => $service->followUp($this->secret, $parent), 404);
    }

    public function test_professional_scope_excludes_admin_platform_ended_team_and_unrelated_professionals(): void
    {
        $service = app(IncidentReporting::class);
        $service->submit($this->reportInput());
        $id = IncidentReport::firstOrFail()->id;
        $this->assertSame('Synthetic private incident body.', $service->read($this->professional, $id)['body']);
        $this->refuses(fn () => $service->read($this->superAdmin(), $id), 403);
        $other = $this->professional('two');
        $this->refuses(fn () => $service->read($other, $id), 404);
        PlatformStaffIdentity::create(['user_id' => $this->professional->id, 'active' => true]);
        $this->refuses(fn () => $service->read($this->professional, $id), 403);
        PlatformStaffIdentity::where('user_id', $this->professional->id)->delete();
        TeamAssignment::where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => $service->read($this->professional, $id), 404);
        $this->assertSame([], $service->listing($this->professional)['items']);
    }

    public function test_internal_staff_follow_up_is_never_returned_to_the_reporter_and_revocation_blocks_replay(): void
    {
        $service = app(IncidentReporting::class);
        $service->submit($this->reportInput());
        $id = IncidentReport::firstOrFail()->id;
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'body' => 'Synthetic internal triage note.', 'internal' => true, 'status' => 'triaged'];
        $one = $service->respond($this->professional, $id, $input);
        $this->assertSame($one, $service->respond($this->professional, $id, $input));
        $this->assertSame([], $service->followUp($this->secret)['threads']);
        $this->assertStringNotContainsString('Synthetic internal triage note', json_encode(DB::table('wellbeing_events')->get()));
        SchoolMembership::where('user_id', $this->professional->id)->update(['status' => 'ended']);
        $this->refuses(fn () => $service->respond($this->professional, $id, $input), 403);
        $this->assertSame(1, IncidentThread::count());
    }

    public function test_case_grants_are_separate_and_admin_metadata_never_loads_clinical_entries(): void
    {
        $case = $this->careCase();
        $service = app(Casework::class);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'kind' => 'note', 'body' => 'Synthetic encrypted clinical body.', 'happened_at' => now()->subMinute()->toISOString()];
        $one = $service->entry($this->professional, $case['id'], $input);
        $this->assertSame($one, $service->entry($this->professional, $case['id'], $input));
        $this->assertSame(1, CaseEntry::count());
        $this->assertStringNotContainsString('Synthetic encrypted clinical', DB::table('support_case_entries')->value('body'));
        $this->assertStringNotContainsString('Synthetic encrypted clinical', DB::table('wellbeing_operations')->orderByDesc('id')->value('receipt'));
        $this->refuses(fn () => $service->read($this->superAdmin(), $case['id']), 403);
        $config = app(WellbeingSettings::class)->configuration($this->superAdmin());
        $this->assertStringNotContainsString('Synthetic encrypted clinical', json_encode($config));
        $other = $this->professional('separate');
        app(WellbeingSettings::class)->assignTeam($this->superAdmin(), $this->category['id'], null, $this->grant($other));
        $this->refuses(fn () => $service->read($other, $case['id']), 404);
        CaseAssignment::where('user_id', $this->professional->id)->update(['active' => false]);
        $this->refuses(fn () => $service->read($this->professional, $case['id']), 404);
        $this->refuses(fn () => $service->entry($this->professional, $case['id'], $input), 404);
    }

    public function test_appointments_are_case_scoped_encrypted_and_overlap_safe_with_idempotent_replay(): void
    {
        $case = $this->careCase();
        $service = app(Casework::class);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'professional_user_id' => $this->professional->id, 'starts_at' => '2026-02-11T08:00:00Z', 'ends_at' => '2026-02-11T08:30:00Z', 'location' => 'Synthetic private room', 'notes' => 'Synthetic appointment detail', 'status' => 'scheduled'];
        $one = $service->appointment($this->professional, $case['id'], null, $input);
        $this->assertSame($one, $service->appointment($this->professional, $case['id'], null, $input));
        $this->assertStringNotContainsString('Synthetic private room', DB::table('wellbeing_appointments')->value('location'));
        $this->refuses(fn () => $service->appointment($this->professional, $case['id'], null, array_replace($input, ['operation_id' => (string) Str::uuid(), 'starts_at' => '2026-02-11T08:15:00Z'])), 409);
        $this->assertSame(1, Appointment::count());
        $adjacent = $service->appointment($this->professional, $case['id'], null, array_replace($input, ['operation_id' => (string) Str::uuid(), 'starts_at' => '2026-02-11T08:30:00Z', 'ends_at' => '2026-02-11T09:00:00Z']));
        $this->assertNotSame($one['id'], $adjacent['id']);
        $this->refuses(fn () => $service->appointments($this->superAdmin(), $case['id'], '2026-02-01', '2026-02-28'), 403);
        $this->assertCount(2, $service->appointments($this->professional, $case['id'], '2026-02-01', '2026-02-28'));
    }

    public function test_cross_school_token_and_records_are_not_reachable_and_module_off_blocks_replay(): void
    {
        $service = app(IncidentReporting::class);
        $service->submit($this->reportInput());
        app(CurrentSchool::class)->run(2, function () use ($service) {
            // Granting the package module alone must not move a school's records.
            DB::table('school_modules')->updateOrInsert(['school_id' => 2, 'module' => 'wellbeing'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
            $this->refuses(fn () => $service->followUp($this->secret), 404);
        });
        app(ModuleGate::class)->change('wellbeing', false, $this->superAdmin());
        $this->refuses(fn () => $service->submit($this->reportInput()), 404);
        $this->refuses(fn () => $service->followUp($this->secret), 404);
    }

    public function test_impersonation_global_context_and_cached_professional_roles_never_load_clinical_bodies(): void
    {
        $case = $this->careCase();
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->refuses(fn () => app(Casework::class)->read($this->professional, $case['id']), 403);
        app(CurrentSchool::class)->allowAcrossSchools();
        $this->refuses(fn () => app(Casework::class)->read($this->professional, $case['id']), 403);
        app(CurrentSchool::class)->set(1);
        $this->professional->load('roles');
        SchoolMembership::where('user_id', $this->professional->id)->update(['status' => 'ended']);
        DB::enableQueryLog();
        $this->refuses(fn () => app(Casework::class)->read($this->professional, $case['id']), 403);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame([], array_values(array_filter($queries, fn ($query) => str_contains($query['query'], '`support_case_entries`'))));
    }

    public function test_case_corrections_preserve_original_history_and_large_receipts_replay_without_truncation(): void
    {
        $case = $this->careCase();
        $service = app(Casework::class);
        $large = str_repeat('界', 20000);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'kind' => 'note', 'body' => $large, 'happened_at' => now()->subMinute()->toISOString()];
        $one = $service->entry($this->professional, $case['id'], $input);
        $this->assertSame($one, $service->entry($this->professional, $case['id'], $input));
        $this->assertGreaterThan(65535, strlen(DB::table('wellbeing_operations')->where('operation_id', $input['operation_id'])->value('receipt')));
        $this->assertSame($large, CaseEntry::firstOrFail()->body);
        $correction = $service->entry($this->professional, $case['id'], array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'body' => 'Correction of the retained original', 'supersedes_id' => $one['entry']['id']]));
        $this->assertSame($one['entry']['id'], $correction['entry']['supersedes_id']);
        $this->assertSame(2, CaseEntry::count());
        $this->assertSame($large, CaseEntry::findOrFail($one['entry']['id'])->body);
        $other = $this->professional('correction');
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $case['id'], null, $this->grant($other));
        $this->refuses(fn () => $service->entry($other, $case['id'], array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 3, 'supersedes_id' => $one['entry']['id']])), 404);
    }

    public function test_linking_requires_both_assignments_identification_and_same_learner(): void
    {
        $case = $this->careCase();
        $reports = app(IncidentReporting::class);
        $reports->submit($this->reportInput());
        $report = IncidentReport::firstOrFail();
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'incident_report_id' => $report->id];
        $this->refuses(fn () => app(Casework::class)->linkIncident($this->professional, $case['id'], $input), 422);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $reports->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        $one = app(Casework::class)->linkIncident($this->professional, $case['id'], $input);
        $this->assertSame($report->id, $one['incident_report_id']);
        $other = $this->professional('linked');
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $case['id'], null, $this->grant($other));
        $this->assertNull(app(Casework::class)->read($other, $case['id'])['incident_report_id']);
        $this->refuses(fn () => app(Casework::class)->linkIncident($other, $case['id'], array_replace($input, ['operation_id' => (string) Str::uuid(), 'revision' => 2])), 404);
    }

    public function test_appointment_dates_require_the_professionals_case_grant_and_cancellation_cannot_create_foreign_links(): void
    {
        $case = $this->careCase();
        $service = app(Casework::class);
        CaseAssignment::where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-10']);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'professional_user_id' => $this->professional->id, 'starts_at' => '2026-02-11T08:00:00Z', 'ends_at' => '2026-02-11T08:30:00Z', 'location' => 'Private room', 'notes' => '', 'status' => 'scheduled'];
        $this->refuses(fn () => $service->appointment($this->professional, $case['id'], null, $input), 422);
        $this->refuses(fn () => $service->appointment($this->professional, $case['id'], null, array_replace($input, ['status' => 'cancelled', 'professional_user_id' => $this->schoolB->ids['tara_user']])), 422);
        $this->assertSame(0, Appointment::count());
        CaseAssignment::where('user_id', $this->professional->id)->update(['ends_on' => '2026-12-31']);
        SchoolMembership::where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-10']);
        $this->refuses(fn () => $service->appointment($this->professional, $case['id'], null, $input), 422);
    }

    public function test_assignment_cancellation_survives_role_revocation_and_keeps_encrypted_revision_history(): void
    {
        $case = $this->careCase();
        $assignment = CaseAssignment::firstOrFail();
        SchoolMembership::where('user_id', $this->professional->id)->update(['status' => 'ended']);
        $saved = app(WellbeingSettings::class)->assignCase($this->superAdmin(), $case['id'], $assignment->id, $this->grant($this->professional, ['revision' => 1, 'active' => false, 'reason' => 'Retained revoked professional assignment']));
        $this->assertFalse($saved['active']);
        $history = Event::where('subject_type', 'support_case_assignments')->where('subject_id', $assignment->id)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame('Explicit synthetic professional assignment', $history[0]->snapshot['reason']);
        $this->assertSame('Retained revoked professional assignment', $history[1]->snapshot['reason']);
        $this->assertStringNotContainsString('Retained revoked professional assignment', DB::table('wellbeing_events')->orderByDesc('id')->value('snapshot'));
    }

    public function test_system_permission_seed_preserves_custom_roles_and_never_grants_case_body_rights(): void
    {
        $custom = Role::create(['name' => 'Synthetic wellbeing helper', 'guard_name' => 'web', 'custom_role' => 1]);
        $custom->givePermissionTo('role-list');
        app(SchoolRoles::class)->seed(1);
        app(SchoolRoles::class)->seed(1);
        $this->assertSame(['role-list'], $custom->fresh()->permissions->pluck('name')->all());
        $this->assertTrue($this->superAdmin()->fresh()->hasPermissionTo('wellbeing-configure'));
        $this->assertFalse($this->superAdmin()->fresh()->hasPermissionTo('pastoral-sensitive-access'));
        $this->assertFalse($this->professional->fresh()->hasPermissionTo('wellbeing-configure'));
    }

    public function test_case_status_and_anonymous_new_follow_up_retain_history_instead_of_hiding_new_concerns(): void
    {
        $case = $this->careCase();
        app(Casework::class)->status($this->professional, $case['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'status' => 'closed', 'reason' => 'Synthetic closure reason']);
        $this->assertSame('closed', app(Casework::class)->read($this->professional, $case['id'])['status']);
        $this->assertSame('Synthetic closure reason', CaseEntry::firstOrFail()->body);
        $reports = app(IncidentReporting::class);
        $reports->submit($this->reportInput());
        $id = IncidentReport::firstOrFail()->id;
        $reports->respond($this->professional, $id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'body' => 'Synthetic response', 'internal' => false, 'status' => 'resolved']);
        $this->assertSame('resolved', $reports->followUp($this->secret)['status']);
        $reply = $reports->reply($this->secret, ['operation_id' => (string) Str::uuid(), 'body' => 'New information needs attention.']);
        $this->assertTrue($reply['posted']);
        $fresh = $reports->followUp($this->secret);
        $this->assertSame('open', $fresh['status']);
        $this->assertCount(2, $fresh['threads']);
    }
}
