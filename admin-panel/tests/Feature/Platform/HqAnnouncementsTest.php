<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Notification;
use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Announcements\AnnouncementText;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Saas\PlatformAnnouncements;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Announcements to schools (Action Plan SA.13): HQ drafts, previews,
 * publishes and withdraws; each targeted school's Super Admins get a notice
 * and a panel banner, and dismissing it is a read.
 * engineering/specs/platform/hq-announcements.md.
 */
class HqAnnouncementsTest extends TestCase
{
    use UsesTwoSchools;

    private const TABLES = ['platform_announcements', 'platform_announcement_schools', 'platform_announcement_reads'];

    private User $schoolBAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // The demo memberships start on the day the fixture is built; the clock below is pinned,
        // so they must have started before it whatever day the suite runs.
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->forget();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 08:00', PlatformAnnouncements::TIMEZONE));
        // School A: no package (legacy) and no HQ record (the default stage, onboarding). School B: core, live.
        DB::table('schools')->where('id', 1)->update(['package_key' => null]);
        DB::table('schools')->where('id', 2)->update(['package_key' => 'core']);
        DB::table('school_accounts')->insert(['school_id' => 2, 'stage' => 'live', 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->schoolBAdmin = app(CurrentSchool::class)->run(2, function (): User {
            $admin = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'baobab-principal@example.test', 'mobile' => '', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $admin->assignRole('Super Admin');

            return $admin;
        });
        app(CurrentSchool::class)->forget();
    }

    private int $staffCount = 0;

    private function staff(string $role): User
    {
        $email = $role.(++$this->staffCount).'@platform.example';
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => 'Synthetic-announcement-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function teacher(): User
    {
        return User::query()->findOrFail($this->schoolA->ids['tara_user']);
    }

    private function fields(array $changes = []): array
    {
        return $changes + [
            'kind' => 'release_notes', 'severity' => 'info', 'title' => 'New report cards are here',
            'body' => "Report cards now show **term marks** side by side.\n\n- Faster printing\n- Clearer comments",
            'shows_from' => '2026-11-02T07:00', 'shows_until' => '2026-11-09T07:00', 'audience' => 'all',
        ];
    }

    private function draft(array $changes = []): int
    {
        $this->post('/saas/announcements', $this->fields($changes))->assertRedirect()->assertSessionHasNoErrors();

        return (int) DB::table('platform_announcements')->max('id');
    }

    private function revision(int $id): int
    {
        return (int) DB::table('platform_announcements')->where('id', $id)->value('revision');
    }

    private function publish(int $id): void
    {
        $this->post('/saas/announcements/'.$id.'/publish', ['revision' => $this->revision($id)])->assertRedirect('/saas/announcements/'.$id)->assertSessionHasNoErrors();
    }

    /** As an Operations user: draft and publish, then sign back in as whoever was acting. */
    private function published(array $changes = []): int
    {
        $this->actingAs($this->staff('operations'));
        $id = $this->draft($changes);
        $this->publish($id);

        return $id;
    }

    /** @return array<int, list<int>> school id => user ids the announcement's notices went to */
    private function recipients(int $id): array
    {
        $title = (string) DB::table('platform_announcements')->where('id', $id)->value('title');
        $notices = Notification::acrossSchools()->where('type', PlatformAnnouncements::TYPE)->where('title', $title)->get();
        $sent = [];
        foreach ($notices as $notice) {
            $users = OutboxEvent::query()->where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole()->payload['user_ids'];
            sort($users);
            $sent[(int) $notice->school_id] = $users;
        }
        ksort($sent);

        return $sent;
    }

    private function counts(): array
    {
        return array_map(fn (string $table) => DB::table($table)->count(), self::TABLES) + [3 => DB::table('platform_announcements')->sum('revision')];
    }

    /** Every HQ announcement endpoint, each with a payload that would succeed for a permitted role, refused with 403. */
    private function refusedEverywhere(int $draft, int $live): void
    {
        $this->get('/saas/announcements')->assertForbidden();
        $this->get('/saas/announcements/'.$draft)->assertForbidden();
        $this->post('/saas/announcements', $this->fields(['title' => 'Should never be drafted']))->assertForbidden();
        $this->put('/saas/announcements/'.$draft, $this->fields(['title' => 'Should never change', 'revision' => $this->revision($draft)]))->assertForbidden();
        $this->post('/saas/announcements/'.$draft.'/publish', ['revision' => $this->revision($draft)])->assertForbidden();
        $this->post('/saas/announcements/'.$live.'/withdraw', ['reason' => 'Should never be withdrawn'])->assertForbidden();
    }

    public function test_operations_drafts_previews_and_publishes_to_every_schools_super_admins(): void
    {
        $this->actingAs($ops = $this->staff('operations'));
        $this->get('/saas')->assertOk()->assertSee('Announcements');
        $id = $this->draft();
        $this->get('/saas/announcements/'.$id)->assertOk()->assertSee('Publishing now would reach 2 active school(s) and notify 2 Super Admin(s).')
            ->assertSee('<strong>term marks</strong>', false)->assertSee('<li>Faster printing</li>', false);
        $this->assertSame([], $this->recipients($id), 'A draft sends nothing');

        $this->publish($id);

        $row = DB::table('platform_announcements')->where('id', $id)->first();
        $this->assertSame(['published', $ops->id], [$row->status, (int) $row->published_by]);
        // Shows from 07:00 South African time, stored in UTC whatever the app's or a school's time zone.
        $this->assertSame('2026-11-02 05:00:00', (string) $row->shows_from);
        $this->assertSame([1 => [$this->superAdmin()->id], 2 => [$this->schoolBAdmin->id]], $this->recipients($id));
        $this->assertSame([1, 2], DB::table('platform_announcement_schools')->where('announcement_id', $id)->orderBy('school_id')->pluck('school_id')->map(fn ($v) => (int) $v)->all());
        $notice = Notification::acrossSchools()->where('type', PlatformAnnouncements::TYPE)->where('school_id', 1)->sole();
        $this->assertSame(['New report cards are here', PlatformAnnouncements::LINK], [$notice->title, $notice->delivery_payload['link']]);
        $this->assertStringStartsWith('Release notes from Navigate Ed: Report cards now show term marks', $notice->message);
        // The type is offered to a school's Super Admin under the school's notification rules.
        $this->assertTrue(app(CurrentSchool::class)->run(1, fn () => app(NotificationRules::class)->offered($this->superAdmin(), PlatformAnnouncements::TYPE)));

        $audit = AuditLog::query()->where('action', 'platform_announcement_published')->sole();
        $this->assertSame(['announcement_id' => $id, 'schools' => 2, 'recipients' => 2], array_intersect_key($audit->after, array_flip(['announcement_id', 'schools', 'recipients'])));
        $this->assertStringNotContainsString('term marks', (string) $audit->toJson());
        $this->get('/saas/audit')->assertOk()->assertSee('Announcement published');
        $this->get('/saas/announcements')->assertOk()->assertSee('Showing now')->assertSee('0 of 2');
    }

    public function test_targeting_by_package_stage_and_list_reaches_only_those_schools_super_admins(): void
    {
        $byPackage = $this->published(['title' => 'For core schools', 'audience' => 'packages', 'packages' => ['core']]);
        $byLegacy = $this->published(['title' => 'For legacy schools', 'audience' => 'packages', 'packages' => ['legacy', 'standard']]);
        $byStage = $this->published(['title' => 'For onboarding schools', 'audience' => 'stages', 'stages' => ['onboarding']]);
        $byLive = $this->published(['title' => 'For live schools', 'audience' => 'stages', 'stages' => ['live', 'at_risk']]);
        $byList = $this->published(['title' => 'For School A', 'audience' => 'schools', 'schools' => [1]]);

        $a = [1 => [$this->superAdmin()->id]];
        $b = [2 => [$this->schoolBAdmin->id]];
        $this->assertSame($b, $this->recipients($byPackage));
        $this->assertSame($a, $this->recipients($byLegacy), 'A school with no package is on the legacy package');
        $this->assertSame($a, $this->recipients($byStage), 'A school with no HQ record is at the default stage');
        $this->assertSame($b, $this->recipients($byLive));
        $this->assertSame($a, $this->recipients($byList));
        // Teachers, parents and learners are never recipients.
        $all = collect([$byPackage, $byLegacy, $byStage, $byLive, $byList])->flatMap(fn ($id) => array_merge(...array_values($this->recipients($id))))->unique()->values()->all();
        $this->assertNotContains($this->teacher()->id, $all);
        $this->assertSame(1, DB::table('platform_announcement_schools')->where('announcement_id', $byList)->count());

        // A suspended school is not reached, and an audience with no active school cannot publish.
        DB::table('schools')->where('id', 2)->update(['status' => 'suspended']);
        $id = $this->draft(['title' => 'Only for live schools', 'audience' => 'stages', 'stages' => ['live']]);
        $this->get('/saas/announcements/'.$id)->assertOk()->assertSee('Publishing now would reach 0 active school(s)');
        $this->post('/saas/announcements/'.$id.'/publish', ['revision' => $this->revision($id)])->assertSessionHasErrors('audience');
        $this->assertSame('draft', DB::table('platform_announcements')->where('id', $id)->value('status'));
        $this->assertSame(0, DB::table('platform_announcement_schools')->where('announcement_id', $id)->count());
    }

    public function test_school_b_never_sees_an_announcement_targeted_only_at_school_a(): void
    {
        $forA = $this->published(['title' => 'Only for Jacaranda', 'audience' => 'schools', 'schools' => [1]]);
        $both = $this->published(['title' => 'For both schools', 'audience' => 'schools', 'schools' => [1, 2]]);

        $this->actingAs($this->schoolBAdmin);
        $this->get('/navigate-ed-announcements')->assertOk()->assertDontSee('Only for Jacaranda')->assertSee('For both schools')
            ->assertDontSee('data-platform-announcement="'.$forA.'"', false)->assertDontSee('Jacaranda');
        $this->post('/navigate-ed-announcements/'.$forA.'/read')->assertNotFound();
        $this->assertSame(0, DB::table('platform_announcement_reads')->count());

        // School A sees its own, never the list of schools it went to nor School B's reads.
        $this->post('/navigate-ed-announcements/'.$both.'/read')->assertRedirect();
        $this->actingAs($this->superAdmin());
        $this->get('/home')->assertOk()->assertSee('data-platform-announcement="'.$forA.'"', false)->assertSee('data-platform-announcement="'.$both.'"', false);
        $this->get('/navigate-ed-announcements')->assertOk()->assertSee('Only for Jacaranda')->assertDontSee(self::SCHOOL_B_MARKER)
            ->assertDontSee('You dismissed it');
        $this->assertSame([2], DB::table('platform_announcement_reads')->pluck('school_id')->map(fn ($v) => (int) $v)->all());
        // Only Super Admins: a teacher sees no banner and cannot dismiss or open the page.
        $this->actingAs($this->teacher());
        $this->get('/home')->assertOk()->assertDontSee('data-platform-announcement', false);
        $this->get('/navigate-ed-announcements')->assertForbidden();
        $this->post('/navigate-ed-announcements/'.$forA.'/read')->assertForbidden();
        $this->assertSame(1, DB::table('platform_announcement_reads')->count());
    }

    public function test_the_banner_shows_only_between_its_dates_and_goes_once_withdrawn(): void
    {
        $id = $this->published(['kind' => 'maintenance', 'severity' => 'warning', 'title' => 'Planned maintenance on Saturday',
            'shows_from' => '2026-11-03T08:00', 'shows_until' => '', 'window_starts_at' => '2026-11-07T22:00', 'window_ends_at' => '2026-11-07T23:30']);
        // A maintenance banner stops when its window ends.
        $this->assertSame(CarbonImmutable::parse('2026-11-07 23:30', PlatformAnnouncements::TIMEZONE)->setTimezone('UTC')->format('Y-m-d H:i:s'),
            (string) DB::table('platform_announcements')->where('id', $id)->value('shows_until'));
        $this->assertStringContainsString('Maintenance from 2026-11-07 22:00 to 2026-11-07 23:30', Notification::acrossSchools()->where('type', PlatformAnnouncements::TYPE)->where('school_id', 1)->sole()->message);
        $banner = 'data-platform-announcement="'.$id.'"';
        $this->actingAs($this->superAdmin());

        $this->get('/home')->assertOk()->assertDontSee($banner, false);
        $this->travelTo(CarbonImmutable::parse('2026-11-03 08:01', PlatformAnnouncements::TIMEZONE));
        $this->get('/home')->assertOk()->assertSee($banner, false)->assertSee('m3-banner--warning', false)->assertSee('Maintenance from 2026-11-07 22:00 to 2026-11-07 23:30.');
        $this->travelTo(CarbonImmutable::parse('2026-11-07 23:31', PlatformAnnouncements::TIMEZONE));
        $this->get('/home')->assertOk()->assertDontSee($banner, false);
        $this->travelTo(CarbonImmutable::parse('2026-11-05 12:00', PlatformAnnouncements::TIMEZONE));
        $this->get('/home')->assertOk()->assertSee($banner, false);

        $this->actingAs($this->staff('owner'));
        $this->post('/saas/announcements/'.$id.'/withdraw', ['reason' => 'Maintenance moved to next week'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', DB::table('platform_announcements')->where('id', $id)->value('status'));
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_announcement_withdrawn')->count());
        $this->actingAs($this->superAdmin());
        $this->get('/home')->assertOk()->assertDontSee($banner, false);
        $this->get('/navigate-ed-announcements')->assertOk()->assertDontSee('Planned maintenance on Saturday');
        $this->post('/navigate-ed-announcements/'.$id.'/read')->assertNotFound();
        $this->assertSame(0, DB::table('platform_announcement_reads')->count());
    }

    public function test_a_school_in_another_time_zone_sees_the_banner_on_the_same_clock(): void
    {
        // School A keeps its clock in Auckland (13 hours ahead of UTC in November); HQ works in South African time.
        app(CurrentSchool::class)->run(1, fn () => Settings::query()->updateOrCreate(['type' => 'time_zone'], ['message' => 'Pacific/Auckland']));
        app(CurrentSchool::class)->forget();
        $id = $this->published(['title' => 'Showing for one hour', 'shows_from' => '2026-11-02T07:59', 'shows_until' => '2026-11-02T09:00']);
        $this->assertSame('2026-11-02 05:59:00', (string) DB::table('platform_announcements')->where('id', $id)->value('shows_from'), 'stored in UTC');
        $banner = 'data-platform-announcement="'.$id.'"';

        $this->actingAs($this->superAdmin());
        $this->get('/home')->assertOk()->assertSee($banner, false);
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:01', PlatformAnnouncements::TIMEZONE));
        $this->get('/home')->assertOk()->assertDontSee($banner, false);
        // Its times read in the school's own zone: 07:59 in Johannesburg is 18:59 in Auckland.
        $this->get('/navigate-ed-announcements/'.$id)->assertOk()->assertSee('From 2026-11-02 18:59');

        $this->post('/navigate-ed-announcements/'.$id.'/read')->assertRedirect();
        $this->assertSame('2026-11-02 07:01:00', (string) DB::table('platform_announcement_reads')->where('announcement_id', $id)->value('read_at'), 'reads are in UTC too');
    }

    public function test_every_announcement_stays_readable_a_page_at_a_time_and_on_its_own(): void
    {
        $ids = [];
        foreach (range(1, 26) as $n) {
            $ids[] = $this->published(['title' => sprintf('Release note number %02d', $n), 'shows_from' => sprintf('2026-11-01T%02d:00', $n % 24), 'shows_until' => '2026-12-31T07:00']);
        }
        $this->actingAs($this->superAdmin());
        $this->get('/navigate-ed-announcements')->assertOk()->assertSee('Older')->assertDontSee('Release note number 24');
        $this->get('/navigate-ed-announcements?page=2')->assertOk()->assertSee('Newer')->assertSee('Release note number 24');
        // A banner's "Read in full" opens the announcement itself, wherever it falls in the list.
        $this->get('/home')->assertOk()->assertSee('/navigate-ed-announcements/', false);
        $this->get('/navigate-ed-announcements/'.$ids[23])->assertOk()->assertSee('Release note number 24');

        // Not published to this school, or not published at all: not found.
        $this->actingAs($this->staff('operations'));
        $onlyB = $this->draft(['title' => 'Only for Baobab', 'audience' => 'schools', 'schools' => [2]]);
        $this->publish($onlyB);
        $draft = $this->draft(['title' => 'Still a draft']);
        $this->actingAs($this->superAdmin());
        $this->get('/navigate-ed-announcements/'.$onlyB)->assertNotFound();
        $this->get('/navigate-ed-announcements/'.$draft)->assertNotFound();
        $this->actingAs($this->teacher());
        $this->get('/navigate-ed-announcements/'.$ids[0])->assertForbidden();
    }

    public function test_dismissing_records_one_read_and_hq_sees_how_many_schools_and_admins_read_it(): void
    {
        $id = $this->published();
        $banner = 'data-platform-announcement="'.$id.'"';
        $this->actingAs($this->superAdmin());
        $this->get('/home')->assertOk()->assertSee($banner, false)->assertSee('New report cards are here');
        $this->from('/home')->post('/navigate-ed-announcements/'.$id.'/read')->assertRedirect('/home');
        $this->post('/navigate-ed-announcements/'.$id.'/read')->assertRedirect();
        $this->assertSame(1, DB::table('platform_announcement_reads')->where('announcement_id', $id)->where('user_id', $this->superAdmin()->id)->where('school_id', 1)->count());
        $this->get('/home')->assertOk()->assertDontSee($banner, false);
        $this->get('/navigate-ed-announcements')->assertOk()->assertSee('New report cards are here')->assertSee('You dismissed it on 2026-11-02 08:00.');

        $this->actingAs($this->staff('owner'));
        $this->get('/saas/announcements/'.$id)->assertOk()->assertSee('Reached 2 school(s); 1 of them have read it.')->assertSee('1 of 2 Super Admins read');
        $this->get('/saas/announcements')->assertOk()->assertSee('1 of 2');

        $this->actingAs($this->schoolBAdmin);
        $this->get('/navigate-ed-announcements')->assertOk()->assertSee('Mark as read')->assertDontSee('You dismissed it');
        $this->post('/navigate-ed-announcements/'.$id.'/read')->assertRedirect();
        $this->actingAs($this->staff('owner'));
        $this->get('/saas/announcements/'.$id)->assertOk()->assertSee('Reached 2 school(s); 2 of them have read it.')->assertSee('2 of 2 Super Admins read');
    }

    public function test_published_text_is_never_changed_and_stale_forms_are_refused(): void
    {
        $this->actingAs($this->staff('operations'));
        $id = $this->draft();
        $stale = $this->revision($id);
        $this->put('/saas/announcements/'.$id, $this->fields(['title' => 'Report cards, improved', 'revision' => $stale]))->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/saas/announcements/'.$id, $this->fields(['title' => 'From an old form', 'revision' => $stale]))->assertStatus(409);
        $this->post('/saas/announcements/'.$id.'/publish', ['revision' => $stale])->assertStatus(409);
        $this->post('/saas/announcements/'.$id.'/withdraw', ['reason' => 'Not published yet'])->assertStatus(409);
        $this->publish($id);

        $this->put('/saas/announcements/'.$id, $this->fields(['title' => 'Changed after publishing', 'revision' => $this->revision($id)]))->assertStatus(409);
        $this->post('/saas/announcements/'.$id.'/publish', ['revision' => $this->revision($id)])->assertStatus(409);
        $this->assertSame('Report cards, improved', DB::table('platform_announcements')->where('id', $id)->value('title'));
        $this->assertSame(1, Notification::acrossSchools()->where('type', PlatformAnnouncements::TYPE)->where('school_id', 1)->count(), 'Publishing twice sends nothing more');

        $this->post('/saas/announcements/'.$id.'/withdraw', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post('/saas/announcements/'.$id.'/withdraw', ['reason' => 'Wrong term dates'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/saas/announcements/'.$id.'/withdraw', ['reason' => 'Wrong term dates'])->assertStatus(409);
        $this->put('/saas/announcements/'.$id, $this->fields(['revision' => $this->revision($id)]))->assertStatus(409);
        $this->get('/saas/announcements/'.$id)->assertOk()->assertSee('Wrong term dates')->assertDontSee('Change the draft');
        $this->get('/saas/announcements/999')->assertNotFound();
    }

    public function test_impossible_drafts_are_refused(): void
    {
        $this->actingAs($ops = $this->staff('operations'));
        $this->post('/saas/announcements', $this->fields(['shows_until' => '2026-11-02T06:00']))->assertSessionHasErrors('shows_until');
        $this->post('/saas/announcements', $this->fields(['window_starts_at' => '2026-11-07T22:00', 'window_ends_at' => '2026-11-07T23:00']))->assertSessionHasErrors(['window_starts_at', 'window_ends_at']);
        $this->post('/saas/announcements', $this->fields(['kind' => 'maintenance', 'window_starts_at' => '2026-11-07T22:00', 'window_ends_at' => '2026-11-07T21:00']))->assertSessionHasErrors('window_ends_at');
        $this->post('/saas/announcements', $this->fields(['kind' => 'maintenance', 'window_starts_at' => '2026-11-07T22:00']))->assertSessionHasErrors('window_ends_at');
        $this->post('/saas/announcements', $this->fields(['audience' => 'packages']))->assertSessionHasErrors('packages');
        $this->post('/saas/announcements', $this->fields(['audience' => 'packages', 'packages' => ['gold']]))->assertSessionHasErrors('packages.0');
        $this->post('/saas/announcements', $this->fields(['audience' => 'stages', 'stages' => ['asleep']]))->assertSessionHasErrors('stages.0');
        $this->post('/saas/announcements', $this->fields(['audience' => 'schools', 'schools' => [999]]))->assertSessionHasErrors('schools.0');
        $this->post('/saas/announcements', $this->fields(['severity' => 'critical']))->assertSessionHasErrors('severity');
        $this->assertSame(0, DB::table('platform_announcements')->count());

        // An announcement whose end has passed would never show.
        $id = $this->draft(['shows_from' => '2026-10-01T08:00', 'shows_until' => '2026-10-02T08:00']);
        $this->post('/saas/announcements/'.$id.'/publish', ['revision' => $this->revision($id)])->assertSessionHasErrors('shows_until');
        $this->assertSame([0, 'draft'], [DB::table('platform_announcement_schools')->count(), DB::table('platform_announcements')->where('id', $id)->value('status')]);

        // The service validates and checks the ability itself.
        try {
            app(PlatformAnnouncements::class)->draft($this->fields(['shows_from' => '2026-11-02 07:00']), $ops);
            $this->fail('The service accepted a malformed time');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shows_from', $e->errors());
        }
        try {
            app(PlatformAnnouncements::class)->draft($this->fields(), $this->staff('sales'));
            $this->fail('The service let Sales draft');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(1, DB::table('platform_announcements')->count());
    }

    public function test_only_owner_and_operations_manage_announcements_and_no_school_user_reaches_them(): void
    {
        $live = $this->published();
        $this->actingAs($this->staff('owner'));
        $draft = $this->draft(['title' => 'A draft nobody else may touch']);
        $before = $this->counts();
        $notices = Notification::acrossSchools()->count();

        foreach (['sales', 'finance', 'content', 'support'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas')->assertOk()->assertDontSee('/saas/announcements');
            $this->refusedEverywhere($draft, $live);
        }
        // A school's Super Admin and its teachers never reach HQ's announcements, on any endpoint.
        foreach ([$this->superAdmin(), $this->schoolBAdmin, $this->teacher()] as $schoolUser) {
            $this->actingAs($schoolUser);
            $this->refusedEverywhere($draft, $live);
        }

        $this->assertSame($before, $this->counts());
        $this->assertSame($notices, Notification::acrossSchools()->count());
        $this->assertSame(['draft', 'published'], [DB::table('platform_announcements')->where('id', $draft)->value('status'), DB::table('platform_announcements')->where('id', $live)->value('status')]);
    }

    public function test_announcement_text_is_escaped_wherever_it_is_shown(): void
    {
        $id = $this->published([
            'title' => 'Update <img src=x onerror=alert(1)>',
            'body' => "<script>alert('x')</script> and **bold** text\n\n[Read the notes](https://navigate-ed.example/notes?a=1&b=2) or [this](javascript:alert(1))\n\n- <b>item</b>",
        ]);
        $this->assertSame('<p>&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt; and <strong>bold</strong> text</p>'."\n"
            .'<p><a href="https://navigate-ed.example/notes?a=1&amp;b=2" rel="noopener noreferrer" target="_blank">Read the notes</a> or [this](javascript:alert(1))</p>'."\n"
            .'<ul><li>&lt;b&gt;item&lt;/b&gt;</li></ul>', (string) AnnouncementText::html((string) DB::table('platform_announcements')->where('id', $id)->value('body')));
        $this->assertSame('a "quote"', AnnouncementText::plain('a **"quote"**'));

        $this->actingAs($this->superAdmin());
        foreach (['/home', '/navigate-ed-announcements'] as $page) {
            $this->get($page)->assertOk()->assertDontSee('<script>alert', false)->assertDontSee('<img src=x', false)->assertDontSee('href="javascript', false)
                ->assertSee('Update &lt;img src=x onerror=alert(1)&gt;', false);
        }
        $this->get('/navigate-ed-announcements')->assertSee('<strong>bold</strong>', false)->assertSee('&lt;b&gt;item&lt;/b&gt;', false);
        $this->actingAs($this->staff('owner'));
        $this->get('/saas/announcements/'.$id)->assertOk()->assertDontSee('<script>alert', false)->assertDontSee('<img src=x', false)->assertSee('&lt;script&gt;', false);
    }
}
