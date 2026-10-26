<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyVisits;

use App\Models\Cards\Card;
use App\Models\Cards\CardReaderDevice;
use App\Models\Cards\CardScanLog;
use App\Models\FamilyVisits\FamilyVisitEvent;
use App\Models\FamilyVisits\GatePass;
use App\Models\FamilyVisits\MeetingSlot;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardReaders;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\FamilyVisits\VisitQueries;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FamilyVisitsTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $teacher;

    private int $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(8, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_visits' => ['always_on' => true], 'modules.cards' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->learner = $this->schoolA->ids['asha'];
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
    }

    private function slot(array $changes = [], ?User $actor = null): array
    {
        return app(Meetings::class)->create($actor ?? $this->admin, $changes + ['operation_id' => (string) Str::uuid(), 'host_id' => $this->teacher->id,
            'title' => 'Synthetic private parents evening', 'venue' => 'Synthetic room', 'starts_at' => '2026-02-20T17:00', 'ends_at' => '2026-02-20T17:15', 'booking_closes_at' => '2026-02-19T12:00']);
    }

    private function pass(array $changes = [], ?User $actor = null): array
    {
        return app(GatePasses::class)->request($actor ?? $this->parent, $changes + ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner,
            'collector' => 'Synthetic designated collector', 'reason' => 'Synthetic private appointment', 'starts_at' => now()->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addHour()->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i')]);
    }

    private function scan(): array
    {
        $reader = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic gate', 'kind' => 'gate', 'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
        $card = Card::create(['user_id' => $this->schoolA->ids['asha_user'], 'type' => 'learner', 'status' => 'active', 'revision' => 1, 'issued_by' => $this->admin->id, 'issued_at' => now()->utc(), 'active_identity' => 'learner:'.$this->schoolA->ids['asha_user']]);
        // Accepted scan is a server-owned fixture; clients cannot create this row.
        $scan = CardScanLog::create(['device_id' => $reader['id'], 'card_id' => $card->id, 'user_id' => $card->user_id, 'method' => 'qr', 'outcome' => 'accepted', 'scanned_at' => now()->utc()]);

        return [$reader, $card, $scan];
    }

    private function refused(callable $action, array $codes = [401, 403, 404, 409]): void
    {
        try {
            $action();
            $this->fail('An unauthorised or stale visit action succeeded.');
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            $this->assertContains($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 404, $codes);
        }
    }

    public function test_meeting_booking_retry_cancel_and_rebook_preserve_history_and_encryption(): void
    {
        $slot = $this->slot();
        $this->assertSame('2026-02-20T15:00:00+00:00', $slot['starts_at']);
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner];
        $booked = app(Meetings::class)->book($this->parent, $slot['id'], $body);
        $this->assertSame($booked, app(Meetings::class)->book($this->parent, $slot['id'], $body));
        $this->assertSame('booked', $booked['status']);
        $this->assertSame(2, FamilyVisitEvent::count());
        $this->assertSame(1, MeetingSlot::count());
        $this->assertStringNotContainsString('Synthetic private', DB::table('meeting_slots')->value('title'));
        $this->assertStringNotContainsString('Synthetic room', DB::table('family_visit_operations')->value('receipt'));
        $this->assertStringNotContainsString('Synthetic', DB::table('family_visit_events')->value('snapshot'));
        $cancel = ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'learner_id' => $this->learner];
        $unbooked = app(Meetings::class)->unbook($this->parent, $slot['id'], $cancel);
        $this->assertSame($unbooked, app(Meetings::class)->unbook($this->parent, $slot['id'], $cancel));
        $this->assertSame('open', $unbooked['status']);
        $this->assertNull(MeetingSlot::first()->learner_id);
        $new = app(Meetings::class)->book($this->parent, $slot['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 3, 'learner_id' => $this->learner]);
        $this->assertSame(4, $new['revision']);
        $this->assertSame(4, FamilyVisitEvent::count());
    }

    public function test_host_and_family_overlap_and_stale_revisions_are_rejected(): void
    {
        $slot = $this->slot();
        $this->refused(fn () => $this->slot());
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner];
        app(Meetings::class)->book($this->parent, $slot['id'], $body);
        $this->refused(fn () => app(Meetings::class)->book($this->parent, $slot['id'], array_merge($body, ['operation_id' => (string) Str::uuid()])));
        $this->refused(fn () => app(Meetings::class)->book($this->parent, $slot['id'], array_merge($body, ['learner_id' => $this->schoolA->ids['ben']])));
        $other = $this->slot(['host_id' => $this->admin->id]);
        $this->refused(fn () => app(Meetings::class)->book($this->parent, $other['id'], array_merge($body, ['operation_id' => (string) Str::uuid()])));
    }

    public function test_wrong_roles_owners_school_and_platform_cannot_book_or_publish(): void
    {
        $slot = $this->slot();
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner];
        foreach ([$this->teacher, User::findOrFail($this->schoolA->ids['asha_user']), User::findOrFail($this->schoolA->ids['gina_user'])] as $actor) {
            $this->refused(fn () => app(Meetings::class)->book($actor, $slot['id'], $body));
            $this->refused(fn () => $this->slot(actor: $actor));
        }
        $this->refused(fn () => app(CurrentSchool::class)->run(2, fn () => app(Meetings::class)->book($this->parent, $slot['id'], $body)));
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        $this->refused(fn () => app(Meetings::class)->book($this->parent, $slot['id'], $body));
        $this->assertNull(MeetingSlot::first()->learner_id);
    }

    public function test_revoked_guardian_denies_old_receipt_and_private_listing(): void
    {
        $slot = $this->slot();
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner];
        app(Meetings::class)->book($this->parent, $slot['id'], $body);
        GuardianLink::query()->where('learner_id', $this->learner)->update(['status' => 'revoked']);
        $this->refused(fn () => app(Meetings::class)->book($this->parent, $slot['id'], $body));
        $this->refused(fn () => app(VisitQueries::class)->meetings($this->parent, $this->learner));
    }

    public function test_host_reader_is_scoped_and_other_guardian_cannot_view_booking(): void
    {
        $slot = $this->slot();
        $other = $this->slot(['host_id' => $this->admin->id]);
        app(Meetings::class)->book($this->parent, $slot['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner]);
        $staff = app(VisitQueries::class)->meetings($this->teacher, null);
        $this->assertSame([$slot['id']], array_column($staff['items'], 'id'));
        $this->assertSame($this->learner, $staff['items'][0]['learner_id']);
        $this->assertCount(2, app(VisitQueries::class)->meetings($this->admin, null)['items']);
        $this->assertSame([$slot['id'], $other['id']], array_column(app(VisitQueries::class)->meetings($this->parent, $this->learner)['items'], 'id'));
    }

    public function test_parent_can_withdraw_booking_after_optional_module_is_disabled(): void
    {
        $slot = $this->slot();
        app(Meetings::class)->book($this->parent, $slot['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner]);
        config(['modules.family_visits' => ['default' => false]]);
        // Host lookups must not turn an optional-module shutdown into a cancellation lock.
        $cancelled = app(Meetings::class)->unbook($this->parent, $slot['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'learner_id' => $this->learner]);
        $this->assertSame('open', $cancelled['status']);
    }

    public function test_pending_pass_cannot_clear_gate_and_collection_is_explicit_retry_safe_once(): void
    {
        $pass = $this->pass();
        [$reader, , $scan] = $this->scan();
        $service = app(GatePasses::class);
        $this->assertSame([], $service->lookup($reader['credential'], $scan->id)['passes']);
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'scan_id' => $scan->id, 'collector_confirmed' => true];
        $this->refused(fn () => $service->collect($reader['credential'], $pass['id'], $body));
        $approved = $service->approve($this->admin, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        $lookup = $service->lookup($reader['credential'], $scan->id);
        $this->assertCount(1, $lookup['passes']);
        $this->assertArrayNotHasKey('reason', $lookup['passes'][0]);
        $body['revision'] = $approved['revision'];
        $result = $service->collect($reader['credential'], $pass['id'], $body);
        $this->assertSame($result, $service->collect($reader['credential'], $pass['id'], $body));
        $this->assertSame('collected', $result['status']);
        $this->assertSame([], $service->lookup($reader['credential'], $scan->id)['passes']);
        $this->refused(fn () => $service->collect($reader['credential'], $pass['id'], array_merge($body, ['operation_id' => (string) Str::uuid()])));
        $this->assertSame(3, FamilyVisitEvent::count());
        $this->assertStringNotContainsString('designated collector', DB::table('gate_passes')->value('collector'));
        $this->assertStringNotContainsString('private appointment', DB::table('gate_passes')->value('reason'));
    }

    public function test_revoked_guardian_or_approver_erases_gate_clearance_and_replay(): void
    {
        $pass = $this->pass();
        [$reader, , $scan] = $this->scan();
        app(GatePasses::class)->approve($this->admin, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        GuardianLink::where('learner_id', $this->learner)->update(['can_approve' => false]);
        $this->refused(fn () => app(GatePasses::class)->lookup($reader['credential'], $scan->id));
        $this->refused(fn () => app(GatePasses::class)->collect($reader['credential'], $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'scan_id' => $scan->id, 'collector_confirmed' => true]));
        $this->assertSame('approved', GatePass::first()->status);
    }

    public function test_scan_expiry_frozen_card_and_revoked_reader_deny_pass_lookup(): void
    {
        $this->pass();
        [$reader, $card, $scan] = $this->scan();
        $scan->forceFill(['scanned_at' => now()->subSeconds(121)])->save();
        $this->refused(fn () => app(GatePasses::class)->lookup($reader['credential'], $scan->id));
        $scan->forceFill(['scanned_at' => now()])->save();
        $card->forceFill(['status' => 'frozen'])->save();
        $this->refused(fn () => app(GatePasses::class)->lookup($reader['credential'], $scan->id));
        $card->forceFill(['status' => 'active'])->save();
        CardReaderDevice::whereKey($reader['id'])->update(['revoked_at' => now()]);
        $this->refused(fn () => app(GatePasses::class)->lookup($reader['credential'], $scan->id));
    }

    public function test_wrong_device_scan_wrong_school_and_non_manager_cannot_collect_or_approve(): void
    {
        $pass = $this->pass();
        [$reader, , $scan] = $this->scan();
        $other = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Other gate', 'kind' => 'gate', 'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
        $this->refused(fn () => app(GatePasses::class)->lookup($other['credential'], $scan->id));
        $this->refused(fn () => app(GatePasses::class)->approve($this->teacher, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]));
        $this->refused(fn () => app(CurrentSchool::class)->run(2, fn () => app(GatePasses::class)->lookup($reader['credential'], $scan->id)));
    }

    public function test_parent_cancellation_removes_approved_pass_even_with_module_disabled(): void
    {
        $pass = $this->pass();
        app(GatePasses::class)->approve($this->admin, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        config(['modules.family_visits' => ['default' => false]]);
        $result = app(GatePasses::class)->cancel($this->parent, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 2]);
        $this->assertSame('cancelled', $result['status']);
    }

    public function test_gate_rules_are_revisioned_and_bound_without_weakening_required_checks(): void
    {
        $rules = app(VisitQueries::class)->gateRules();
        $this->assertSame(0, $rules['revision']);
        $result = app(VisitQueries::class)->changeRules($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'max_window_minutes' => 30, 'max_future_days' => 2, 'scan_max_age_seconds' => 30]);
        $this->assertSame(1, $result['revision']);
        $this->assertTrue($result['staff_approval_required']);
        $this->refused(fn () => $this->pass(), [422]);
        $this->assertSame(0, GatePass::count());
    }

    public function test_disabled_module_keeps_identity_lookup_without_exposing_or_collecting_passes(): void
    {
        $pass = $this->pass();
        [$reader, , $scan] = $this->scan();
        app(GatePasses::class)->approve($this->admin, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        config(['modules.family_visits' => ['default' => false]]);
        $lookup = app(GatePasses::class)->lookup($reader['credential'], $scan->id);
        $this->assertFalse($lookup['available']);
        $this->assertSame([], $lookup['passes']);
        $this->assertSame('', $lookup['learner_name']);
        $this->refused(fn () => app(GatePasses::class)->collect($reader['credential'], $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'scan_id' => $scan->id, 'collector_confirmed' => true]));
    }

    public function test_expired_pass_or_unconfirmed_collector_cannot_be_consumed(): void
    {
        $pass = $this->pass();
        [$reader, , $scan] = $this->scan();
        app(GatePasses::class)->approve($this->admin, $pass['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'scan_id' => $scan->id, 'collector_confirmed' => true];
        try {
            app(GatePasses::class)->collect($reader['credential'], $pass['id'], array_merge($body, ['collector_confirmed' => false]));
            $this->fail('Collector check was optional.');
        } catch (ValidationException) {
            $this->assertSame('approved', GatePass::first()->status);
        }
        $this->travelTo(now()->addHours(2));
        $scan->forceFill(['scanned_at' => now()])->save();
        $this->assertSame([], app(GatePasses::class)->lookup($reader['credential'], $scan->id)['passes']);
        $this->refused(fn () => app(GatePasses::class)->collect($reader['credential'], $pass['id'], $body));
    }

    public function test_wall_times_reject_nonexistent_or_ambiguous_instants_and_invalid_windows(): void
    {
        app(CurrentSchool::class)->school()->forceFill(['timezone' => 'Europe/London'])->save();
        foreach ([['starts_at' => '2026-03-29T01:30', 'ends_at' => '2026-03-29T02:00'], ['starts_at' => '2026-10-25T01:30', 'ends_at' => '2026-10-25T02:00']] as $dates) {
            try {
                $this->slot($dates);
                $this->fail('A DST gap or fold was guessed.');
            } catch (ValidationException) {
                $this->assertSame(0, MeetingSlot::count());
            }
        }
        $this->refused(fn () => $this->slot(['ends_at' => '2026-02-20T16:00']), [422]);
    }

    public function test_cross_school_composite_foreign_key_blocks_forged_collection_target(): void
    {
        try {
            DB::table('gate_passes')->insert(['id' => (string) Str::uuid(), 'school_id' => 2, 'learner_id' => $this->learner, 'requested_by' => $this->parent->id,
                'collector' => 'synthetic', 'reason' => 'synthetic', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'status' => 'pending', 'revision' => 1]);
            $this->fail('A foreign school learner was accepted.');
        } catch (QueryException $error) {
            $this->assertSame('23000', $error->errorInfo[0]);
        }
    }

    public function test_panel_requires_staff_and_fresh_write_scope_without_flashing_private_input(): void
    {
        $this->actingAs($this->parent)->get('/family-visits')->assertForbidden();
        $this->actingAs($this->teacher)->get('/family-visits')->assertOk()->assertDontSee('Approve this collection request')->assertDontSee('Publish meeting slot');
        $this->actingAs($this->admin)->get('/family-visits')->assertOk()->assertSee('Publish meeting slot')->assertHeader('Cache-Control', 'no-store, private');
        $body = ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->teacher->id, 'expected_school_id' => 1, 'host_id' => $this->teacher->id,
            'title' => 'Synthetic confidential panel title', 'venue' => 'Synthetic room', 'starts_at' => '2026-02-20T17:00', 'ends_at' => '2026-02-20T17:15', 'booking_closes_at' => '2026-02-19T12:00'];
        $this->post('/family-visits/meeting-create', $body)->assertForbidden()->assertSessionMissing('_old_input');
        $body['expected_account_id'] = $this->admin->id;
        $body = array_map(fn ($value) => is_int($value) ? (string) $value : $value, $body);
        $this->post('/family-visits/meeting-create', $body)->assertRedirect('/family-visits');
        $this->assertSame(1, MeetingSlot::count());
        $this->post('/family-visits/meeting-create', array_merge($body, ['title' => str_repeat('x', 192)]))->assertUnprocessable()->assertSessionMissing('_old_input');
    }

    public function test_parent_configuration_excludes_the_staff_roster_while_authorised_slots_include_their_host(): void
    {
        $slot = $this->slot();
        $config = app(VisitQueries::class)->configuration($this->parent);
        $this->assertSame([], $config['hosts']);
        $this->assertNotEmpty($config['children']);
        $this->assertNotEmpty(app(VisitQueries::class)->configuration($this->admin)['hosts']);
        $listing = app(VisitQueries::class)->meetings($this->parent, $this->learner);
        $this->assertSame($slot['host_name'], $listing['items'][0]['host_name']);
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('synthetic-visit-http')->plainTextToken)
            ->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $user->id]);
    }

    public function test_http_meeting_contracts_cover_config_scoped_lists_book_unbook_cancel_and_rules(): void
    {
        $this->api($this->parent)->getJson('/api/v2/family-visits/configuration')->assertOk()->assertJsonPath('data.school_id', 1)
            ->assertJsonPath('data.reader_id', (int) $this->parent->id)->assertJsonPath('data.children.0.id', $this->learner)
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $slot = $this->api($this->admin)->postJson('/api/v2/family-visits/meetings', ['operation_id' => (string) Str::uuid(), 'host_id' => $this->teacher->id,
            'title' => 'Synthetic HTTP evening', 'venue' => 'Synthetic room', 'starts_at' => '2026-02-20T17:00', 'ends_at' => '2026-02-20T17:15', 'booking_closes_at' => '2026-02-19T12:00'])->assertOk()->json('data');
        $url = '/api/v2/family-visits/meetings/'.$slot['id'];
        $this->api($this->parent)->getJson('/api/v2/family-visits/meetings?learner_id='.$this->learner)->assertOk()->assertJsonPath('data.items.0.status', 'open');
        $this->postJson($url.'/book', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner])->assertOk()->assertJsonPath('data.status', 'booked');
        $this->api($this->teacher)->getJson('/api/v2/family-visits/meetings')->assertOk()->assertJsonPath('data.items.0.learner_id', $this->learner);
        $this->api($this->parent)->postJson($url.'/unbook', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'learner_id' => $this->learner])->assertOk()->assertJsonPath('data.status', 'open');
        $this->api($this->admin)->postJson($url.'/cancel', ['operation_id' => (string) Str::uuid(), 'revision' => 3])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->putJson('/api/v2/family-visits/rules', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'max_window_minutes' => 90, 'max_future_days' => 14, 'scan_max_age_seconds' => 60])->assertOk()->assertJsonPath('data.revision', 1);
    }

    public function test_http_pass_contracts_require_real_guardian_staff_and_delegated_scan_authority(): void
    {
        $input = ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'collector' => 'Synthetic HTTP collector', 'reason' => 'Synthetic private HTTP reason',
            'starts_at' => now()->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addHour()->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i')];
        $pass = $this->api($this->parent)->postJson('/api/v2/family-visits/passes', $input)->assertOk()->assertJsonPath('data.status', 'pending')->json('data');
        $this->getJson('/api/v2/family-visits/passes?learner_id='.$this->learner)->assertOk()->assertJsonPath('data.items.0.id', $pass['id']);
        $this->api($this->admin)->postJson('/api/v2/family-visits/passes/'.$pass['id'].'/approve', ['operation_id' => (string) Str::uuid(), 'revision' => 1])->assertOk()->assertJsonPath('data.status', 'approved');
        [$reader, , $scan] = $this->scan();
        $url = '/api/v2/card-device/scans/'.$scan->id.'/gate-passes';
        $this->api($this->parent)->getJson($url)->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($reader['credential'])->getJson($url)->assertOk()->assertJsonPath('data.passes.0.collector', 'Synthetic HTTP collector')->assertDontSee('Synthetic private HTTP reason');
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'scan_id' => $scan->id, 'collector_confirmed' => true];
        $collect = '/api/v2/card-device/gate-passes/'.$pass['id'].'/collect';
        $this->postJson($collect, $body)->assertOk()->assertJsonPath('data.status', 'collected');
        $this->postJson($collect, $body)->assertOk()->assertJsonPath('data.status', 'collected');
        $this->withHeader('X-School-Id', '2')->getJson($url)->assertForbidden();
        $this->api($this->parent)->getJson('/api/v2/family-visits/configuration')->assertOk();
        $next = $this->postJson('/api/v2/family-visits/passes', array_merge($input, ['operation_id' => (string) Str::uuid()]))->assertOk()->json('data');
        $this->postJson('/api/v2/family-visits/passes/'.$next['id'].'/cancel', ['operation_id' => (string) Str::uuid(), 'revision' => 1])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_http_wrong_owner_role_school_account_and_extra_fields_refuse_without_private_bodies(): void
    {
        $slot = $this->slot();
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'learner_id' => $this->learner];
        $url = '/api/v2/family-visits/meetings/'.$slot['id'].'/book';
        $this->api($this->teacher)->postJson($url, $body)->assertForbidden()->assertDontSee('Synthetic private parents');
        $this->api(User::findOrFail($this->schoolA->ids['gina_user']))->postJson($url, $body)->assertNotFound();
        $this->api($this->parent)->withHeader('X-School-Id', '2')->postJson($url, $body)->assertForbidden();
        $this->api($this->parent)->withHeader('X-Account-Id', (string) $this->teacher->id)->postJson($url, $body)->assertForbidden();
        $this->api($this->parent)->postJson($url, $body + ['school_id' => 2])->assertUnprocessable();
        $this->postJson($url.'?learner_id='.$this->learner, $body)->assertUnprocessable();
        $this->assertNull(MeetingSlot::first()->learner_id);
    }
}
