<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolHealthSnapshot;
use App\Models\User;
use App\Services\Platform\Notifications\PushQueue;
use App\Services\Platform\Saas\PlatformSchoolHealth;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * HQ school health and usage (SA.10): figures from each school's own rows,
 * at-risk flags at their thresholds, the nightly figures and the comparison,
 * who may see them, and that no person is ever shown.
 * engineering/specs/platform/hq-school-health.md.
 */
class HqSchoolHealthTest extends TestCase
{
    use UsesTwoSchools;

    /** The demo schools' people: none of them may appear on an HQ page. */
    private const PEOPLE = ['Asha', 'Farid', 'Mona', 'Gina', 'Tara', 'Theo', 'Cara', '@example.com', 'GR-'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        // Every sign-in and device in a test is one it made itself.
        DB::table('user_devices')->delete();
        DB::table('personal_access_tokens')->delete();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-health-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function person(string $name, bool $schoolB = false): int
    {
        return ($schoolB ? $this->schoolB : $this->schoolA)->ids[$name.'_user'];
    }

    /** An app sign-in: created $signedIn days ago, last used $used days ago. */
    private function signIn(int $userId, int $signedIn, ?int $used = null, string $app = 'parent-app'): int
    {
        return DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => (new User)->getMorphClass(), 'tokenable_id' => $userId, 'name' => $app, 'token' => hash('sha256', Str::random(40)),
            'abilities' => '["*"]', 'last_used_at' => $used === null ? null : now()->subDays($used), 'created_at' => now()->subDays($signedIn), 'updated_at' => now(),
        ]);
    }

    private function device(int $userId, int $tokenId, int $seen): void
    {
        DB::table('user_devices')->insert(['user_id' => $userId, 'token_hash' => hash('sha256', Str::random(40)), 'token' => 'synthetic', 'platform' => 'android',
            'personal_access_token_id' => $tokenId, 'last_seen_at' => now()->subDays($seen), 'created_at' => now()->subDays($seen), 'updated_at' => now()]);
    }

    /** A delivery row as the transports log it: the type behind its channel (mail.comms.notice, push.comms.message). */
    private function delivery(int $school, string $channel, string $status, int $daysAgo = 1, string $type = 'comms.notice'): void
    {
        DB::table('notification_deliveries')->insert(['school_id' => $school, 'delivery_key' => (string) Str::uuid(), 'type' => $channel.'.'.$type, 'channel' => $channel,
            'status' => $status, 'attempt' => 1, 'created_at' => now()->subDays($daysAgo), 'updated_at' => now()]);
    }

    private function aiUsage(int $school, int $userId, string $date, int $cents, int $reserved = 0, ?string $currency = null, string $feature = 'teaching_assistant'): void
    {
        $currency ??= (string) config('ai.billing_currency', 'USD');
        DB::table('ai_usage')->insert(['school_id' => $school, 'user_id' => $userId, 'feature' => $feature, 'provider' => 'openai', 'operation_id' => (string) Str::uuid(),
            'request_hash' => str_repeat('a', 64), 'usage_date' => $date, 'unit' => 'tokens', 'state' => 'completed', 'cost_cents' => $cents, 'reserved_cost_cents' => $reserved,
            'billing_currency' => $currency, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function failedJob(?int $school): void
    {
        $context = $school === null ? Context::dehydrate() : app(CurrentSchool::class)->run($school, fn () => Context::dehydrate());
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'failed_at' => now()->subDay(),
            'payload' => json_encode(['displayName' => 'App\\Jobs\\Platform\\SendMail', 'illuminate:log:context' => $context], JSON_THROW_ON_ERROR),
            'exception' => 'RuntimeException: could not reach farid@example.com']);
    }

    /** School A: three guardians, two reachable families, deliveries, AI spend and a failed job. */
    private function seedSchoolA(): void
    {
        // Farid (father of Asha and Cara) signed in 3 days ago, verified email.
        $this->signIn($this->person('farid'), 3);
        DB::table('users')->where('id', $this->person('farid'))->update(['email_verified_at' => now()]);
        // Mona (mother of Asha) signed in 20 days ago, used the app 10 days ago, has a phone for push.
        $mona = $this->signIn($this->person('mona'), 20, 10);
        $this->device($this->person('mona'), $mona, 20);
        // Gina (guardian of Ben): no sign-in, no verified email, no device: Ben is not reached.
        DB::table('users')->whereIn('id', [$this->person('mona'), $this->person('gina')])->update(['email_verified_at' => null]);
        $this->signIn($this->person('tara'), 15, 1, 'teacher-app');
        $this->signIn($this->person('asha'), 5, null, 'student-app');
        DB::table('learner_activity_days')->insert(['school_id' => 1, 'learner_id' => $this->schoolA->ids['asha'], 'grade_level_id' => DB::table('grade_levels')->value('id'),
            'session_year_id' => $this->schoolA->ids['session_year'], 'on_date' => now('Africa/Johannesburg')->toDateString()]);

        foreach (range(1, 19) as $i) {
            $this->delivery(1, 'mail', 'delivered');
        }
        $this->delivery(1, 'mail', 'bounced');
        $this->delivery(1, 'mail', 'complained');
        $this->delivery(1, 'mail', 'failed');
        $this->delivery(1, 'mail', 'bounced', 40);
        foreach (range(1, 4) as $i) {
            $this->delivery(1, 'push', 'sent', 2, 'comms.message');
        }
        $this->delivery(1, 'push', 'failed', 2, 'comms.message');
        // A sensitive module's messages are never counted.
        $this->delivery(1, 'push', 'sent', 2, 'safeguarding.alert');
        foreach (['wellbeing', 'teaching_assistant'] as $module) {
            DB::table('school_modules')->updateOrInsert(['school_id' => 1, 'module' => $module], ['in_package' => true, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        $local = CarbonImmutable::now('Africa/Johannesburg');
        $this->aiUsage(1, $this->person('tara'), $local->toDateString(), 150, 50);
        $this->aiUsage(1, $this->person('tara'), $local->startOfMonth()->subDay()->toDateString(), 9000);
        DB::table('ai_school_limits')->insert(['school_id' => 1, 'revision' => 1, 'paused' => false, 'limits' => json_encode(['school_daily_cents' => 500]), 'created_at' => now(), 'updated_at' => now()]);
        $this->failedJob(1);
        $this->failedJob(null);
    }

    /** School B: busy everywhere, so any of it in School A's figures is a leak. */
    private function seedSchoolB(): void
    {
        foreach (['farid', 'mona', 'gina', 'tara', 'theo', 'asha', 'ben', 'cara'] as $name) {
            $token = $this->signIn($this->person($name, true), 1, 0);
            $this->device($this->person($name, true), $token, 1);
            DB::table('users')->where('id', $this->person($name, true))->update(['email_verified_at' => now()]);
        }
        foreach (range(1, 30) as $i) {
            $this->delivery(2, 'mail', 'bounced');
            $this->delivery(2, 'push', 'failed');
        }
        $this->aiUsage(2, $this->person('tara', true), now('Africa/Johannesburg')->toDateString(), 70000);
        $this->failedJob(2);
        $this->failedJob(2);
    }

    public function test_messages_queued_through_the_push_transport_count_for_their_module(): void
    {
        Queue::fake();
        DB::table('notification_deliveries')->delete();
        app(CurrentSchool::class)->run(1, function (): void {
            PushQueue::toDevice('comms.message', $this->person('farid'), 'synthetic-token', ['message' => ['token' => 'synthetic-token']]);
            PushQueue::toDevice('comms.notice', $this->person('farid'), 'synthetic-token', ['message' => ['token' => 'synthetic-token']]);
        });
        app(CurrentSchool::class)->forget();
        $this->assertSame(['push.comms.message', 'push.comms.notice'], DB::table('notification_deliveries')->orderBy('type')->pluck('type')->all());

        $modules = collect(app(PlatformSchoolHealth::class)->compute(1)['modules']['list'])->keyBy('key');
        $this->assertSame(1, $modules['chat']['messages']);
        $this->assertSame(1, $modules['communication']['messages']);
    }

    public function test_the_figures_come_from_the_schools_own_data(): void
    {
        $this->seedSchoolA();
        $m = app(PlatformSchoolHealth::class)->compute(1);

        $this->assertSame(['guardians' => 3, 'active' => 1], $m['families']);
        $this->assertSame(['learners' => 3, 'reached' => 2, 'by_email' => 2, 'by_push' => 1], $m['reach']);
        $this->assertSame(['people' => 3, 'days_7' => 1, 'days_30' => 2], $m['sign_ins']['Parent']);
        $this->assertSame(['people' => 3, 'days_7' => 1, 'days_30' => 1], $m['sign_ins']['Student']);
        $this->assertSame(['people' => 2, 'days_7' => 1, 'days_30' => 1], $m['sign_ins']['Teacher']);
        $this->assertSame(0, $m['sign_ins']['Staff']['days_30']);
        $this->assertSame(1, $m['learners_active']);
        $this->assertSame(['mail_sent' => 21, 'mail_bounced' => 1, 'mail_complained' => 1, 'mail_failed' => 1, 'push_attempts' => 6, 'push_failed' => 1], $m['delivery']);
        $modules = collect($m['modules']['list'])->keyBy('key');
        $this->assertSame(22, $modules['communication']['messages']);
        $this->assertSame(5, $modules['chat']['messages']);
        $this->assertSame(2, $modules['teaching_assistant']['ai_requests']);
        $this->assertSame([true, null, null], [$modules['wellbeing']['sensitive'], $modules['wellbeing']['messages'], $modules['wellbeing']['ai_requests']]);
        $this->assertSame($m['modules']['on'], $modules->count());
        $daysInMonth = CarbonImmutable::now('Africa/Johannesburg')->daysInMonth;
        $this->assertSame(['spent_cents' => 200, 'cap_cents' => 500 * $daysInMonth, 'daily_cap_cents' => 500], array_intersect_key($m['ai'], array_flip(['spent_cents', 'cap_cents', 'daily_cap_cents'])));
        $this->assertSame(['count' => 1, 'by_job' => ['SendMail' => 1]], $m['failed_jobs']);
        $this->assertNull($m['storage']);
    }

    public function test_ai_spend_in_another_billing_currency_is_never_added_to_the_cap(): void
    {
        $this->seedSchoolA();
        $other = config('ai.billing_currency', 'USD') === 'ZAR' ? 'USD' : 'ZAR';
        $local = CarbonImmutable::now('Africa/Johannesburg');
        $before = app(PlatformSchoolHealth::class)->compute(1)['ai'];
        $this->assertFalse($before['other_currency']);

        // Receipts from before a change of billing currency, and one with none recorded.
        $this->aiUsage(1, $this->person('tara'), $local->toDateString(), 40000, 0, $other);
        DB::table('ai_usage')->where('cost_cents', 40000)->update(['billing_currency' => null]);
        $this->aiUsage(1, $this->person('tara'), $local->toDateString(), 30000, 0, $other);

        $after = app(PlatformSchoolHealth::class)->compute(1)['ai'];
        $this->assertSame([200, true], [$after['spent_cents'], $after['other_currency']]);
        $this->actingAs($this->staff('owner'))->get('/saas/schools/1/health')->assertOk()
            ->assertSee('Spend recorded in another billing currency this month is not included.');
    }

    public function test_school_ai_summary_uses_live_hq_caps_and_keeps_the_schools_lower_limit(): void
    {
        $this->seedSchoolA();
        config(['ai.inference.features' => [
            'school_assistant' => ['school_daily_cents' => 900],
            'teaching_assistant' => ['school_daily_cents' => 700],
        ]]);
        foreach (['school_assistant' => 100, 'teaching_assistant' => 200] as $feature => $cents) {
            DB::table('platform_ai_caps')->insert(['feature' => $feature, 'cap' => 'school_daily_cents', 'cents' => $cents]);
        }
        $health = app(PlatformSchoolHealth::class);
        $this->assertSame(200, $health->compute(1)['ai']['daily_cap_cents']);
        DB::table('ai_school_limits')->where('school_id', 1)->update(['limits' => json_encode(['school_daily_cents' => 50])]);
        $this->assertSame(50, $health->compute(1)['ai']['daily_cap_cents']);
    }

    public function test_school_ai_summary_excludes_personal_tutor_spend_and_caps(): void
    {
        $this->seedSchoolA();
        config(['ai.inference.personal_features' => ['tutor'], 'ai.inference.features' => [
            'teaching_assistant' => ['school_daily_cents' => 100],
            'tutor' => ['school_daily_cents' => 999999],
        ]]);
        $local = CarbonImmutable::now('Africa/Johannesburg');
        $this->aiUsage(1, $this->person('asha'), $local->toDateString(), 90000, 10000, null, 'tutor');
        $other = config('ai.billing_currency', 'USD') === 'ZAR' ? 'USD' : 'ZAR';
        $this->aiUsage(1, $this->person('asha'), $local->toDateString(), 80000, 0, $other, 'tutor');
        $ai = app(PlatformSchoolHealth::class)->compute(1)['ai'];
        $this->assertSame([200, 100, false], [$ai['spent_cents'], $ai['daily_cap_cents'], $ai['other_currency']]);
    }

    public function test_school_b_activity_never_counts_for_school_a(): void
    {
        $this->seedSchoolA();
        $before = app(PlatformSchoolHealth::class)->compute(1);
        $this->seedSchoolB();
        $after = app(PlatformSchoolHealth::class)->compute(1);

        $this->assertSame($before, $after);
        // School B's own figures carry its activity.
        $b = app(PlatformSchoolHealth::class)->compute(2);
        $this->assertSame(['guardians' => 3, 'active' => 3], $b['families']);
        $this->assertSame(3, $b['reach']['reached']);
        $this->assertSame(30, $b['delivery']['mail_bounced']);
        $this->assertSame(70000, $b['ai']['spent_cents']);
        $this->assertSame(2, $b['failed_jobs']['count']);
    }

    /** @return array<string, mixed> healthy figures that trigger no flag */
    private function metrics(array $override = []): array
    {
        return array_replace_recursive([
            'families' => ['guardians' => 100, 'active' => 10],
            'reach' => ['learners' => 100, 'reached' => 90],
            'delivery' => ['mail_sent' => 1000, 'mail_bounced' => 0, 'mail_complained' => 0, 'push_attempts' => 100, 'push_failed' => 0],
            'ai' => ['spent_cents' => 0, 'cap_cents' => 100],
            'failed_jobs' => ['count' => 0],
        ], $override);
    }

    private function states(array $metrics, array $history = [10, 10, 10, 10]): array
    {
        return collect(app(PlatformSchoolHealth::class)->flags($metrics, $history))->pluck('state', 'key')->all();
    }

    public function test_flags_trigger_at_and_above_their_thresholds(): void
    {
        $this->assertSame(['activity_drop' => 'ok', 'low_reach' => 'ok', 'bounce_rate' => 'ok', 'complaint_rate' => 'ok', 'push_failure_rate' => 'ok', 'ai_spend' => 'ok', 'failed_jobs' => 'ok'], $this->states($this->metrics()));

        // Weekly active families down 30% or more on the four weeks before.
        $this->assertSame('ok', $this->states($this->metrics(['families' => ['active' => 8]]))['activity_drop']);
        $this->assertSame('flagged', $this->states($this->metrics(['families' => ['active' => 7]]))['activity_drop']);
        $this->assertSame('flagged', $this->states($this->metrics(['families' => ['active' => 0]]))['activity_drop']);
        $this->assertSame('unknown', $this->states($this->metrics(['families' => ['active' => 0]]), [10, 10, 10])['activity_drop']);
        $this->assertSame('unknown', $this->states($this->metrics(['families' => ['active' => 0]]), [4, 4, 4, 4])['activity_drop']);
        // Reach below 60% (60% itself is fine), once there are enough learners.
        $this->assertSame('ok', $this->states($this->metrics(['reach' => ['reached' => 60]]))['low_reach']);
        $this->assertSame('flagged', $this->states($this->metrics(['reach' => ['reached' => 59]]))['low_reach']);
        $this->assertSame('unknown', $this->states($this->metrics(['reach' => ['learners' => 9, 'reached' => 0]]))['low_reach']);
        // Bounces at 5% or more of at least 20 emails; complaints at 0.1% of at least 100.
        $this->assertSame('flagged', $this->states($this->metrics(['delivery' => ['mail_sent' => 20, 'mail_bounced' => 1]]))['bounce_rate']);
        $this->assertSame('flagged', $this->states($this->metrics(['delivery' => ['mail_sent' => 20, 'mail_bounced' => 2]]))['bounce_rate']);
        $this->assertSame('ok', $this->states($this->metrics(['delivery' => ['mail_sent' => 21, 'mail_bounced' => 1]]))['bounce_rate']);
        $this->assertSame('unknown', $this->states($this->metrics(['delivery' => ['mail_sent' => 19, 'mail_bounced' => 1]]))['bounce_rate']);
        $this->assertSame('flagged', $this->states($this->metrics(['delivery' => ['mail_complained' => 1]]))['complaint_rate']);
        $this->assertSame('ok', $this->states($this->metrics(['delivery' => ['mail_sent' => 1001, 'mail_complained' => 1]]))['complaint_rate']);
        // Push failures at 10%; AI at 90% of the cap; 10 failed jobs.
        $this->assertSame('flagged', $this->states($this->metrics(['delivery' => ['push_failed' => 10]]))['push_failure_rate']);
        $this->assertSame('ok', $this->states($this->metrics(['delivery' => ['push_failed' => 9]]))['push_failure_rate']);
        $this->assertSame('flagged', $this->states($this->metrics(['ai' => ['spent_cents' => 90]]))['ai_spend']);
        $this->assertSame('flagged', $this->states($this->metrics(['ai' => ['spent_cents' => 150]]))['ai_spend']);
        $this->assertSame('ok', $this->states($this->metrics(['ai' => ['spent_cents' => 89]]))['ai_spend']);
        $this->assertSame('unknown', $this->states($this->metrics(['ai' => ['cap_cents' => null]]))['ai_spend']);
        $this->assertSame('flagged', $this->states($this->metrics(['failed_jobs' => ['count' => 10]]))['failed_jobs']);
        $this->assertSame('ok', $this->states($this->metrics(['failed_jobs' => ['count' => 9]]))['failed_jobs']);

        // The thresholds are configuration.
        config(['school_health.flags.low_reach.percent' => 95]);
        $this->assertSame('flagged', $this->states($this->metrics())['low_reach']);
    }

    public function test_the_trend_reads_only_the_schools_nightly_figures_of_the_four_weeks_before(): void
    {
        $today = CarbonImmutable::now('Africa/Johannesburg')->startOfDay();
        foreach ([1 => [6 => 99, 7 => 10, 14 => 12, 21 => 14, 28 => 16, 29 => 99], 2 => [7 => 500, 14 => 500]] as $school => $days) {
            app(CurrentSchool::class)->run($school, function () use ($days, $today) {
                foreach ($days as $ago => $active) {
                    SchoolHealthSnapshot::query()->create(['snapshot_date' => $today->subDays($ago)->toDateString(), 'active_guardians' => $active, 'metrics' => [], 'flags' => [], 'computed_at' => now()]);
                }
            });
        }

        $this->assertSame([16, 14, 12, 10], app(PlatformSchoolHealth::class)->history(1, $today->toDateString()));
        $this->assertSame([500, 500], app(PlatformSchoolHealth::class)->history(2, $today->toDateString()));
    }

    public function test_the_nightly_run_keeps_one_row_per_school_and_day_and_flagged_schools_come_first(): void
    {
        $this->seedSchoolA();
        $this->seedSchoolB();
        $old = CarbonImmutable::now('Africa/Johannesburg')->subDays(500)->toDateString();
        app(CurrentSchool::class)->run(1, fn () => SchoolHealthSnapshot::query()->create(['snapshot_date' => $old, 'metrics' => [], 'flags' => [], 'computed_at' => now()]));

        $this->artisan('platform:school-health')->assertSuccessful();
        $this->artisan('platform:school-health')->assertSuccessful();

        $rows = app(CurrentSchool::class)->acrossSchools(fn () => SchoolHealthSnapshot::acrossSchools()->orderBy('school_id')->get());
        $this->assertSame([1, 2], $rows->map(fn ($row) => (int) $row->getAttribute('school_id'))->all(), 'one row per school and day; the 500-day-old row is gone');
        [$a, $b] = [$rows[0], $rows[1]];
        $this->assertSame([3, 1, 3, 2], [$a->guardians, $a->active_guardians, $a->learners, $a->reached_learners]);
        $flagged = fn (SchoolHealthSnapshot $row) => collect($row->flags)->where('state', 'flagged')->pluck('key')->all();
        $this->assertSame([], $flagged($a));
        $this->assertSame(['bounce_rate', 'push_failure_rate', 'ai_spend'], $flagged($b));
        $this->assertSame(3, $b->flag_count);

        $this->actingAs($this->staff('operations'));
        $page = $this->get('/saas/health')->assertOk()->assertSee('Bounces')->assertSee('Push failures');
        $html = $page->getContent();
        $this->assertLessThan(strpos($html, 'data-health-school="1"'), strpos($html, 'data-health-school="2"'), 'the flagged school is listed first');
        $html = $this->get('/saas/health?sort=reach')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'data-health-school="2"'), strpos($html, 'data-health-school="1"'), 'lowest reach first');
        $this->get('/saas/health?sort[]=x')->assertOk();
    }

    public function test_a_school_without_nightly_figures_is_listed_as_not_computed(): void
    {
        $this->actingAs($this->staff('finance'));

        $this->get('/saas/health')->assertOk()->assertSee('Not computed yet');
        $this->get('/saas/schools/1/health')->assertOk()->assertSee('No nightly figures yet')->assertSee('Not enough nightly figures yet');
    }

    public function test_each_allowed_role_sees_the_pages_and_others_are_refused(): void
    {
        $count = fn () => app(CurrentSchool::class)->acrossSchools(fn () => SchoolHealthSnapshot::acrossSchools()->count());
        $before = $count();
        foreach (['owner', 'operations', 'support', 'finance'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas')->assertOk()->assertSee('School health');
            $this->get('/saas/schools/1')->assertOk()->assertSee('/saas/schools/1/health', false);
            $this->get('/saas/health')->assertOk();
            $this->get('/saas/schools/2/health')->assertOk()->assertSee('Baobab High');
            $this->get('/saas/schools/987654/health')->assertNotFound();
        }
        $refused = ['sales' => $this->staff('sales'), 'content' => $this->staff('content')];
        foreach ($refused as $user) {
            $this->actingAs($user);
            $this->get('/saas')->assertOk()->assertDontSee('School health');
            $this->get('/saas/schools/1')->assertOk()->assertDontSee('/saas/schools/1/health', false);
            $this->get('/saas/health')->assertForbidden();
            $this->get('/saas/schools/1/health')->assertForbidden();
            $this->get('/saas/schools/987654/health')->assertForbidden();
        }
        // School accounts never reach HQ: School A's Super Admin and a teacher.
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->person('tara'))] as $user) {
            $this->actingAs($user);
            $this->get('/saas/health')->assertForbidden();
            $this->get('/saas/schools/1/health')->assertForbidden();
        }
        // The service checks the ability itself, whatever the route.
        $sales = $refused['sales'];
        foreach ([fn () => app(PlatformSchoolHealth::class)->school($sales, 1), fn () => app(PlatformSchoolHealth::class)->overview($sales)] as $call) {
            try {
                $call();
                $this->fail('Sales must be refused by the service.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame($before, $count(), 'opening the pages writes nothing');
    }

    public function test_the_pages_show_counts_and_never_a_person(): void
    {
        $this->seedSchoolA();
        $this->seedSchoolB();
        $this->artisan('platform:school-health')->assertSuccessful();
        $this->actingAs($this->staff('support'));

        $school = $this->get('/saas/schools/1/health')->assertOk()
            ->assertSee('1 of 3 guardians')->assertSee('2 of 3')->assertSee('SendMail: 1')->assertSee('Not shown (sensitive)', false);
        foreach ([...self::PEOPLE, self::SCHOOL_B_MARKER, 'could not reach'] as $text) {
            $school->assertDontSee($text);
        }
        $list = $this->get('/saas/health')->assertOk();
        foreach ([...self::PEOPLE, 'could not reach'] as $text) {
            $list->assertDontSee($text);
        }
        // School B's people carry the same first names, and their own addresses.
        $other = $this->get('/saas/schools/2/health')->assertOk()->assertSee('3 of 3 guardians');
        foreach (self::PEOPLE as $text) {
            $other->assertDontSee($text);
        }
    }
}
