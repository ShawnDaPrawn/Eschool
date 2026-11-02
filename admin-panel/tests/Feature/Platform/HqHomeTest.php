<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiUsage;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolAccount;
use App\Models\Platform\SchoolContact;
use App\Models\User;
use App\Services\Platform\Saas\PlatformHome;
use App\Services\Platform\Saas\PlatformIncidents;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The HQ home page (Action Plan 4F): the landing page after an HQ sign-in,
 * with one tile per area the staff member's role may open, counted across
 * both schools, never naming a person. engineering/specs/platform/hq-home.md.
 */
class HqHomeTest extends TestCase
{
    use UsesTwoSchools;

    /** Every person and private text of the two schools that must never reach the page. */
    private const PEOPLE = ['Asha', 'Farid', 'Mona', 'Gina', 'Tara', 'Theo', 'superadmin@gmail.com', '@example.com', 'Nomsa Baobab-Contact', 'Synthetic private tutor answer'];

    /** The tiles of the features in main, in page order. */
    private const ALL = ['stages', 'at_risk', 'onboarding', 'incidents', 'lurits', 'ai', 'health'];

    private const SCHOOL_TILES = ['stages', 'at_risk', 'onboarding'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        DB::table('schools')->update(['timezone' => 'Africa/Johannesburg']);
    }

