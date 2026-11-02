<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolContact;
use App\Models\User;
use App\Services\Platform\Saas\PlatformIncidents;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The breach and incident register (Action Plan SA.12): HQ records an
 * incident, the schools it affects and when each was told, the Regulator
 * decision and the closure. engineering/specs/platform/hq-incidents.md.
 */
class HqIncidentsTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const SUMMARY = 'A support export was emailed to the wrong school contact address.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        $this->travelTo(CarbonImmutable::parse('2026-10-30 08:00', PlatformIncidents::TIMEZONE));
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-incident-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function open(array $changes = []): int
    {
        $this->post('/saas/incidents', $changes + [
            'title' => 'Export sent to the wrong address', 'category' => 'breach', 'severity' => 'high', 'personal_information' => 'yes',
            'detected_at' => '2026-10-30T07:15', 'occurred_at' => '2026-10-29T16:40', 'summary' => self::SUMMARY, 'schools' => [1],
        ])->assertRedirect()->assertSessionHasNoErrors();

        return (int) DB::table('platform_incidents')->max('id');
    }

    private function revision(int $id): int
    {
        return (int) DB::table('platform_incidents')->where('id', $id)->value('revision');
    }

    private function details(int $id, array $changes = []): array
    {
        return $changes + ['title' => 'Export sent to the wrong address', 'category' => 'breach', 'severity' => 'high', 'status' => 'contained',
            'personal_information' => 'yes', 'occurred_at' => '2026-10-29T16:40', 'summary' => self::SUMMARY, 'revision' => $this->revision($id)];
    }

    /** Every incident endpoint, each with a payload that would succeed for a permitted role, refused with 403. */
    private function refusedEverywhere(int $id): void
    {
        $this->get('/saas/incidents')->assertForbidden();
        $this->get('/saas/incidents/'.$id)->assertForbidden();
        $this->post('/saas/incidents', ['title' => 'Should never be recorded', 'category' => 'security', 'severity' => 'low', 'personal_information' => 'no',
            'detected_at' => '2026-10-30T07:00', 'summary' => 'This incident must never be recorded.', 'schools' => [1]])->assertForbidden();
        $this->put('/saas/incidents/'.$id, $this->details($id, ['title' => 'Should never be changed']))->assertForbidden();
        $this->post('/saas/incidents/'.$id.'/schools', ['school_id' => 2])->assertForbidden();
        $this->put('/saas/incidents/'.$id.'/schools/1', ['notified_on' => '2026-10-30', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertForbidden();
        $this->post('/saas/incidents/'.$id.'/entries', ['kind' => 'note', 'body' => 'Should never be added'])->assertForbidden();
    }

    public function test_support_records_an_incident_tells_the_school_and_closes_it(): void
    {
        $schoolA = (string) DB::table('schools')->where('id', 1)->value('name');
        app(CurrentSchool::class)->run(1, fn () => SchoolContact::query()->create(['role' => 'information_officer', 'name' => 'Thandi Mokoena', 'email' => 'io@jacaranda.example']));
        $this->actingAsHq($this->staff('support'));

        $id = $this->open();
        $this->assertSame('INC-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT), PlatformIncidents::reference($id));
        $this->get('/saas/incidents')->assertOk()->assertSee(PlatformIncidents::reference($id))->assertSee('One school not told')->assertSee('Regulator decision not recorded');
        $this->get('/saas/incidents/'.$id)->assertOk()->assertSee($schoolA)->assertSee('Thandi Mokoena')->assertSee('Not told yet')
            ->assertSee('Record whether the Information Regulator must be told.')->assertDontSee(self::SCHOOL_B_MARKER.' High</th>', false);
        // Detected 07:15 South African time is stored in the app's timezone.
        $this->assertSame(CarbonImmutable::parse('2026-10-30 07:15', PlatformIncidents::TIMEZONE)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            (string) DB::table('platform_incidents')->where('id', $id)->value('detected_at'));

        // It cannot close while the school is untold and the Regulator decision is open.
        $this->put('/saas/incidents/'.$id, $this->details($id, ['status' => 'closed', 'closure_summary' => 'Address corrected and the export deleted by the recipient.']))
            ->assertSessionHasErrors('status');
        $this->assertSame('open', DB::table('platform_incidents')->where('id', $id)->value('status'));

        $this->put('/saas/incidents/'.$id.'/schools/1', ['notified_on' => '2026-10-30', 'notified_how' => 'phone', 'notified_contact' => 'Information Officer'])
            ->assertRedirect('/saas/incidents/'.$id)->assertSessionHas('success');
        $this->put('/saas/incidents/'.$id, $this->details($id, ['regulator' => 'required', 'regulator_reason' => 'Learner contact details were disclosed']))
            ->assertRedirect('/saas/incidents/'.$id)->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('platform_incidents')->where('id', $id)->value('contained_at'));
        // A required notification needs its date before closing.
        $this->put('/saas/incidents/'.$id, $this->details($id, ['status' => 'closed', 'regulator' => 'required', 'regulator_reason' => 'Learner contact details were disclosed',
            'closure_summary' => 'Address corrected and the export deleted by the recipient.']))->assertSessionHasErrors('status');
        $this->put('/saas/incidents/'.$id, $this->details($id, ['status' => 'closed', 'regulator' => 'required', 'regulator_reason' => 'Learner contact details were disclosed',
            'regulator_notified_on' => '2026-10-30', 'regulator_reference' => 'IR-2026-77', 'closure_summary' => 'Address corrected and the export deleted by the recipient.']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $row = DB::table('platform_incidents')->where('id', $id)->first();
        $this->assertSame(['closed', 'required'], [$row->status, $row->regulator]);
        $this->assertNotNull($row->closed_at);
        $this->post('/saas/incidents/'.$id.'/entries', ['kind' => 'note', 'body' => 'Follow-up: export screen now asks to confirm the address.'])->assertRedirect();
        $this->get('/saas/incidents/'.$id)->assertOk()->assertSee('Status changed from Contained to Closed.')->assertSee('Information Regulator told on 2026-10-30 (reference IR-2026-77).')
            ->assertSee($schoolA.' told on 2026-10-30 by phone call (Information Officer).')->assertSee('Follow-up: export screen');
        $this->get('/saas/incidents')->assertOk()->assertSee('Recently closed')->assertSee('No open incidents.');

        // The audit log records ids and states, never the free text, and names the school it was told about.
        $audit = AuditLog::query()->where('action', 'like', 'platform_incident%')->get();
        $this->assertSame(['platform_incident_entry_added', 'platform_incident_opened', 'platform_incident_school_notified', 'platform_incident_updated'], $audit->pluck('action')->unique()->sort()->values()->all());
        $this->assertStringNotContainsString('wrong school contact', (string) $audit->toJson());
        $this->assertStringNotContainsString('Learner contact details', (string) $audit->toJson());
        $this->assertSame(1, (int) $audit->firstWhere('action', 'platform_incident_school_notified')->school_id);
    }

    public function test_a_closed_incident_is_reopened_before_it_changes_and_stale_forms_are_refused(): void
    {
        $this->actingAsHq($this->staff('operations'));
        $id = $this->open(['category' => 'availability', 'personal_information' => 'no', 'schools' => []]);
        $stale = $this->revision($id);
        $this->post('/saas/incidents/'.$id.'/schools', ['school_id' => 2])->assertRedirect();
        $this->post('/saas/incidents/'.$id.'/schools', ['school_id' => 2])->assertSessionHasErrors('school_id');
        // Adding a school changed the incident: a form opened before it is refused.
        $this->put('/saas/incidents/'.$id, $this->details($id, ['category' => 'availability', 'personal_information' => 'no', 'revision' => $stale]))->assertStatus(409);

        // No personal information: closing needs only the closure summary.
        $this->put('/saas/incidents/'.$id, $this->details($id, ['category' => 'availability', 'personal_information' => 'no', 'status' => 'closed']))->assertSessionHasErrors('closure_summary');
        $this->put('/saas/incidents/'.$id, $this->details($id, ['category' => 'availability', 'personal_information' => 'no', 'status' => 'closed', 'closure_summary' => 'Worker restarted; alert threshold lowered.']))
            ->assertSessionHasNoErrors();
        $this->put('/saas/incidents/'.$id, $this->details($id, ['category' => 'availability', 'personal_information' => 'no', 'status' => 'closed', 'title' => 'Changed after closing', 'closure_summary' => 'Worker restarted; alert threshold lowered.']))->assertStatus(409);
        $this->post('/saas/incidents/'.$id.'/schools', ['school_id' => 1])->assertStatus(409);
        $this->put('/saas/incidents/'.$id.'/schools/2', ['notified_on' => '2026-10-30', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertStatus(409);
        $this->assertSame('Export sent to the wrong address', DB::table('platform_incidents')->where('id', $id)->value('title'));

        $this->put('/saas/incidents/'.$id, $this->details($id, ['category' => 'availability', 'personal_information' => 'no', 'status' => 'open', 'closure_summary' => 'Worker restarted; alert threshold lowered.']))->assertSessionHasNoErrors();
        $this->assertSame([null, null], array_values((array) DB::table('platform_incidents')->where('id', $id)->first(['closed_at', 'contained_at'])));
    }

    public function test_impossible_records_are_refused(): void
    {
        $this->actingAsHq($support = $this->staff('support'));
        $base = ['title' => 'Unusual sign-ins', 'category' => 'security', 'severity' => 'low', 'personal_information' => 'unknown', 'summary' => 'Repeated failed sign-ins from one address.'];
        $this->post('/saas/incidents', $base + ['detected_at' => '2026-10-31T09:00'])->assertSessionHasErrors('detected_at');
        $this->post('/saas/incidents', $base + ['detected_at' => '2026-10-30T07:00', 'occurred_at' => '2026-10-30T07:30'])->assertSessionHasErrors('occurred_at');
        $this->post('/saas/incidents', ['category' => 'breach', 'personal_information' => 'no', 'detected_at' => '2026-10-30T07:00'] + $base)->assertSessionHasErrors('personal_information');
        $this->post('/saas/incidents', $base + ['detected_at' => '2026-10-30T07:00', 'schools' => [999]])->assertSessionHasErrors('schools.0');
        $this->assertSame(0, DB::table('platform_incidents')->count());

        $id = $this->open();
        $this->put('/saas/incidents/'.$id.'/schools/1', ['notified_on' => '2026-10-31', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertSessionHasErrors('notified_on');
        $this->put('/saas/incidents/'.$id.'/schools/1', ['notified_on' => '2026-10-29', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertSessionHasErrors('notified_on');
        $this->put('/saas/incidents/'.$id, $this->details($id, ['regulator_notified_on' => '2026-10-30']))->assertSessionHasErrors('regulator_notified_on');
        $this->assertNull(DB::table('platform_incident_schools')->where('incident_id', $id)->value('notified_on'));

        try {
            app(PlatformIncidents::class)->open($base + ['detected_at' => '2026-10-30 07:00'], $support);
            $this->fail('The service accepted a malformed time');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('detected_at', $e->errors());
        }
    }

    public function test_only_roles_with_the_register_and_never_a_school_reach_it(): void
    {
        $this->actingAsHq($this->staff('support'));
        $id = $this->open();
        $before = [DB::table('platform_incidents')->count(), DB::table('platform_incident_schools')->count(), DB::table('platform_incident_entries')->count(), $this->revision($id)];

        foreach (['sales', 'finance', 'content'] as $role) {
            $this->actingAsHq($this->staff($role));
            $this->get('/saas')->assertOk()->assertDontSee('Incidents');
            $this->refusedEverywhere($id);
        }

        // A school's own Super Admin and teachers never reach HQ's register, on any endpoint.
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->schoolA->ids['tara_user'])] as $schoolUser) {
            $this->newBrowserSession()->actingAs($schoolUser);
            $this->refusedEverywhere($id);
        }

        // A school not listed on the incident cannot be marked as told through it.
        $this->actingAsHq($this->staff('owner'));
        $this->get('/saas')->assertSee('Incidents');
        $this->put('/saas/incidents/'.$id.'/schools/2', ['notified_on' => '2026-10-30', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertNotFound();
        $this->put('/saas/incidents/999/schools/1', ['notified_on' => '2026-10-30', 'notified_how' => 'email', 'notified_contact' => 'Principal'])->assertNotFound();
        $this->get('/saas/incidents/999')->assertNotFound();

        $this->assertSame($before, [DB::table('platform_incidents')->count(), DB::table('platform_incident_schools')->count(), DB::table('platform_incident_entries')->count(), $this->revision($id)]);
        $this->assertSame(0, DB::table('platform_incident_schools')->where('school_id', 2)->count());
        $this->assertNull(DB::table('platform_incident_schools')->where('incident_id', $id)->value('notified_on'));
    }

    public function test_the_whole_timeline_stays_reachable_a_page_at_a_time(): void
    {
        $this->actingAsHq($this->staff('support'));
        $id = $this->open();
        $rows = [];
        for ($i = 1; $i <= PlatformIncidents::TIMELINE_PER_PAGE; $i++) {
            $rows[] = ['incident_id' => $id, 'kind' => 'note', 'body' => 'Synthetic step '.$i, 'actor_id' => null, 'created_at' => now()->addMinutes($i)];
        }
        DB::table('platform_incident_entries')->insert($rows);

        $this->get('/saas/incidents/'.$id)->assertOk()->assertSee('Synthetic step '.PlatformIncidents::TIMELINE_PER_PAGE)
            ->assertDontSee('Incident recorded: High')->assertSee('Older entries')->assertSee('Page 1 of 2');
        $this->get('/saas/incidents/'.$id.'?timeline=2')->assertOk()->assertSee('Incident recorded: High, one affected school.')
            ->assertSee('Newer entries')->assertDontSee('Synthetic step '.PlatformIncidents::TIMELINE_PER_PAGE.'<');
    }
}
