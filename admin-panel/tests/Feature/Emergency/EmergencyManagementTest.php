<?php

declare(strict_types=1);

namespace Tests\Feature\Emergency;

use App\Jobs\Platform\FanoutNotification;
use App\Jobs\Platform\SendPush;
use App\Models\Cards\Card;
use App\Models\Cards\CardScanLog;
use App\Models\Emergency\EmergencyAlert;
use App\Models\Emergency\EmergencyDrill;
use App\Models\Emergency\EmergencyEvent;
use App\Models\Emergency\EmergencyRelease;
use App\Models\Emergency\EmergencyVisitor;
use App\Models\FamilyVisits\GatePass;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Settings;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Cards\CardReaders;
use App\Services\Emergency\EmergencyAlerts;
use App\Services\Emergency\EmergencyDrills;
use App\Services\Emergency\EmergencyQueries;
use App\Services\Emergency\EmergencyReunification;
use App\Services\Emergency\EmergencyVisitors;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Notifications\UrgentMessaging;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Emergency management (Action Plan 5.8): critical alerts and lockdowns,
 * the drill log, visitor sign-in and reunification.
 * engineering/specs/community/emergency-management.md.
 */
class EmergencyManagementTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private User $teacher;

    private User $farid;

    private User $gina;

    private User $asha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config(['modules.emergency' => ['always_on' => true], 'modules.cards' => ['always_on' => true], 'modules.family_visits' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $this->asha = User::findOrFail($this->schoolA->ids['asha_user']);
        foreach (['asha' => 'section_a', 'ben' => 'section_a', 'cara' => 'section_c'] as $learner => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$learner], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids[$section],
                'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic emergency fixture', 'legacy_dates' => false]);
        }
        foreach ([[$this->farid, 'asha', 'father'], [$this->gina, 'ben', 'guardian']] as [$guardian, $learner, $relationship]) {
            $guardian->forceFill(['email_verified_at' => now()])->save();
            GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $guardian->id], ['status' => 'active', 'relationship' => $relationship, 'can_approve' => true]);
            app(ApprovalService::class)->decide($guardian, Students::findOrFail($this->schoolA->ids[$learner]), 'approve', config('identity.consent_version'));
        }
    }

    /** A request with this person's app token; the guard forgets whoever called before. */
    private function api(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    /** A panel request signed in as this person. */
    private function panel(User $user): static
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->withoutToken();

        return $this->actingAs($user);
    }

    private function op(array $input = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $input;
    }

    private function prepare(array $input = [], ?User $actor = null): array
    {
        return app(EmergencyAlerts::class)->prepare($actor ?? $this->admin, $this->op($input + ['kind' => 'lockdown', 'audience' => 'everyone', 'drill' => false]));
    }

    private function send(array $input = [], ?User $actor = null): array
    {
        $alert = $this->prepare($input, $actor);

        return app(EmergencyAlerts::class)->confirm($actor ?? $this->admin, $alert['id'], $this->op(['revision' => $alert['revision'], 'confirmation' => $alert['confirmation_word']]));
    }

    /** Runs the durable fanout the way the worker does, without sending anything to a provider. */
    private function deliver(): void
    {
        Queue::fake();
        Outbox::dispatchPending();
        foreach (Queue::pushed(FanoutNotification::class) as $job) {
            app()->call([$job, 'handle']);
        }
    }

    private function told(int $noticeId): array
    {
        return UserNotification::query()->where('notification_id', $noticeId)->orderBy('user_id')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    private function refused(callable $action, array $codes = [403, 404, 409, 422]): int
    {
        try {
            $action();
            $this->fail('An action that should be refused succeeded.');
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            $code = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : 404;
            $this->assertContains($code, $codes);

            return $code;
        }
    }

    private function guardianCardScan(User $guardian): CardScanLog
    {
        $reader = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic gate', 'kind' => 'gate', 'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
        $card = Card::create(['user_id' => $guardian->id, 'type' => 'guardian', 'status' => 'active', 'revision' => 1, 'issued_by' => $this->admin->id, 'issued_at' => now()->utc(), 'active_identity' => 'guardian:'.$guardian->id]);

        // An accepted scan is a server-owned fixture; clients cannot create this row.
        return CardScanLog::create(['device_id' => $reader['id'], 'card_id' => $card->id, 'user_id' => $guardian->id, 'method' => 'qr', 'outcome' => 'accepted', 'scanned_at' => now()->utc()]);
    }

    public function test_a_lockdown_is_prepared_confirmed_sent_to_staff_and_families_and_cleared(): void
    {
        $prepared = $this->prepare();
        $this->assertSame('pending', $prepared['status']);
        $this->assertSame('LOCKDOWN', $prepared['confirmation_word']);
        $this->assertSame(0, Notification::query()->where('type', EmergencyAlerts::TYPE)->count(), 'nothing is sent before the second step');
        $this->assertSame(422, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->admin, $prepared['id'], $this->op(['revision' => 1, 'confirmation' => 'yes']))));

        $sent = app(EmergencyAlerts::class)->confirm($this->admin, $prepared['id'], $this->op(['revision' => 1, 'confirmation' => 'lockdown']));
        $this->assertSame('active', $sent['status']);
        $this->assertSame($this->admin->id, $sent['confirmed_by']);
        $this->deliver();
        $alert = EmergencyAlert::findOrFail($sent['id']);
        $told = $this->told((int) $alert->notification_id);
        foreach (['tara_user', 'theo_user', 'farid_user', 'mona_user', 'gina_user'] as $person) {
            $this->assertContains($this->schoolA->ids[$person], $told, $person.' is told');
        }
        foreach (['asha_user', 'ben_user', 'cara_user'] as $learner) {
            $this->assertNotContains($this->schoolA->ids[$learner], $told, 'learners are never sent alerts');
        }
        $this->assertSame(count($told), $sent['recipient_count']);
        $notice = Notification::findOrFail($alert->notification_id);
        $this->assertSame('safety.critical', $notice->type);
        $this->assertContains('safety.critical', config('mail.streams.account_types'), 'emergency mail goes on the account stream');
        $this->assertSame('Lockdown', $notice->title);

        // Who triggered it is in the audit trail and the platform audit log.
        $this->assertSame(['alert.prepared', 'alert.confirmed'], EmergencyEvent::query()->where('alert_id', $alert->id)->orderBy('id')->pluck('kind')->all());
        $this->assertSame($this->admin->id, (int) EmergencyEvent::query()->where('kind', 'alert.confirmed')->value('actor_id'));
        $audit = AuditLog::query()->where('action', 'emergency.alert_sent')->sole();
        $this->assertSame($this->admin->id, $audit->after['confirmed_by']);
        $this->assertSame($this->admin->id, $audit->after['prepared_by']);
        $this->assertSame($alert->id, $audit->after['alert_id']);

        // Only one alert can be active; the all-clear goes to the same people.
        $this->assertSame(409, $this->refused(fn () => $this->send()));
        $clear = app(EmergencyAlerts::class)->allClear($this->admin, $alert->id, $this->op(['revision' => $sent['revision']]));
        $this->assertSame('all_clear', $clear['status']);
        $this->deliver();
        $alert->refresh();
        $this->assertSame($told, $this->told((int) $alert->all_clear_notification_id));
        $this->assertStringStartsWith('All clear', Notification::findOrFail($alert->all_clear_notification_id)->title);
        $this->assertSame(409, $this->refused(fn () => app(EmergencyAlerts::class)->allClear($this->admin, $alert->id, $this->op(['revision' => $clear['revision']]))));

        // A staff-only alert never reaches families.
        $staff = $this->send(['audience' => 'staff', 'kind' => 'shelter']);
        $this->deliver();
        $staffTold = $this->told((int) EmergencyAlert::findOrFail($staff['id'])->notification_id);
        $this->assertContains($this->teacher->id, $staffTold);
        $this->assertNotContains($this->farid->id, $staffTold);
    }

    public function test_an_ordinary_teacher_cannot_trigger_or_clear_a_lockdown(): void
    {
        foreach ([$this->teacher, $this->farid, $this->asha] as $actor) {
            $this->assertSame(403, $this->refused(fn () => $this->prepare(actor: $actor)));
        }
        $pending = $this->prepare();
        $this->assertSame(403, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->teacher, $pending['id'], $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']))));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyAlerts::class)->cancel($this->teacher, $pending['id'], $this->op(['revision' => 1]))));
        $this->assertSame('pending', EmergencyAlert::findOrFail($pending['id'])->status);
        $this->api($this->schoolA->tokens['tara'])->postJson('/api/v2/emergency/alerts', $this->op(['kind' => 'lockdown', 'audience' => 'everyone', 'drill' => false]))->assertForbidden();
        $this->api($this->schoolA->tokens['tara'])->postJson('/api/v2/emergency/alerts/'.$pending['id'].'/confirm', $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']))->assertForbidden();
        $this->assertSame(0, Notification::query()->where('type', EmergencyAlerts::TYPE)->count());

        // The panel shows a teacher no alert form, and refuses the write.
        $this->panel($this->teacher)->get('/emergency')->assertOk()->assertDontSee(__('Prepare the alert'));
        $this->panel($this->teacher)->post('/emergency/alert-prepare', $this->op(['expected_account_id' => $this->teacher->id, 'expected_school_id' => 1, 'kind' => 'lockdown', 'audience' => 'everyone', 'drill' => '0']))->assertForbidden();
        $this->panel($this->farid)->get('/emergency')->assertForbidden();

        $active = app(EmergencyAlerts::class)->confirm($this->admin, $pending['id'], $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyAlerts::class)->allClear($this->teacher, $active['id'], $this->op(['revision' => $active['revision']]))));
        $this->assertSame('active', EmergencyAlert::findOrFail($active['id'])->status);

        // A prepared alert left unconfirmed for over ten minutes must be prepared again.
        $stale = $this->prepare(['kind' => 'evacuation']);
        $this->travel(11)->minutes();
        $this->assertSame(409, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->admin, $stale['id'], $this->op(['revision' => 1, 'confirmation' => 'EVACUATE']))));

        // With the module switched off nobody can send one.
        config(['modules.emergency' => ['default' => false]]);
        $this->assertSame(404, $this->refused(fn () => $this->prepare()));
    }

    public function test_a_school_b_alert_never_reaches_school_a_people(): void
    {
        [$alertB, $noticeB] = app(CurrentSchool::class)->run(2, function () {
            $office = User::create(['first_name' => 'Baobab', 'last_name' => 'Office', 'email' => 'baobab-emergency-office@example.test', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $office->assignRole('Super Admin');
            $sent = $this->send(actor: $office);

            return [$sent, (int) EmergencyAlert::findOrFail($sent['id'])->notification_id];
        });
        $this->deliver();
        $told = app(CurrentSchool::class)->run(2, fn () => UserNotification::query()->where('notification_id', $noticeB)->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $this->assertNotEmpty($told);
        $schoolA = SchoolMembership::query()->where('school_id', 1)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([], array_values(array_intersect($told, $schoolA)), 'no School A person is told of a School B alert');
        $this->assertSame(0, DB::table('notification_dispatches')->where('notification_id', $noticeB)->whereIn('user_id', $schoolA)->count());
        $this->assertSame(0, DB::table('user_notifications')->where('notification_id', $noticeB)->where('school_id', 1)->count());
        $this->assertSame(0, DB::table('notification_dispatches')->where('notification_id', $noticeB)->where('school_id', '!=', 2)->count());

        // School A can neither see nor act on it.
        $this->assertSame(404, $this->refused(fn () => app(EmergencyAlerts::class)->allClear($this->admin, $alertB['id'], $this->op(['revision' => $alertB['revision']]))));
        $this->assertSame(404, $this->refused(fn () => app(EmergencyReunification::class)->rollCall($this->admin, $alertB['id'])));
        $this->assertNull(app(EmergencyQueries::class)->status($this->farid)['alert']);
        $this->api($this->schoolA->tokens['farid'])->getJson('/api/v2/emergency/status')->assertOk()->assertJsonPath('data.alert', null)->assertDontSee(self::SCHOOL_B_MARKER);
        $this->api($this->schoolA->tokens['tara'])->getJson('/api/v2/emergency/alerts/'.$alertB['id'].'/roll-call')->assertNotFound();
        $this->panel($this->admin)->get('/emergency')->assertOk()->assertDontSee(self::SCHOOL_B_MARKER)->assertSee(__('There is no alert now.'));
    }

    public function test_a_guardian_sees_only_their_own_childrens_status(): void
    {
        $alert = $this->send();
        app(EmergencyReunification::class)->mark($this->admin, $alert['id'], $this->op(['learner_ids' => [$this->schoolA->ids['ben']], 'status' => 'accounted']));

        $gina = app(EmergencyQueries::class)->status($this->gina);
        $this->assertSame([$this->schoolA->ids['ben']], array_column($gina['children'], 'learner_id'));
        $this->assertSame('accounted', $gina['children'][0]['status']);
        $this->assertSame(404, $this->refused(fn () => app(EmergencyQueries::class)->status($this->gina, $this->schoolA->ids['asha'])));
        $this->api($this->schoolA->tokens['gina'])->getJson('/api/v2/emergency/status?learner_id='.$this->schoolA->ids['asha'])->assertNotFound();
        $this->api($this->schoolA->tokens['gina'])->getJson('/api/v2/emergency/status')->assertOk()
            ->assertJsonCount(1, 'data.children')->assertJsonPath('data.alert.kind', 'lockdown')->assertDontSee('Asha')->assertDontSee('Cara');

        $farid = app(EmergencyQueries::class)->status($this->farid);
        $this->assertEqualsCanonicalizing([$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], array_column($farid['children'], 'learner_id'));
        $this->assertSame(['unaccounted', 'unaccounted'], array_column($farid['children'], 'status'));

        app(EmergencyReunification::class)->release($this->admin, $alert['id'], $this->op(['learner_id' => $this->schoolA->ids['asha'], 'released_to' => 'guardian',
            'guardian_user_id' => $this->farid->id, 'verification' => 'id_document', 'identity_checked' => true]));
        $mona = app(EmergencyQueries::class)->status(User::findOrFail($this->schoolA->ids['mona_user']));
        $this->assertSame('released', $mona['children'][0]['status']);
        $this->assertSame($this->farid->full_name, $mona['children'][0]['released_to_name']);
        $this->assertSame([$this->schoolA->ids['ben']], array_column(app(EmergencyQueries::class)->status($this->gina)['children'], 'learner_id'));

        // Learners and School B guardians are refused.
        $this->api($this->schoolA->tokens['asha'])->getJson('/api/v2/emergency/status')->assertForbidden();
        $schoolB = $this->api($this->schoolB->tokens['farid'])->withHeader('X-School-Id', '2')->getJson('/api/v2/emergency/status')->assertOk()->assertJsonPath('data.alert', null);
        $this->assertEqualsCanonicalizing([$this->schoolB->ids['asha'], $this->schoolB->ids['cara']], array_column($schoolB->json('data.children'), 'learner_id'));
    }

    public function test_a_release_to_someone_not_authorised_is_refused(): void
    {
        $alert = $this->send();
        $asha = $this->schoolA->ids['asha'];
        $release = fn (array $input) => app(EmergencyReunification::class)->release($this->admin, $alert['id'], $this->op($input + ['learner_id' => $asha, 'identity_checked' => true]));

        // Ben's guardian is not Asha's.
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'guardian', 'guardian_user_id' => $this->gina->id, 'verification' => 'id_document'])));
        // A teacher is not a guardian.
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'guardian', 'guardian_user_id' => $this->teacher->id, 'verification' => 'id_document'])));
        // A School B guardian with the same name is not.
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'guardian', 'guardian_user_id' => $this->schoolB->ids['farid_user'], 'verification' => 'id_document'])));
        // Gina's card cannot vouch for Farid.
        $scan = $this->guardianCardScan($this->gina);
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'guardian', 'guardian_user_id' => $this->farid->id, 'verification' => 'card_scan', 'scan_id' => $scan->id])));
        // A collector needs a pass approved for this child today, with the same name.
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'collector', 'gate_pass_id' => (string) Str::uuid(), 'collector_name' => 'Synthetic Stranger', 'verification' => 'id_document'])));
        $pass = GatePass::create(['learner_id' => $asha, 'requested_by' => $this->farid->id, 'approved_by' => $this->admin->id, 'collector' => 'Synthetic Aunt Nomsa',
            'reason' => 'Synthetic', 'starts_at' => now()->utc(), 'ends_at' => now()->addHour()->utc(), 'status' => 'approved', 'revision' => 2]);
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'collector', 'gate_pass_id' => $pass->id, 'collector_name' => 'Synthetic Somebody Else', 'verification' => 'id_document'])));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyReunification::class)->release($this->admin, $alert['id'], $this->op(['learner_id' => $this->schoolA->ids['ben'], 'released_to' => 'collector',
            'gate_pass_id' => $pass->id, 'collector_name' => 'Synthetic Aunt Nomsa', 'verification' => 'id_document', 'identity_checked' => true]))));
        $pending = GatePass::create(['learner_id' => $asha, 'requested_by' => $this->farid->id, 'collector' => 'Synthetic Pending', 'reason' => 'Synthetic',
            'starts_at' => now()->utc(), 'ends_at' => now()->addHour()->utc(), 'status' => 'pending', 'revision' => 1]);
        $this->assertSame(403, $this->refused(fn () => $release(['released_to' => 'collector', 'gate_pass_id' => $pending->id, 'collector_name' => 'Synthetic Pending', 'verification' => 'id_document'])));
        // A teacher may not release, even to the right person.
        $this->assertSame(403, $this->refused(fn () => app(EmergencyReunification::class)->release($this->teacher, $alert['id'], $this->op(['learner_id' => $asha, 'released_to' => 'guardian',
            'guardian_user_id' => $this->farid->id, 'verification' => 'id_document', 'identity_checked' => true]))));
        $this->assertSame(0, EmergencyRelease::query()->count());

        // The named collector, checked by identity document, is released to; the release is logged once.
        $done = $release(['released_to' => 'collector', 'gate_pass_id' => $pass->id, 'collector_name' => ' synthetic aunt  NOMSA ', 'verification' => 'id_document']);
        $this->assertSame('collector', $done['released_to']);
        $this->assertSame(409, $this->refused(fn () => $release(['released_to' => 'guardian', 'guardian_user_id' => $this->farid->id, 'verification' => 'id_document'])));
        $this->assertSame(1, EmergencyRelease::query()->count());
        $this->assertStringNotContainsString('Nomsa', (string) DB::table('emergency_releases')->value('collector_name'), 'the collector name is encrypted');
        $this->assertSame(1, EmergencyEvent::query()->where('kind', 'child.released')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'emergency.child_released')->count());

        // Ben's guardian, checked by the guardian card at the gate reader.
        $card = app(EmergencyReunification::class)->release($this->admin, $alert['id'], $this->op(['learner_id' => $this->schoolA->ids['ben'], 'released_to' => 'guardian',
            'guardian_user_id' => $this->gina->id, 'verification' => 'card_scan', 'scan_id' => $scan->id, 'identity_checked' => true]));
        $this->assertSame('guardian', $card['released_to']);
        $roll = app(EmergencyReunification::class)->rollCall($this->admin, $alert['id']);
        $this->assertSame(2, $roll['totals']['released']);

        // Nobody is released during a drill.
        app(EmergencyAlerts::class)->allClear($this->admin, $alert['id'], $this->op(['revision' => $alert['revision']]));
        $drill = $this->send(['drill' => true]);
        $this->assertSame(409, $this->refused(fn () => app(EmergencyReunification::class)->release($this->admin, $drill['id'], $this->op(['learner_id' => $this->schoolA->ids['cara'],
            'released_to' => 'guardian', 'guardian_user_id' => $this->farid->id, 'verification' => 'id_document', 'identity_checked' => true]))));
    }

    public function test_critical_alerts_bypass_quiet_hours_while_ordinary_notices_still_wait(): void
    {
        $zone = 'Africa/Johannesburg';
        $now = CarbonImmutable::now($zone);
        app(QuietHours::class)->save($this->farid, ['enabled' => true, 'starts_at' => $now->subHour()->format('H:i'), 'ends_at' => $now->addHours(2)->format('H:i'), 'timezone' => $zone]);
        $this->assertNull(app(QuietHours::class)->holdUntil($this->farid->id, 'safety.critical'));
        $this->assertNotNull(app(QuietHours::class)->holdUntil($this->farid->id, 'comms.notice'));
        $this->assertContains('safety.critical', QuietHours::exemptTypes());

        config(['queue.default' => 'database']);
        Settings::query()->updateOrCreate(['type' => 'project_id'], ['message' => 'demo-project']);
        $this->app->bind(FcmClient::class, fn () => new class extends FcmClient
        {
            public function accessToken(): ?string
            {
                return 'test-access-token';
            }
        });
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/0:1'])]);
        $this->farid->forceFill(['fcm_id' => 'synthetic-farid-device', 'device_type' => 'android'])->save();

        $this->send();
        $this->deliver();
        $pushes = Queue::pushed(SendPush::class, fn (SendPush $job) => ($job->message['message']['token'] ?? null) === 'synthetic-farid-device')->all();
        $this->assertCount(1, $pushes);
        $pushes[0]->handle(app(FcmClient::class));
        Http::assertSentCount(1);
        $this->assertCount(1, Queue::pushed(SendPush::class, fn (SendPush $job) => ($job->message['message']['token'] ?? null) === 'synthetic-farid-device'), 'not held for later');

        // An ordinary notice still waits for the end of quiet hours.
        app(Notifier::class)->send($this->farid, 'comms.notice', 'Synthetic newsletter', 'Read it later');
        $ordinary = Queue::pushed(SendPush::class, fn (SendPush $job) => ($job->message['message']['data']['notice_type'] ?? null) === 'comms.notice')->all();
        $this->assertCount(1, $ordinary);
        $ordinary[0]->handle(app(FcmClient::class));
        Http::assertSentCount(1);
        $this->assertCount(2, Queue::pushed(SendPush::class, fn (SendPush $job) => ($job->message['message']['data']['notice_type'] ?? null) === 'comms.notice'), 'held: queued again for later');
    }

    public function test_drills_are_labelled_clearly_and_fill_in_the_drill_log(): void
    {
        $drill = app(EmergencyDrills::class)->plan($this->admin, $this->op(['type' => 'lockdown', 'planned_at' => CarbonImmutable::now('Africa/Johannesburg')->format('Y-m-d\TH:i'), 'notes' => 'Term drill']));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyDrills::class)->plan($this->teacher, $this->op(['type' => 'fire', 'planned_at' => '2026-12-01T10:00']))));
        $alert = $this->prepare(['drill' => true, 'drill_id' => $drill['id'], 'title' => 'Lockdown practice', 'message' => 'Close doors and wait.']);
        $this->assertSame('DRILL', $alert['confirmation_word']);
        $this->assertSame(422, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']))));
        $sent = app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], $this->op(['revision' => 1, 'confirmation' => 'drill']));
        $notice = Notification::findOrFail(EmergencyAlert::findOrFail($sent['id'])->notification_id);
        $this->assertSame('DRILL: Lockdown practice', $notice->title);
        $this->assertStringStartsWith('This is a drill. There is no emergency.', $notice->message);
        $this->assertStringStartsWith('This is a drill. There is no emergency. Close doors and wait.', (string) $notice->body);
        $this->assertSame('1', $notice->delivery_payload['drill']);
        $text = app(UrgentMessaging::class)->content('sms', 'safety.critical', $notice, $notice->title, (string) $notice->message);
        $this->assertStringContainsString('DRILL: Lockdown practice', $text['body'], 'a text says it is a drill too');
        $this->assertStringContainsString('This is a drill. There is no emergency.', $text['body']);
        $status = app(EmergencyQueries::class)->status($this->farid);
        $this->assertTrue($status['alert']['drill']);
        $this->assertSame('DRILL: Lockdown practice', $status['alert']['title']);
        $this->assertNotNull(EmergencyDrill::findOrFail($drill['id'])->started_at);

        // Roll call is practised in a drill.
        $marked = app(EmergencyReunification::class)->mark($this->teacher, $sent['id'], $this->op(['learner_ids' => [$this->schoolA->ids['asha']], 'status' => 'accounted']));
        $this->assertSame([$this->schoolA->ids['asha']], $marked['learner_ids']);

        $this->travel(4)->minutes();
        $clear = app(EmergencyAlerts::class)->allClear($this->admin, $sent['id'], $this->op(['revision' => $sent['revision']]));
        $this->assertStringStartsWith('DRILL: All clear', Notification::findOrFail(EmergencyAlert::findOrFail($clear['id'])->all_clear_notification_id)->title);
        $log = EmergencyDrill::findOrFail($drill['id']);
        $this->assertNotNull($log->ended_at);
        $recorded = app(EmergencyDrills::class)->record($this->admin, $drill['id'], $this->op(['revision' => $log->revision, 'notes' => 'Everyone inside in 3 minutes.']));
        $this->assertSame('completed', $recorded['status']);
        $this->assertSame($sent['id'], $recorded['alert_id']);
        $this->assertSame(422, $this->refused(fn () => app(EmergencyDrills::class)->record($this->admin, $drill['id'], $this->op(['revision' => $recorded['revision'], 'started_at' => '2026-03-01T10:00', 'ended_at' => '2026-03-01T09:00']))));
        // A real alert can never be tied to the drill log.
        $this->assertSame(422, $this->refused(fn () => $this->prepare(['drill' => false, 'drill_id' => $drill['id']])));
    }

    public function test_a_broadcast_is_sent_once_whatever_is_retried(): void
    {
        $alert = $this->prepare();
        $body = $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']);
        $first = app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], $body);
        $again = app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], $body);
        $this->assertSame($first, $again, 'the same operation returns the first receipt');
        $this->assertSame(409, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], $this->op(['revision' => 1, 'confirmation' => 'LOCKDOWN']))));
        $this->assertSame(409, $this->refused(fn () => app(EmergencyAlerts::class)->confirm($this->admin, $alert['id'], array_replace($body, ['confirmation' => 'lockdown']))));
        $this->assertSame(1, Notification::query()->where('type', EmergencyAlerts::TYPE)->count());
        $this->assertSame(1, EmergencyEvent::query()->where('kind', 'alert.confirmed')->count());

        $this->deliver();
        $notice = (int) EmergencyAlert::findOrFail($first['id'])->notification_id;
        $count = UserNotification::query()->where('notification_id', $notice)->count();
        // A retried fanout never tells anyone twice.
        DB::table('domain_events')->update(['dispatched_at' => null, 'attempts' => 0]);
        $this->deliver();
        $this->assertSame($count, UserNotification::query()->where('notification_id', $notice)->count());
        $this->assertSame(UserNotification::query()->where('notification_id', $notice)->distinct()->count('user_id'), $count);

        // The API is idempotent in the same way.
        $prepared = $this->api($this->admin->createToken('test')->plainTextToken);
        $clearBody = $this->op(['revision' => $first['revision']]);
        $one = $prepared->postJson('/api/v2/emergency/alerts/'.$first['id'].'/all-clear', $clearBody)->assertOk()->json('data');
        $two = $prepared->postJson('/api/v2/emergency/alerts/'.$first['id'].'/all-clear', $clearBody)->assertOk()->json('data');
        $this->assertSame($one, $two);
        $this->assertSame(1, EmergencyEvent::query()->where('kind', 'alert.all_clear')->count());
    }

    public function test_roll_call_shows_each_class_and_teachers_mark_only_their_own(): void
    {
        $alert = $this->send();
        $teacherView = app(EmergencyReunification::class)->rollCall($this->teacher, $alert['id']);
        $this->assertSame([$this->schoolA->ids['section_a']], array_column($teacherView['classes'], 'class_section_id'));
        $this->assertSame(2, $teacherView['classes'][0]['unaccounted']);
        $this->assertNull($teacherView['visitors_on_site']);
        $this->assertSame(404, $this->refused(fn () => app(EmergencyReunification::class)->mark($this->teacher, $alert['id'], $this->op(['learner_ids' => [$this->schoolA->ids['cara']], 'status' => 'accounted']))));
        $this->assertSame(404, $this->refused(fn () => app(EmergencyReunification::class)->rollCall($this->teacher, $alert['id'], $this->schoolA->ids['section_c'])));
        app(EmergencyReunification::class)->mark($this->teacher, $alert['id'], $this->op(['learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['ben']], 'status' => 'accounted']));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyReunification::class)->rollCall($this->farid, $alert['id'])));

        $office = app(EmergencyReunification::class)->rollCall($this->admin, $alert['id']);
        $this->assertCount(2, $office['classes']);
        $this->assertSame(['accounted' => 2, 'unaccounted' => 1, 'released' => 0], $office['totals']);
        $this->api($this->schoolA->tokens['tara'])->getJson('/api/v2/emergency/alerts/'.$alert['id'].'/roll-call')->assertOk()->assertJsonCount(1, 'data.classes');
        $this->api($this->schoolA->tokens['tara'])->postJson('/api/v2/emergency/alerts/'.$alert['id'].'/roll-call', $this->op(['learner_ids' => [$this->schoolA->ids['asha']], 'status' => 'unaccounted']))
            ->assertOk()->assertJsonPath('data.learner_ids', [$this->schoolA->ids['asha']]);
        $this->panel($this->admin)->get('/emergency')->assertOk()->assertSee(__('Roll call'))->assertSee(__('Release a child'));
    }

    public function test_visitors_sign_in_by_card_or_by_hand_and_their_details_are_erased_after_retention(): void
    {
        $manual = app(EmergencyVisitors::class)->signIn($this->admin, $this->op(['method' => 'manual', 'name' => 'Synthetic Plumber Visitor', 'phone' => '0820000000', 'reason' => 'Fix a tap', 'host' => 'Office']));
        $scan = $this->guardianCardScan($this->gina);
        $card = app(EmergencyVisitors::class)->signIn($this->admin, $this->op(['method' => 'card', 'scan_id' => $scan->id, 'reason' => 'Meeting']));
        $this->assertSame($this->gina->full_name, $card['name']);
        $this->assertSame(409, $this->refused(fn () => app(EmergencyVisitors::class)->signIn($this->admin, $this->op(['method' => 'card', 'scan_id' => $scan->id, 'reason' => 'Again']))));
        $this->assertSame(403, $this->refused(fn () => app(EmergencyVisitors::class)->signIn($this->teacher, $this->op(['method' => 'manual', 'name' => 'Synthetic', 'reason' => 'Visit']))));
        $this->assertStringNotContainsString('Plumber', (string) DB::table('emergency_visitors')->where('id', $manual['id'])->value('name'), 'visitor details are encrypted');
        $this->assertSame([$manual['id'], $card['id']], array_column(app(EmergencyVisitors::class)->onSite(), 'id'));
        app(EmergencyVisitors::class)->signOut($this->admin, $card['id'], $this->op(['revision' => 1]));
        $this->assertSame([$manual['id']], array_column(app(EmergencyVisitors::class)->onSite(), 'id'));

        // Without the cards module only manual entry works.
        config(['modules.cards' => ['default' => false]]);
        $this->assertSame(422, $this->refused(fn () => app(EmergencyVisitors::class)->signIn($this->admin, $this->op(['method' => 'card', 'scan_id' => $this->guardianCardScanId(), 'reason' => 'Meeting']))));
        config(['modules.cards' => ['always_on' => true]]);

        // School B never sees School A's visitors.
        $this->assertSame([], app(CurrentSchool::class)->run(2, fn () => app(EmergencyVisitors::class)->onSite()));

        // The school chooses how long details are kept; after that they are erased.
        $this->assertSame(403, $this->refused(fn () => app(EmergencyVisitors::class)->changeRetention($this->teacher, $this->op(['revision' => 0, 'visitor_retention_days' => 7]))));
        app(EmergencyVisitors::class)->changeRetention($this->admin, $this->op(['revision' => 0, 'visitor_retention_days' => 7]));
        $this->travel(6)->days();
        $this->artisan('emergency:purge-visitors')->assertSuccessful();
        $this->assertSame('Synthetic Plumber Visitor', EmergencyVisitor::findOrFail($manual['id'])->name);
        $this->travel(2)->days();
        $this->artisan('emergency:purge-visitors')->assertSuccessful();
        foreach ([$manual['id'], $card['id']] as $id) {
            $visitor = EmergencyVisitor::findOrFail($id);
            $this->assertNull($visitor->name);
            $this->assertNull($visitor->phone);
            $this->assertNull($visitor->reason);
            $this->assertNull($visitor->user_id);
            $this->assertNotNull($visitor->redacted_at);
        }
        $this->assertSame([], app(EmergencyVisitors::class)->onSite());
    }

    private function guardianCardScanId(): string
    {
        return (string) Str::uuid();
    }

    public function test_the_panel_sends_an_alert_in_two_steps(): void
    {
        $this->panel($this->admin)->get('/emergency')->assertOk()->assertSee(__('Send an emergency alert'))->assertHeader('Cache-Control', 'no-store, private');
        $form = ['expected_account_id' => (string) $this->admin->id, 'expected_school_id' => '1', 'kind' => 'lockdown', 'audience' => 'everyone', 'drill' => '0', 'title' => '', 'message' => ''];
        $this->post('/emergency/alert-prepare', $this->op(['expected_account_id' => (string) $this->teacher->id] + $form))->assertForbidden();
        $this->post('/emergency/alert-prepare', $this->op($form))->assertRedirect('/emergency');
        $alert = EmergencyAlert::query()->sole();
        $this->assertSame('pending', $alert->status);
        $this->get('/emergency')->assertOk()->assertSee(__('Confirm and send'))->assertSee('LOCKDOWN');
        $this->post('/emergency/alert-confirm', $this->op(['expected_account_id' => (string) $this->admin->id, 'expected_school_id' => '1', 'source_id' => $alert->id, 'revision' => '1', 'confirmation' => 'nope']))->assertStatus(422);
        $this->post('/emergency/alert-confirm', $this->op(['expected_account_id' => (string) $this->admin->id, 'expected_school_id' => '1', 'source_id' => $alert->id, 'revision' => '1', 'confirmation' => 'LOCKDOWN']))->assertRedirect('/emergency');
        $this->assertSame('active', $alert->refresh()->status);
        $this->get('/emergency')->assertOk()->assertSee(__('Send the all-clear'))->assertSee(__('Roll call'));
    }
}