    private function staff(string $role, ?string $email = null): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $email ?? $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-hq-home-password-264!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** Something to count in every area, in both schools. */
    private function seedBothSchools(): void
    {
        // School B is at risk, with an HQ business contact whose name must not show; School A is onboarding (no record).
        app(CurrentSchool::class)->run(2, function (): void {
            SchoolAccount::query()->create(['stage' => 'at_risk', 'revision' => 1]);
            SchoolContact::query()->create(['role' => 'principal', 'name' => 'Nomsa Baobab-Contact', 'email' => 'nomsa@baobab.example']);
        });

        $support = $this->staff('support', 'incident-recorder@platform.example');
        $incidents = app(PlatformIncidents::class);
        $base = ['category' => 'breach', 'personal_information' => 'yes', 'detected_at' => now('Africa/Johannesburg')->subDays(3)->format('Y-m-d\TH:i'), 'summary' => 'A synthetic incident summary for the home page.'];
        $incidents->open($base + ['title' => 'Export sent to the wrong place', 'severity' => 'high', 'schools' => [1, 2]], $support);
        $incidents->open($base + ['title' => 'A low synthetic event', 'severity' => 'low', 'schools' => [2]], $support);

        // One LURITS claim raised by each school.
        foreach ([[1, $this->schoolA->ids['asha'], $this->schoolB->ids['asha_user']], [2, $this->schoolB->ids['ben'], $this->schoolA->ids['ben_user']]] as [$school, $learner, $holder]) {
            DB::table('lurits_conflicts')->insert(['school_id' => $school, 'learner_id' => $learner, 'lurits_hash' => hash('sha256', 'lurits-'.$school),
                'holder_user_id' => $holder, 'source' => 'import', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        }

        // Safeguarding flags waiting: one open in A, two in B, one escalated in B.
        $this->flag(1, 'open');
        $this->flag(2, 'open');
        $this->flag(2, 'open');
        $this->flag(2, 'escalated');

        // Delivery failures in the last day from both schools, and one too old to count.
        foreach ([[1, 'mail', 'failed', 'failed_at', 2], [2, 'mail', 'failed', 'failed_at', 1], [2, 'mail', 'bounced', 'feedback_at', 1], [1, 'push', 'failed', 'failed_at', 1], [2, 'push', 'failed', 'failed_at', 48]] as [$school, $channel, $status, $column, $hoursAgo]) {
            DB::table('notification_deliveries')->insert(['school_id' => $school, 'delivery_key' => (string) Str::uuid(), 'type' => 'synthetic.notice', 'channel' => $channel,
                'recipient_user_id' => $this->schoolA->ids['farid_user'], 'status' => $status, $column => now()->subHours($hoursAgo), 'created_at' => now()->subHours($hoursAgo), 'updated_at' => now()]);
        }
        foreach ([1, 30] as $hoursAgo) {
            DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => '{"person":"Asha"}', 'exception' => 'Synthetic failure for Farid', 'failed_at' => now()->subHours($hoursAgo)]);
        }
        foreach ([300, 60, -600] as $secondsAgo) {
            DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{"person":"Gina"}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->getTimestamp() - $secondsAgo, 'created_at' => now()->getTimestamp() - 600]);
        }
        config(['queue.default' => 'database']);
    }

    private function flag(int $schoolId, string $status): void
    {
        app(CurrentSchool::class)->run($schoolId, function () use ($schoolId, $status): void {
            $usage = AiUsage::create([
                'user_id' => $schoolId === 1 ? $this->schoolA->ids['tara_user'] : $this->schoolB->ids['tara_user'], 'feature' => 'tutor', 'provider' => 'openai',
                'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', Str::random()), 'usage_date' => now('Africa/Johannesburg')->toDateString(), 'unit' => 'tokens',
                'state' => 'completed', 'used_units' => 100, 'reserved_units' => 0, 'cost_cents' => 0, 'reserved_cost_cents' => 0, 'billing_currency' => 'USD',
                'inference_output' => ['output' => 'Synthetic private tutor answer'], 'inference_sources' => [],
            ]);
            AiFlag::create(['ai_usage_id' => $usage->id, 'code' => 'synthetic_self_harm_signal', 'review_status' => $status]);
        });
    }

    /** @return list<string> the tiles the page shows, in page order */
    private function shownTiles(TestResponse $page): array
    {
        preg_match_all('/data-hq-tile="([a-z_]+)"/', (string) $page->getContent(), $matches);

        return $matches[1];
    }

    public function test_sign_in_lands_on_the_home_page_and_the_old_links_still_work(): void
    {
        $this->staff('sales');

        $this->post('/login', ['email' => 'sales@platform.example', 'password' => 'Synthetic-hq-home-password-264!'])->assertRedirect('/saas/home');
        $this->get('/saas/home')->assertOk()->assertSee('Schools by stage')
            ->assertSee('href="'.route('saas.index').'"', false);
        // The school list keeps its address, and the platform name in the bar leads home.
        $this->get('/saas')->assertOk()->assertSee('Baobab High')->assertSee('href="'.route('saas.home').'"', false);
    }

    public function test_the_owner_sees_counts_from_both_schools_and_never_a_person(): void
    {
        $this->seedBothSchools();
        $this->actingAs($this->staff('owner'));

        $page = $this->get('/saas/home')->assertOk();
        $tiles = $page->viewData('tiles');

        $this->assertSame(self::ALL, $this->shownTiles($page));
        $this->assertSame(self::ALL, array_keys($tiles));
        // Schools by stage: A has no record (onboarding), B is at risk.
        $this->assertSame(2, $tiles['stages']['total']);
        $this->assertSame(['lead' => 0, 'demo' => 0, 'contracted' => 0, 'onboarding' => 1, 'live' => 0, 'at_risk' => 1, 'churned' => 0], $tiles['stages']['stages']);
        $this->assertSame(['count' => 1, 'schools' => [['id' => 2, 'name' => 'Baobab High']]], $tiles['at_risk']);
        // Onboarding: School A only, from its live checklist.
        $this->assertSame(1, $tiles['onboarding']['count']);
        $this->assertSame([1], array_column($tiles['onboarding']['schools'], 'id'));
        $this->assertSame('Contract and operator agreement', $tiles['onboarding']['schools'][0]['next']);
        // Incidents and claims from both schools; flags summed across both.
        $this->assertSame(['open' => 2, 'contained' => 0, 'serious' => 1, 'attention' => 2, 'oldest_days' => 3], $tiles['incidents']);
        $this->assertSame(['open' => 2, 'capped' => false], $tiles['lurits']);
        $this->assertSame([3, 1, 2], [$tiles['ai']['flags_open'], $tiles['ai']['flags_escalated'], $tiles['ai']['schools']]);
        // Platform health: the due jobs, the last day's failed jobs, and delivery failures from both schools.
        $this->assertSame(2, $tiles['health']['backlog']);
        $this->assertSame(5, $tiles['health']['oldest_wait_minutes']);
        $this->assertSame(['since' => 1, 'kept' => 2], $tiles['health']['failed_jobs']);
        $this->assertSame(['failed' => 2, 'bounced' => 1, 'complained' => 0], $tiles['health']['deliveries']['mail']);
        $this->assertSame(['failed' => 1, 'bounced' => 0, 'complained' => 0], $tiles['health']['deliveries']['push']);

        $page->assertSee('Baobab High')->assertSee('Jobs waiting');
        foreach (self::PEOPLE as $person) {
            $page->assertDontSee($person);
            $this->assertStringNotContainsString($person, (string) json_encode($tiles), 'the tiles carry no person');
        }
        // Incident titles are free text and stay on the register.
        $page->assertDontSee('Export sent to the wrong place');
    }

    public function test_each_role_sees_only_its_tiles_and_every_link_opens(): void
    {
        $this->seedBothSchools();
        $expected = [
            'owner' => self::ALL,
            'operations' => self::ALL,
            'support' => self::ALL,
            'finance' => self::SCHOOL_TILES,
            'content' => self::SCHOOL_TILES,
            'sales' => self::SCHOOL_TILES,
        ];
        $this->assertSame(array_keys((array) config('platform_roles')), array_keys($expected), 'every role is covered');

        foreach ($expected as $role => $tiles) {
            $this->actingAs($this->staff($role));
            $page = $this->get('/saas/home')->assertOk();
            $this->assertSame($tiles, $this->shownTiles($page), $role);
            $this->assertSame($tiles, array_keys($page->viewData('tiles')), $role);

            // Every link on a tile leads to a page this role may open.
            preg_match('/<div class="saas-home">(.*)<\/div>\s*<\/main>/s', (string) $page->getContent(), $home);
            preg_match_all('/href="([^"]+)"/', $home[1], $links);
            $this->assertNotEmpty($links[1], $role);
            foreach (array_unique($links[1]) as $link) {
                $this->get($link)->assertOk();
            }
        }
    }

    public function test_sales_sees_only_what_it_may(): void
    {
        $this->seedBothSchools();
        $this->actingAs($this->staff('sales'));

        $page = $this->get('/saas/home')->assertOk()->assertSee('Schools by stage')->assertSee('Schools at risk')->assertSee('Onboarding blockers');
        foreach (['Open incidents', 'LURITS claims', 'Safeguarding flag', 'Platform health', 'Jobs waiting', 'Incident register', 'AI control centre'] as $hidden) {
            $page->assertDontSee($hidden);
        }
        foreach (['/saas/incidents', '/saas/lurits', '/saas/ai', '/saas/audit', '/saas/staff'] as $area) {
            $this->get($area)->assertForbidden();
        }
        // The service refuses tiles a role does not hold, whoever calls it.
        $this->assertSame(self::SCHOOL_TILES, array_keys(app(PlatformHome::class)->tiles(auth()->user())));
    }

    public function test_a_school_account_and_a_retired_role_are_refused(): void
    {
        foreach (['superadmin' => $this->superAdmin(), 'teacher' => User::query()->findOrFail($this->schoolA->ids['tara_user'])] as $who => $user) {
            $this->actingAs($user)->get('/saas/home')->assertForbidden();
        }
        $this->actingAs(app(CurrentSchool::class)->run(2, fn () => User::query()->findOrFail($this->schoolB->ids['tara_user'])))->get('/saas/home')->assertForbidden();

        $retired = $this->staff('sales');
        PlatformAdminGrant::query()->where('user_id', $retired->id)->update(['role' => 'retired']);
        $this->actingAs($retired->fresh())->get('/saas/home')->assertForbidden();
        $this->assertSame([], app(PlatformStaffAccess::class)->abilities($retired->fresh()));

        auth()->logout();
        $this->get('/saas/home')->assertRedirect('/login');
    }

    public function test_an_empty_platform_shows_quiet_tiles(): void
    {
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(1, fn () => SchoolAccount::query()->create(['stage' => 'live', 'revision' => 1]));
        app(CurrentSchool::class)->run(2, fn () => SchoolAccount::query()->create(['stage' => 'live', 'revision' => 1]));
        $this->actingAs($this->staff('operations'));

        $page = $this->get('/saas/home')->assertOk()
            ->assertSee('No school is marked at risk.')->assertSee('No active school is being set up.')
            ->assertSee('The queue is not the database queue, so its backlog is not measured here.');
        $tiles = $page->viewData('tiles');
        $this->assertSame(['open' => 0, 'contained' => 0, 'serious' => 0, 'attention' => 0, 'oldest_days' => null], $tiles['incidents']);
        $this->assertNull($tiles['health']['backlog']);
    }

    /**
     * Budget: a fixed number of queries whatever the number of schools that
     * are past onboarding. Only the onboarding tile reads per school, and
     * only for up to PlatformHome::ONBOARDING_LIMIT schools being set up.
     */
    public function test_the_page_takes_a_fixed_budget_of_queries(): void
    {
        $this->seedBothSchools();
        $owner = $this->staff('owner');
        $sales = $this->staff('sales');

        $ownerQueries = $this->queriesFor($owner);
        $salesQueries = $this->queriesFor($sales);

        // A third school that is live adds no query.
        School::query()->forceCreate(['id' => 3, 'name' => 'Cedar Primary', 'slug' => 'cedar-primary', 'timezone' => 'Africa/Johannesburg']);
        app(CurrentSchool::class)->run(3, fn () => SchoolAccount::query()->create(['stage' => 'live', 'revision' => 1]));
        $this->assertSame($ownerQueries, $this->queriesFor($owner), 'The number of queries grows with the number of live schools');

        $this->assertLessThanOrEqual(70, $ownerQueries, "The owner's home page takes $ownerQueries queries, more than budgeted");
        $this->assertLessThanOrEqual(50, $salesQueries, "Sales' home page takes $salesQueries queries, more than budgeted");
    }

    private function queriesFor(User $user): int
    {
        $this->app['auth']->forgetGuards();
        $this->actingAs($user);
        // Once to warm what the app keeps between requests (settings), then counted.
        $this->get('/saas/home')->assertOk();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/saas/home')->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    public function test_the_incident_tile_counts_every_unclosed_incident_beyond_the_registers_display_limit(): void
    {
        $row = ['category' => 'breach', 'personal_information' => 'no', 'summary' => 'Synthetic', 'status' => 'open', 'severity' => 'low',
            'detected_at' => now()->subDays(10), 'created_at' => now(), 'updated_at' => now()];
        $columns = Schema::getColumnListing('platform_incidents');
        foreach (range(1, 205) as $n) {
            DB::table('platform_incidents')->insert(array_intersect_key($row + ['title' => 'Synthetic incident '.$n], array_flip($columns)));
        }
        // The newest is the only serious one, so a count over the 200 oldest would miss it.
        DB::table('platform_incidents')->orderByDesc('id')->limit(1)->update(['severity' => 'critical', 'detected_at' => now()->subDay()]);

        $tiles = app(PlatformHome::class)->tiles($this->staff('owner', 'incident-count@platform.example'));
        $this->assertSame(205, $tiles['incidents']['open']);
        $this->assertSame(1, $tiles['incidents']['serious']);
    }
}
