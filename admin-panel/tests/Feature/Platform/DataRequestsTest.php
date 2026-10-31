<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Models\Platform\AuditLog;
use App\Models\Platform\DataRequest;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Privacy\DataRequests;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * POPIA data subject requests that reach Navigate Ed (Action Plan SA.11):
 * HQ logs and passes them, the school's Super Admin answers, HQ closes.
 * engineering/specs/platform/data-requests.md.
 */
class DataRequestsTest extends TestCase
{
    use UsesTwoSchools;

    private const REQUESTER = 'Nomsa Requester';

    private const DETAILS = 'Asks for a copy of everything the school holds about her son.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        // The demo memberships start on the day the fixture is built; the clock below is pinned,
        // so they must have started before it whatever day the suite runs.
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
        $this->travelTo(CarbonImmutable::parse('2026-11-02 08:00', DataRequests::TIMEZONE));
    }

    private function hq(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->firstOrCreate(['email' => $role.'@platform.example'], ['first_name' => ucfirst($role), 'last_name' => 'Operator', 'mobile' => '', 'password' => 'Synthetic-request-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->firstOrCreate(['user_id' => $user->id]);
        PlatformAdminGrant::query()->updateOrCreate(['user_id' => $user->id], ['active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** School A's Super Admin, with a current membership so Navigate Ed can tell them. */
    private function officerA(): User
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->acrossSchools(fn () => SchoolMembership::query()->firstOrCreate(['school_id' => 1, 'user_id' => $admin->id, 'role' => 'Super Admin'], ['status' => 'active', 'starts_on' => '2026-01-01']));

        return $admin;
    }

    private function officerB(): User
    {
        return app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): User {
            $user = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal@baobab.example', 'mobile' => '', 'password' => 'Synthetic-request-password-582!', 'status' => 1]);
            SchoolMembership::query()->create(['school_id' => self::SCHOOL_B_ID, 'user_id' => $user->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
            $user->assignRole('Super Admin');

            return $user;
        });
    }

    private function log(int $schoolId = 1, array $changes = []): int
    {
        $this->post('/saas/data-requests', $changes + [
            'school_id' => $schoolId, 'kind' => 'access', 'channel' => 'email', 'requester_role' => 'parent',
            'requester_name' => self::REQUESTER, 'requester_contact' => 'nomsa@example.com', 'details' => self::DETAILS, 'received_on' => '2026-11-01',
        ])->assertRedirect()->assertSessionHasNoErrors();

        return (int) app(CurrentSchool::class)->acrossSchools(fn () => DataRequest::acrossSchools()->max('id'));
    }

    private function row(int $id): DataRequest
    {
        return app(CurrentSchool::class)->acrossSchools(fn () => DataRequest::acrossSchools()->findOrFail($id));
    }

    public function test_hq_logs_and_passes_a_request_the_school_answers_and_hq_closes_it(): void
    {
        $officer = $this->officerA();
        $this->actingAs($this->hq('operations'));
        $id = $this->log();

        $row = $this->row($id);
        $this->assertSame('received', $row->status);
        $this->assertSame('2026-12-01', $row->due_on->toDateString());
        // Encrypted at rest: the raw column never holds the name or the request.
        $raw = DB::table('platform_data_requests')->where('id', $id)->first();
        $this->assertStringNotContainsString(self::REQUESTER, (string) $raw->requester_name);
        $this->assertStringNotContainsString('son', (string) $raw->details);
        $this->get('/saas/data-requests')->assertOk()->assertSee('DR-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT))->assertSee('Jacaranda')->assertDontSee(self::REQUESTER);
        $this->get('/saas/data-requests/'.$id)->assertOk()->assertSee(self::REQUESTER)->assertSee(self::DETAILS);

        // Not passed yet: the school does not see it.
        $this->actingAs($officer)->get('/data-requests')->assertOk()->assertDontSee(self::REQUESTER);

        $this->actingAs($this->hq('support'))->post('/saas/data-requests/'.$id.'/pass')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('with_school', $this->row($id)->status);
        $notice = DB::table('notifications')->where('school_id', 1)->where('type', 'school.data_request')->sole();
        $this->assertStringNotContainsString('Nomsa', $notice->title.' '.$notice->message);
        $intent = OutboxEvent::query()->where('school_id', 1)->where('type', 'notification.requested')->latest('id')->firstOrFail();
        $this->assertSame(['notification_id' => (int) $notice->id, 'user_ids' => [(int) $officer->id]], $intent->payload);
        $this->post('/saas/data-requests/'.$id.'/pass')->assertStatus(409);

        $this->actingAs($officer)->get('/data-requests')->assertOk()->assertSee(self::REQUESTER)->assertSee(self::DETAILS)->assertSee('Record your answer');
        $this->post('/data-requests/'.$id.'/answer', ['outcome' => 'fulfilled', 'note' => 'Sent the full record to the parent on 3 November.'])->assertRedirect('/data-requests')->assertSessionHasNoErrors();
        $this->assertSame('answered', $this->row($id)->status);
        $this->post('/data-requests/'.$id.'/answer', ['outcome' => 'refused', 'note' => 'A second answer must not replace the first.'])->assertStatus(409);
        $this->assertSame('fulfilled', $this->row($id)->outcome);

        $this->actingAs($this->hq('operations'))->get('/saas/data-requests/'.$id)->assertSee('Sent the full record to the parent on 3 November.');
        $this->post('/saas/data-requests/'.$id.'/close', ['note' => 'Checked with the school; nothing more for Navigate Ed.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('closed', $this->row($id)->status);
        $this->post('/saas/data-requests/'.$id.'/withdraw', ['note' => 'Too late to withdraw.'])->assertStatus(409);

        $audit = AuditLog::query()->withoutGlobalScopes()->where('action', 'like', 'platform_data_request_%')->orderBy('id')->get();
        $this->assertSame(['platform_data_request_logged', 'platform_data_request_passed', 'platform_data_request_answered', 'platform_data_request_closed'], $audit->pluck('action')->all());
        $this->assertStringNotContainsString('Nomsa', $audit->toJson());
        $this->assertStringNotContainsString('full record', $audit->toJson());
    }

    public function test_the_email_links_to_the_staff_panel_and_in_person_requests_are_kept_whole(): void
    {
        Queue::fake();
        config(['app.url' => 'https://staff.example.test', 'identity.portal_url' => 'https://family.example.test']);
        $officer = $this->officerA();
        $officer->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($this->hq('operations'));
        $id = $this->log(1, ['channel' => 'in_person']);
        $this->assertSame('in_person', $this->row($id)->channel);
        $this->get('/saas/data-requests/'.$id)->assertOk()->assertSee('In person');

        app(CurrentSchool::class)->run(1, function () use ($officer): void {
            $notifier = app(Notifier::class);
            $notice = $notifier->makeNotice('school.data_request', 'A data request needs your answer', 'Please answer it.', '/data-requests');
            $notifier->send($officer, 'school.data_request', $notice->title, $notice->message, '/data-requests', $notice, ['email']);
        });
        Queue::assertPushed(SendMail::class, fn ($job) => $job->mailable->noticeUrl === 'https://staff.example.test/data-requests');
    }

    public function test_roles_without_the_ability_are_refused_and_nothing_changes(): void
    {
        $this->officerA();
        $this->actingAs($this->hq('operations'));
        $id = $this->log();
        $before = DataRequest::acrossSchools()->count();

        $this->actingAs($this->hq('sales'));
        $this->get('/saas/data-requests')->assertForbidden();
        $this->get('/saas/data-requests/'.$id)->assertForbidden();
        $this->post('/saas/data-requests', ['school_id' => 1, 'kind' => 'deletion', 'channel' => 'email', 'requester_role' => 'other', 'requester_name' => 'Never logged',
            'requester_contact' => 'never@example.com', 'details' => 'This must never be logged.', 'received_on' => '2026-11-01'])->assertForbidden();
        $this->post('/saas/data-requests/'.$id.'/pass')->assertForbidden();
        $this->post('/saas/data-requests/'.$id.'/withdraw', ['note' => 'Should never be withdrawn'])->assertForbidden();
        $this->assertSame($before, DataRequest::acrossSchools()->count());
        $this->assertSame('received', $this->row($id)->status);

        // A school Super Admin is not platform staff.
        $this->actingAs($this->superAdmin())->get('/saas/data-requests')->assertForbidden();
        $this->post('/saas/data-requests/'.$id.'/pass')->assertForbidden();

        $this->actingAs($this->hq('owner'))->post('/saas/data-requests/'.$id.'/pass')->assertRedirect();
        // A teacher at the school cannot see or answer it.
        $this->actingAs(User::query()->findOrFail($this->schoolA->ids['tara_user']));
        $this->get('/data-requests')->assertForbidden();
        $this->post('/data-requests/'.$id.'/answer', ['outcome' => 'refused', 'note' => 'A teacher must not answer this.'])->assertForbidden();
        $this->assertSame('with_school', $this->row($id)->status);
        $this->assertNull($this->row($id)->outcome);
    }

    public function test_a_school_sees_and_answers_only_its_own_requests(): void
    {
        $officerA = $this->officerA();
        $this->actingAs($this->hq('operations'));
        $id = $this->log(self::SCHOOL_B_ID, ['requester_name' => 'Baobab Requester']);

        // School B has no Super Admin yet: nobody to pass it to.
        $this->post('/saas/data-requests/'.$id.'/pass')->assertSessionHasErrors('request');
        $this->assertSame('received', $this->row($id)->status);
        $officerB = $this->officerB();
        $this->post('/saas/data-requests/'.$id.'/pass')->assertSessionHasNoErrors();
        $this->assertSame([], DB::table('notifications')->where('school_id', 1)->where('type', 'school.data_request')->pluck('id')->all());

        $this->actingAs($officerA)->get('/data-requests')->assertOk()->assertDontSee('Baobab Requester')->assertSee('No data requests');
        $this->post('/data-requests/'.$id.'/answer', ['outcome' => 'fulfilled', 'note' => 'School A must not answer School B.'])->assertNotFound();
        $this->assertSame('with_school', $this->row($id)->status);

        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(DataRequests::class)->answer($officerB, $id, ['outcome' => 'partly', 'note' => 'Sent the marks; the counselling notes are withheld.']));
        $this->assertSame('partly', $this->row($id)->outcome);
        $this->assertSame((int) $officerB->id, (int) $this->row($id)->answered_by);
    }

    public function test_dates_are_checked_and_overdue_requests_stand_out(): void
    {
        $this->officerA();
        $this->actingAs($this->hq('operations'));
        $base = ['school_id' => 1, 'kind' => 'correction', 'channel' => 'phone', 'requester_role' => 'learner', 'requester_name' => self::REQUESTER,
            'requester_contact' => '082 000 0000', 'details' => 'The surname is spelt wrongly on reports.'];
        $count = DataRequest::acrossSchools()->count();
        $this->post('/saas/data-requests', $base + ['received_on' => '2026-11-03'])->assertSessionHasErrors('received_on');
        $this->post('/saas/data-requests', $base + ['received_on' => '2026-11-01', 'due_on' => '2027-03-01'])->assertSessionHasErrors('due_on');
        $this->post('/saas/data-requests', $base + ['received_on' => '2026-11-01', 'due_on' => '2026-10-30'])->assertSessionHasErrors('due_on');
        $this->post('/saas/data-requests', ['school_id' => 999, 'received_on' => '2026-11-01'] + $base)->assertSessionHasErrors('school_id');
        $this->assertSame($count, DataRequest::acrossSchools()->count());

        $id = $this->log(1, ['due_on' => '2026-11-10']);
        $this->get('/saas/data-requests')->assertSee('8 days left')->assertDontSee('Overdue');
        $this->travelTo(CarbonImmutable::parse('2026-11-12 08:00', DataRequests::TIMEZONE));
        // HQ grants expire within hours, so a fresh one ten days later.
        $this->actingAs($this->hq('owner'))->get('/saas/data-requests')->assertSee('Overdue');
        $this->post('/saas/data-requests/'.$id.'/withdraw', ['note' => 'The learner phoned to withdraw it.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $this->row($id)->status);
        $this->get('/saas/data-requests')->assertSee('Recently closed')->assertDontSee('Overdue');
    }
}
