<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Notification;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\SupportTicket;
use App\Models\Platform\SupportTicketMessage;
use App\Models\User;
use App\Services\Panel\Navigation;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Saas\PlatformSupportDesk;
use App\Services\Platform\Support\SchoolSupportTickets;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The support desk (Action Plan SA.17): a school's office opens a ticket to
 * Navigate Ed, HQ answers it from /saas. engineering/specs/platform/support-desk.md.
 */
class SupportDeskTest extends TestCase
{
    use UsesTwoSchools;

    private const BODY = 'The timetable page shows an empty grid since this morning for Grade 7.';

    private const REPLY = 'We fixed the cache that held last term\'s grid; please reload the page.';

    private const NOTE = 'Root cause: stale projection after the bell schedule change.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00', 'Africa/Johannesburg'));
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-support-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function schoolBAdmin(): User
    {
        return app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): User {
            $user = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal.baobab@example.com', 'mobile' => '', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $user->assignRole('Super Admin');
            app(MembershipService::class)->grant($user, self::SCHOOL_B_ID, 'Super Admin');

            return $user;
        });
    }

    /** Signs in as someone else in a fresh browser. */
    private function as(User $user): void
    {
        $this->flushSession();
        app(CurrentSchool::class)->forget();
        $this->actingAs($user);
    }

    /** Opens a ticket as the signed-in school user and returns its id. */
    private function open(array $changes = []): int
    {
        $before = (int) SupportTicket::acrossSchools()->max('id');
        $this->post('/support-tickets', $changes + ['subject' => 'Timetable page is empty', 'category' => 'problem', 'priority' => 'normal', 'body' => self::BODY])
            ->assertRedirect()->assertSessionHasNoErrors();
        $id = (int) SupportTicket::acrossSchools()->max('id');
        $this->assertGreaterThan($before, $id);

        return $id;
    }

    private function ticket(int $id): SupportTicket
    {
        return SupportTicket::acrossSchools()->findOrFail($id);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'tickets' => DB::table('school_support_tickets')->count(),
            'messages' => DB::table('school_support_ticket_messages')->count(),
            'events' => DB::table('school_account_events')->where('kind', 'support')->count(),
            'notices' => DB::table('notifications')->where('type', 'school.support_reply')->count(),
            'audit' => DB::table('audit_log')->where('action', 'like', 'support_ticket_%')->count(),
        ];
    }

    public function test_a_school_opens_a_ticket_hq_answers_it_and_the_school_closes_it(): void
    {
        $admin = $this->superAdmin();
        $this->as($admin);
        $this->get('/support-tickets')->assertOk()->assertSee('Open a ticket')->assertSee('No tickets yet')
            ->assertSee('Do not paste learners\' personal information here');
        $id = $this->open(['priority' => 'urgent']);
        $reference = SupportTicket::referenceFor($id);
        $ticket = $this->ticket($id);
        $this->assertSame([1, 'open', 'urgent', 'problem', (int) $admin->id], [(int) $ticket->school_id, $ticket->status, $ticket->priority, $ticket->category, $ticket->opened_by]);
        // The message is encrypted at rest and read back by the model.
        $raw = (string) DB::table('school_support_ticket_messages')->where('ticket_id', $id)->value('body');
        $this->assertStringNotContainsString('timetable', strtolower($raw));
        $this->assertSame(self::BODY, app(CurrentSchool::class)->run(1, fn () => SupportTicketMessage::query()->where('ticket_id', $id)->sole()->body));
        // HQ's timeline of the school says it opened, without the school's words.
        $line = (string) DB::table('school_account_events')->where('school_id', 1)->where('kind', 'support')->latest('id')->value('body');
        $this->assertSame('Support ticket '.$reference.' opened (problem, urgent).', $line);
        $this->get('/support-tickets/'.$id)->assertOk()->assertSee($reference)->assertSee(self::BODY)->assertSee('With Navigate Ed');
        $this->get('/support-tickets')->assertOk()->assertSee($reference)->assertSee('Timetable page is empty');

        $support = $this->staff('support');
        $this->as($support);
        $this->get('/saas')->assertOk()->assertSee('Support desk');
        $this->get('/saas/support')->assertOk()->assertSee($reference)->assertSee('Timetable page is empty')->assertSee('Jacaranda')->assertSee('Urgent')->assertSee('New');
        $this->get('/saas/support/'.$id)->assertOk()->assertSee(self::BODY)->assertSee('Opened 2026-11-02 09:00 by super admin');

        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => self::REPLY])->assertRedirect('/saas/support/'.$id)->assertSessionHasNoErrors();
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'note', 'body' => self::NOTE])->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/saas/support/'.$id.'/status', ['status' => 'waiting_on_school'])->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => $support->id])->assertRedirect()->assertSessionHasNoErrors();
        $ticket = $this->ticket($id);
        $this->assertSame(['waiting_on_school', (int) $support->id], [$ticket->status, $ticket->assigned_to]);
        $this->get('/saas/support/'.$id)->assertOk()->assertSee(self::REPLY)->assertSee(self::NOTE)->assertSee('Internal note: the school never sees this')->assertSee('Support Operator');
        $this->assertSame(1, DB::table('notifications')->where('type', 'school.support_reply')->where('school_id', 1)->count(), 'only the reply tells the school');

        $this->as($admin);
        $this->get('/support-tickets/'.$id)->assertOk()->assertSee(self::REPLY)->assertSee('Navigate Ed support')->assertSee('Waiting for your reply')
            ->assertDontSee(self::NOTE)->assertDontSee('Support Operator');
        $this->post('/support-tickets/'.$id.'/messages', ['body' => 'Thanks, it works again for Grade 7.'])->assertRedirect('/support-tickets/'.$id)->assertSessionHasNoErrors();
        $this->assertSame('open', $this->ticket($id)->status, 'a school message puts the ticket back in the queue');
        $this->post('/support-tickets/'.$id.'/close')->assertRedirect('/support-tickets/'.$id)->assertSessionHasNoErrors();
        $ticket = $this->ticket($id);
        $this->assertSame(['closed', (int) $admin->id], [$ticket->status, $ticket->closed_by]);
        $this->assertNotNull($ticket->closed_at);
        $this->assertSame('Support ticket '.$reference.' closed by the school.', (string) DB::table('school_account_events')->where('school_id', 1)->where('kind', 'support')->latest('id')->value('body'));
        $this->get('/support-tickets/'.$id)->assertOk()->assertSee('This ticket is closed. Open a new ticket if you still need help.')->assertDontSee('Add a message');

        $actions = DB::table('audit_log')->where('school_id', 1)->where('action', 'like', 'support_ticket_%')->orderBy('id')->pluck('action')->all();
        $this->assertSame(['support_ticket_opened', 'support_ticket_message_added', 'support_ticket_message_added', 'support_ticket_status_changed', 'support_ticket_assigned', 'support_ticket_message_added', 'support_ticket_closed'], $actions);
    }

    public function test_office_staff_with_the_settings_permission_use_it_and_see_it_in_the_menu(): void
    {
        $role = app(CurrentSchool::class)->run(1, function (): Role {
            $role = Role::query()->create(['name' => 'Front office', 'custom_role' => 1, 'guard_name' => 'web']);
            $role->givePermissionTo('setting-create');

            return $role;
        });
        $office = app(CurrentSchool::class)->run(1, function () use ($role): User {
            $user = User::query()->create(['first_name' => 'Olive', 'last_name' => 'Office', 'email' => 'office@jacaranda.example', 'mobile' => '', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $user->assignRole($role);
            app(MembershipService::class)->grant($user, 1, 'Front office');

            return $user;
        });
        $this->as($office);
        $id = $this->open(['category' => 'billing']);
        $this->get('/support-tickets/'.$id)->assertOk()->assertSee(self::BODY);
        $labels = app(CurrentSchool::class)->run(1, fn () => array_column(app(Navigation::class)->items($office->fresh()), 'label'));
        $this->assertContains(__('nav_support_tickets'), $labels);
        $tara = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->assertNotContains(__('nav_support_tickets'), app(CurrentSchool::class)->run(1, fn () => array_column(app(Navigation::class)->items($tara), 'label')));
    }

    public function test_the_queue_is_urgent_first_then_oldest_and_marks_what_changed_since_the_last_look(): void
    {
        $admin = $this->superAdmin();
        $this->as($admin);
        $old = $this->open(['subject' => 'Oldest normal ticket']);
        $this->travel(10)->minutes();
        $urgent = $this->open(['subject' => 'Later urgent ticket', 'priority' => 'urgent']);
        $this->travel(10)->minutes();
        $newer = $this->open(['subject' => 'Newest normal ticket']);
        $this->as($this->schoolBAdmin());
        $this->travel(10)->minutes();
        $baobab = $this->open(['subject' => 'Baobab urgent ticket', 'priority' => 'urgent']);
        $this->post('/support-tickets/'.$baobab.'/close')->assertRedirect();

        $support = $this->staff('support');
        $this->as($support);
        $this->travel(10)->minutes();
        $first = app(PlatformSupportDesk::class)->queue($support);
        $this->assertSame([$urgent, $old, $newer], array_column($first['queue'], 'id'), 'urgent first, then the oldest; a closed ticket leaves the queue');
        $this->assertSame([$baobab], array_column($first['recent'], 'id'));
        $this->assertSame([true, true, true], array_column($first['queue'], 'new'));
        $this->assertNull($first['lastSeen']);

        $this->travel(10)->minutes();
        $this->as($admin);
        $this->post('/support-tickets/'.$old.'/messages', ['body' => 'Any news on this one?'])->assertRedirect();
        $this->travel(1)->minutes();
        $this->as($support);
        $page = $this->get('/saas/support')->assertOk()->assertSee('Marked new: changed since you last opened the queue');
        $second = $page->viewData('queue');
        $this->assertSame([$urgent => false, $old => true, $newer => false], array_column($second, 'new', 'id'));
    }

    public function test_teachers_and_parents_are_refused_and_nothing_is_written(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open();
        $before = $this->counts();
        foreach (['tara_user', 'farid_user'] as $person) {
            $this->as(User::query()->findOrFail($this->schoolA->ids[$person]));
            $this->get('/support-tickets')->assertForbidden();
            $this->get('/support-tickets/'.$id)->assertForbidden();
            $this->post('/support-tickets', ['subject' => 'Should never be sent', 'category' => 'question', 'priority' => 'normal', 'body' => 'This ticket must never be written.'])->assertForbidden();
            $this->post('/support-tickets/'.$id.'/messages', ['body' => 'Should never be added'])->assertForbidden();
            $this->post('/support-tickets/'.$id.'/close')->assertForbidden();
        }
        $this->assertSame($before, $this->counts());
        $this->assertSame('open', $this->ticket($id)->status);
    }

    public function test_sales_finance_content_and_school_accounts_are_refused_on_hq_and_nothing_is_written(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open();
        $before = $this->counts();
        $owner = $this->staff('owner');
        foreach (['sales', 'finance', 'content'] as $role) {
            $this->as($this->staff($role));
            $this->get('/saas')->assertOk()->assertDontSee('Support desk');
            $this->get('/saas/schools/1')->assertOk()->assertSee('One open support ticket.')->assertDontSee('Timetable page is empty')->assertDontSee(SupportTicket::referenceFor($id));
            $this->get('/saas/support')->assertForbidden();
            $this->get('/saas/support/'.$id)->assertForbidden();
            $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => 'Should never be sent'])->assertForbidden();
            $this->put('/saas/support/'.$id.'/status', ['status' => 'closed'])->assertForbidden();
            $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => $owner->id])->assertForbidden();
        }
        $this->as($this->superAdmin());
        $this->get('/saas/support')->assertForbidden();
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => 'Should never be sent'])->assertForbidden();
        $this->assertSame($before, $this->counts());
        $ticket = $this->ticket($id);
        $this->assertSame(['open', null], [$ticket->status, $ticket->assigned_to]);
        $this->assertSame(0, DB::table('platform_support_views')->count(), 'a refused visit records nothing');
    }

    public function test_another_school_cannot_see_reply_to_or_close_a_ticket(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open(['subject' => 'Jacaranda private subject']);
        $before = $this->ticket($id)->getAttributes();
        $counts = $this->counts();

        $this->as($this->schoolBAdmin());
        $this->get('/support-tickets')->assertOk()->assertDontSee('Jacaranda private subject')->assertDontSee(SupportTicket::referenceFor($id));
        $this->get('/support-tickets/'.$id)->assertNotFound();
        $this->post('/support-tickets/'.$id.'/messages', ['body' => 'Baobab should never write here'])->assertNotFound();
        $this->post('/support-tickets/'.$id.'/close')->assertNotFound();

        $this->assertSame($counts, $this->counts());
        $this->assertEquals($before, $this->ticket($id)->getAttributes());
        $this->assertSame(1, DB::table('school_support_ticket_messages')->where('ticket_id', $id)->count());
    }

    public function test_a_school_never_reads_hq_internal_notes(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open();
        $this->as($this->staff('operations'));
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'note', 'body' => self::NOTE])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('notifications')->where('type', 'school.support_reply')->count(), 'a note tells nobody');

        $this->as($this->superAdmin());
        $this->get('/support-tickets/'.$id)->assertOk()->assertSee(self::BODY)->assertDontSee(self::NOTE)->assertDontSee('Root cause');
        $this->get('/support-tickets')->assertOk()->assertDontSee('Root cause');
        $shown = app(CurrentSchool::class)->run(1, fn () => app(SchoolSupportTickets::class)->show($this->superAdmin(), $id));
        $this->assertSame([self::BODY], array_column($shown['messages'], 'body'));
    }

    public function test_the_reply_notice_tells_the_opener_with_the_subject_only(): void
    {
        $admin = $this->superAdmin();
        $this->as($admin);
        $id = $this->open(['subject' => 'Timetable page is empty']);
        $this->as($this->staff('support'));
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => self::REPLY])->assertRedirect()->assertSessionHasNoErrors();

        $notice = Notification::acrossSchools()->where('type', 'school.support_reply')->sole();
        $this->assertSame(1, (int) $notice->school_id);
        $this->assertSame('Navigate Ed replied to your support ticket', $notice->title);
        $this->assertSame('Timetable page is empty', $notice->message);
        $this->assertSame(['link' => '/support-tickets/'.$id], $notice->delivery_payload);
        $stored = json_encode($notice->getAttributes());
        foreach (['fixed the cache', 'reload the page', 'empty grid'] as $words) {
            $this->assertStringNotContainsString($words, (string) $stored);
        }
        $event = DB::table('domain_events')->where('school_id', 1)->where('type', 'notification.requested')->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame([(int) $admin->id], json_decode((string) $event->payload, true)['user_ids'] ?? null);

        // Delivery (the queued fanout, called directly: the test transaction never commits).
        $sent = app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send($admin->fresh(), $notice->type, $notice->title, $notice->message, '/support-tickets/'.$id, $notice));
        $this->assertContains('in_app', $sent);
        $this->assertSame(1, DB::table('user_notifications')->where('notification_id', $notice->id)->where('user_id', $admin->id)->count());
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->assertSame([], app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send($teacher, $notice->type, $notice->title, $notice->message, '/support-tickets/'.$id, $notice)), 'admin portal only');
    }

    public function test_the_audit_log_holds_ids_and_states_never_the_free_text(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open(['subject' => 'Secret subject words']);
        $this->post('/support-tickets/'.$id.'/messages', ['body' => 'Secret school follow up'])->assertRedirect();
        $this->as($this->staff('owner'));
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => 'Secret hq reply words'])->assertRedirect();
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'note', 'body' => 'Secret hq note words'])->assertRedirect();
        $this->put('/saas/support/'.$id.'/status', ['status' => 'closed'])->assertRedirect();

        $rows = DB::table('audit_log')->where('action', 'like', 'support_ticket_%')->get();
        $this->assertCount(5, $rows);
        foreach ($rows as $row) {
            $this->assertSame(1, (int) $row->school_id);
            $this->assertStringNotContainsStringIgnoringCase('secret', (string) $row->before.(string) $row->after, $row->action);
            $this->assertSame($id, json_decode((string) $row->after, true)['ticket_id']);
        }
        $this->assertStringNotContainsStringIgnoringCase('secret', (string) DB::table('school_account_events')->where('kind', 'support')->pluck('body')->implode(' '));
    }

    public function test_hq_school_page_shows_open_tickets_and_the_latest_with_links(): void
    {
        $this->as($this->superAdmin());
        $first = $this->open(['subject' => 'First Jacaranda ticket']);
        $second = $this->open(['subject' => 'Second Jacaranda ticket', 'priority' => 'urgent']);
        $this->post('/support-tickets/'.$first.'/close')->assertRedirect();
        $this->as($this->schoolBAdmin());
        $this->open(['subject' => 'Baobab ticket']);

        $this->as($this->staff('support'));
        $this->get('/saas/schools/1')->assertOk()->assertSee('One open support ticket.')
            ->assertSee('Second Jacaranda ticket')->assertSee('First Jacaranda ticket')->assertDontSee('Baobab ticket')
            ->assertSee(route('saas.support.show', $second), false)->assertSee(route('saas.support.show', $first), false);
        $this->get('/saas/schools/2')->assertOk()->assertSee('One open support ticket.')->assertSee('Baobab ticket')->assertDontSee('Jacaranda ticket');
    }

    public function test_closed_tickets_assignment_and_input_are_checked(): void
    {
        $this->as($this->superAdmin());
        $id = $this->open();
        $this->post('/support-tickets', ['subject' => 'Hi', 'category' => 'gossip', 'priority' => 'whenever', 'body' => 'short'])
            ->assertSessionHasErrors(['subject', 'category', 'priority', 'body']);
        $this->assertSame(1, DB::table('school_support_tickets')->count());

        $owner = $this->staff('owner');
        $sales = $this->staff('sales');
        $this->as($owner);
        $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => $sales->id])->assertSessionHasErrors('assigned_to');
        $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => $this->superAdmin()->id])->assertSessionHasErrors('assigned_to');
        $this->assertNull($this->ticket($id)->assigned_to);
        $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => $owner->id])->assertSessionHasNoErrors();
        $this->put('/saas/support/'.$id.'/assignee', ['assigned_to' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->ticket($id)->assigned_to);
        $this->put('/saas/support/'.$id.'/status', ['status' => 'lost'])->assertSessionHasErrors('status');
        $this->get('/saas/support/999999')->assertNotFound();

        $this->put('/saas/support/'.$id.'/status', ['status' => 'closed'])->assertSessionHasNoErrors();
        $this->assertSame('Support ticket '.SupportTicket::referenceFor($id).' closed by Navigate Ed.', (string) DB::table('school_account_events')->where('kind', 'support')->latest('id')->value('body'));
        $messages = DB::table('school_support_ticket_messages')->count();
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'reply', 'body' => 'Too late to reply'])->assertStatus(409);
        $this->post('/saas/support/'.$id.'/messages', ['kind' => 'note', 'body' => 'A note after closing is fine'])->assertSessionHasNoErrors();
        $this->assertSame($messages + 1, DB::table('school_support_ticket_messages')->count());

        $this->as($this->superAdmin());
        $this->post('/support-tickets/'.$id.'/messages', ['body' => 'Is this still open?'])->assertStatus(409);
        $this->post('/support-tickets/'.$id.'/close')->assertStatus(409);

        $this->as($owner);
        $this->put('/saas/support/'.$id.'/status', ['status' => 'open'])->assertSessionHasNoErrors();
        $ticket = $this->ticket($id);
        $this->assertSame(['open', null, null], [$ticket->status, $ticket->closed_at, $ticket->closed_by]);
        $this->assertSame('Support ticket '.SupportTicket::referenceFor($id).' reopened by Navigate Ed.', (string) DB::table('school_account_events')->where('kind', 'support')->latest('id')->value('body'));
        $this->assertSame($messages + 1, DB::table('school_support_ticket_messages')->count());
    }
}
