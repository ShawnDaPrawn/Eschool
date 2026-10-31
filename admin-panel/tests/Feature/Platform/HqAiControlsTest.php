<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiSchoolLimit;
use App\Models\Ai\AiUsage;
use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformAiControls;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The HQ AI control centre (Action Plan SA.15): qualification, caps, spend
 * against caps and safeguarding queue counts per school, and switching a
 * school's AI features. engineering/specs/platform/hq-ai.md.
 */
class HqAiControlsTest extends TestCase
{
    use UsesTwoSchools;

    private const PRIVATE_OUTPUT = 'Synthetic private tutor answer never shown at HQ';

    private const PRIVATE_SOURCE = 'synthetic-private-source-never-shown';

    private const REASON = 'Pilot agreed with the principal for term four.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        DB::table('schools')->update(['timezone' => 'Africa/Johannesburg']);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00', 'Africa/Johannesburg'));
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-ai-control-password-418!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** One metered receipt in a school, with private content that must never reach HQ. */
    private function usage(int $schoolId, string $date, int $cost, int $reserved = 0, ?string $currency = 'USD', string $feature = 'school_assistant'): AiUsage
    {
        return app(CurrentSchool::class)->run($schoolId, fn () => AiUsage::create([
            'user_id' => $schoolId === 1 ? $this->schoolA->ids['tara_user'] : $this->schoolB->ids['tara_user'], 'feature' => $feature, 'provider' => 'openai',
            'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', Str::random()), 'usage_date' => $date, 'unit' => 'tokens', 'state' => 'completed',
            'used_units' => 100, 'reserved_units' => 0, 'cost_cents' => $cost, 'reserved_cost_cents' => $reserved, 'billing_currency' => $currency,
            'inference_output' => ['output' => self::PRIVATE_OUTPUT], 'inference_sources' => [self::PRIVATE_SOURCE],
        ]));
    }

    private function flag(int $schoolId, string $status = 'open', ?CarbonImmutable $at = null): void
    {
        $usage = $this->usage($schoolId, now()->toDateString(), 0, 0, 'USD', 'tutor');
        app(CurrentSchool::class)->run($schoolId, function () use ($usage, $status, $at): void {
            $flag = AiFlag::create(['ai_usage_id' => $usage->id, 'code' => 'synthetic_self_harm_signal', 'review_status' => $status]);
            if ($at !== null) {
                DB::table('ai_flags')->where('id', $flag->id)->update(['created_at' => $at]);
            }
        });
    }

    private function moduleRow(int $schoolId, string $module): ?object
    {
        return DB::table('school_modules')->where('school_id', $schoolId)->where('module', $module)->first();
    }

    private function seedMetering(): void
    {
        // School A: two receipts this month (one today), one last month and one in another currency.
        $this->usage(1, '2026-10-20', 120, 30);
        $this->usage(1, '2026-10-03', 250);
        $this->usage(1, '2026-09-30', 9999);
        $this->usage(1, '2026-10-05', 40, 0, null);
        // Subject tutor spend is personal: shown apart, never against School A's cap.
        $this->usage(1, '2026-10-20', 900, 0, 'USD', 'tutor');
        $this->usage(1, '2026-10-04', 60, 0, 'USD', 'tutor');
        // School B: a larger, distinctive spend that must stay on School B's row.
        $this->usage(2, '2026-10-20', 777);
        $this->flag(1);
        $this->flag(1, 'escalated');
        $this->flag(1, 'reviewed');
        $this->flag(2);
        $this->flag(2);
        $this->flag(2);
        // Past the 30-day retention and without a snapshot: no reviewer can open it, so it is not counted.
        $this->flag(1, 'open', CarbonImmutable::now()->subDays(40));
        app(CurrentSchool::class)->run(1, fn () => AiSchoolLimit::create(['revision' => 1, 'paused' => false, 'limits' => ['school_daily_cents' => 600, 'learner_daily_cents' => 20]]));
    }

    public function test_the_page_shows_qualification_caps_spend_and_queue_counts_from_metering(): void
    {
        $this->seedMetering();
        $this->actingAs($this->staff('support'));

        $response = $this->get('/saas/ai')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $response->assertSee('AI control centre')->assertSee('Not qualified')->assertSee('No evaluation result recorded')
            ->assertSee('10 cases in its corpus')->assertDontSee('Switch an AI feature for a school')->assertSee('Spending caps')->assertDontSee('Save caps for')
            ->assertDontSee(self::PRIVATE_OUTPUT)->assertDontSee(self::PRIVATE_SOURCE)->assertDontSee('synthetic_self_harm_signal');

        $schools = collect($response->viewData('schools'))->keyBy('id');
        // School A: this month is 120 + 30 reserved + 250 in USD, and 40 with no recorded currency; September is left out.
        $this->assertSame(['0.40 (currency not recorded)', 'US$ 4.00'], $schools[1]['month']);
        $this->assertSame('US$ 1.50', $schools[1]['today']);
        // The school's own 600-cent limit lowers the 1000-cent platform ceiling; its learner limit lowers 50 to 20.
        $this->assertSame(['US$ 6.00', 25, 'US$ 0.20'], [$schools[1]['cap'], $schools[1]['used_percent'], $schools[1]['learner_cap']]);
        $this->assertSame(['open' => 1, 'escalated' => 1], $schools[1]['queue']);
        // The tutor's 9.00 today would put School A over its cap if it counted: it is personal and shown apart.
        $this->assertSame(['month' => ['US$ 9.60'], 'today' => 'US$ 9.00', 'learner_cap' => 'US$ 0.20'], $schools[1]['personal']);
        $this->assertFalse($schools[1]['over']);
        $response->assertSee('Subject tutor (personal)')->assertSee('Not counted: a personal service');
        // School B: only its own spend and flags.
        $this->assertSame(['US$ 7.77'], $schools[2]['month']);
        $this->assertSame(['US$ 7.77', 'US$ 10.00', 77, 'US$ 0.50'], [$schools[2]['today'], $schools[2]['cap'], $schools[2]['used_percent'], $schools[2]['learner_cap']]);
        $this->assertSame(['open' => 3, 'escalated' => 0], $schools[2]['queue']);
        // School B's flagged tutor receipts were metered at nothing: personal, and not on School A's row.
        $this->assertSame(['month' => ['US$ 0.00'], 'today' => 'US$ 0.00', 'learner_cap' => 'US$ 0.50'], $schools[2]['personal']);

        $features = $response->viewData('features');
        $this->assertFalse($features['tutor']['qualified']);
        $this->assertSame(['tutor', 50, 50000], [$features['tutor']['module'], $features['tutor']['caps']['learner_cents'], $features['tutor']['caps']['learner_tokens']]);
        $this->assertTrue($features['tutor']['personal']);
        $this->assertNull($features['tutor']['caps']['school_cents']);
        $this->assertStringNotContainsString(self::PRIVATE_OUTPUT, json_encode($response->viewData('schools')));
        $this->assertFalse(app(CurrentSchool::class)->isSet());
    }

    public function test_school_b_spend_is_never_attributed_to_school_a(): void
    {
        $this->usage(2, '2026-10-20', 777);
        $this->usage(2, '2026-10-01', 333);
        $this->flag(2);
        $this->actingAs($this->staff('operations'));

        $schools = collect($this->get('/saas/ai')->assertOk()->viewData('schools'))->keyBy('id');
        $this->assertSame([], $schools[1]['month']);
        $this->assertSame(['US$ 0.00', 0, ['open' => 0, 'escalated' => 0]], [$schools[1]['today'], $schools[1]['used_percent'], $schools[1]['queue']]);
        $this->assertSame(['US$ 11.10'], $schools[2]['month']);
        $this->assertSame(['open' => 1, 'escalated' => 0], $schools[2]['queue']);
    }

    public function test_owner_switches_a_qualified_feature_on_and_off_with_an_audited_reason(): void
    {
        config(['ai.providers.openai.qualified' => true]);
        $this->actingAs($this->staff('owner'));
        $this->get('/saas/ai')->assertOk()->assertSee('Switch an AI feature for a school');
        $before = $this->moduleRow(2, 'tutor');

        $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])
            ->assertRedirect('/saas/ai')->assertSessionHasNoErrors()->assertSessionHas('success', 'AI feature switched.');
        $this->assertTrue((bool) $this->moduleRow(1, 'tutor')->enabled);
        $this->assertEquals($before, $this->moduleRow(2, 'tutor'));
        $this->assertSame(['tutor'], array_keys(array_filter(collect($this->get('/saas/ai')->viewData('schools'))->keyBy('id')[1]['modules'], fn ($s) => $s['enabled'])));

        // The same switch again changes nothing and writes no audit row.
        $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])
            ->assertSessionHas('success', 'Nothing changed: the feature was already in that state.');
        $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '0', 'reason' => 'Paused while the term ends.'])->assertSessionHasNoErrors();
        $this->assertFalse((bool) $this->moduleRow(1, 'tutor')->enabled);

        $audit = AuditLog::query()->where('action', 'platform_ai_feature_switched')->orderBy('id')->get();
        $this->assertCount(2, $audit);
        $this->assertSame([1, 1], $audit->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $first = $audit->first();
        $this->assertSame(['enabled' => false], $first->before);
        // Ids, states and the reason only: the reason is the one free-text value.
        $this->assertSame(['module', 'features', 'enabled', 'reason', 'changed_by'], array_keys($first->after));
        $this->assertSame(['tutor', ['tutor'], true, self::REASON], [$first->after['module'], $first->after['features'], $first->after['enabled'], $first->after['reason']]);
        $strings = array_filter(array_merge(array_values($first->after), $first->after['features']), 'is_string');
        $this->assertSame(['tutor', self::REASON, 'tutor'], array_values($strings));
        $this->get('/saas/audit')->assertOk()->assertSee('AI feature switched');
    }

    public function test_switching_on_an_unqualified_feature_is_refused(): void
    {
        $this->actingAs($this->staff('owner'));
        $before = [$this->moduleRow(1, 'tutor'), AuditLog::query()->where('action', 'platform_ai_feature_switched')->count()];

        $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])->assertSessionHasErrors('module');
        $this->putJson('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => true, 'reason' => self::REASON])->assertStatus(422)
            ->assertJsonValidationErrors('module');
        // A module carrying two features needs both qualified: one qualified provider is not enough.
        config(['ai.providers.anthropic.qualified' => true, 'ai.inference.features.message_translation.provider' => 'anthropic']);
        $this->putJson('/saas/ai/switch', ['school_id' => 1, 'module' => 'ask_school', 'enabled' => true, 'reason' => self::REASON])->assertStatus(422);
        // Not an AI module, an unknown school or no reason: refused before anything changes.
        $this->putJson('/saas/ai/switch', ['school_id' => 1, 'module' => 'gallery', 'enabled' => true, 'reason' => self::REASON])->assertStatus(422);
        $this->putJson('/saas/ai/switch', ['school_id' => 999, 'module' => 'tutor', 'enabled' => false, 'reason' => self::REASON])->assertStatus(422);
        $this->putJson('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => false])->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->assertEquals($before, [$this->moduleRow(1, 'tutor'), AuditLog::query()->where('action', 'platform_ai_feature_switched')->count()]);
        $this->assertFalse(app(CurrentSchool::class)->run(1, fn () => app(ModuleGate::class)->allows('tutor')));
    }

    public function test_wrong_roles_are_refused_and_nothing_changes(): void
    {
        config(['ai.providers.openai.qualified' => true]);
        $this->seedMetering();
        $before = fn (): array => [DB::table('school_modules')->orderBy('id')->get()->toArray(), AuditLog::query()->where('action', 'platform_ai_feature_switched')->count()];
        $snapshot = $before();

        foreach (['sales', 'finance', 'content'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas')->assertOk()->assertDontSee('AI control centre');
            $this->get('/saas/ai')->assertForbidden();
            $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])->assertForbidden();
        }
        // Support and Operations see the page but cannot switch.
        foreach (['support', 'operations'] as $role) {
            $this->actingAs($this->staff($role));
            $this->get('/saas')->assertOk()->assertSee('AI control centre');
            $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])->assertForbidden();
            try {
                app(PlatformAiControls::class)->switch(['school_id' => 1, 'module' => 'tutor', 'enabled' => true, 'reason' => self::REASON], User::query()->where('email', $role.'@platform.example')->firstOrFail());
                $this->fail('The service switched a feature for '.$role);
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }

        $this->assertEquals($snapshot, $before());
    }

    public function test_a_school_account_is_refused(): void
    {
        config(['ai.providers.openai.qualified' => true]);
        $this->seedMetering();
        $before = $this->moduleRow(1, 'tutor');
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->schoolA->ids['tara_user'])] as $schoolUser) {
            $this->actingAs($schoolUser);
            $this->get('/saas/ai')->assertForbidden();
            $this->put('/saas/ai/switch', ['school_id' => 1, 'module' => 'tutor', 'enabled' => '1', 'reason' => self::REASON])->assertForbidden();
        }
        $this->assertEquals($before, $this->moduleRow(1, 'tutor'));
        $this->assertFalse((bool) $before?->enabled);
        $this->assertSame(0, AuditLog::query()->where('action', 'platform_ai_feature_switched')->count());
    }

    public function test_the_dated_cost_report_stays_behind_its_own_grant_and_is_linked_for_holders(): void
    {
        $support = $this->staff('support');
        $this->actingAs($support)->get('/saas/ai')->assertOk()->assertDontSee('Open the dated cost report');
        $this->get('/platform/ai-costs')->assertForbidden();

        PlatformAiCostGrant::create(['user_id' => $support->id, 'active' => true, 'expires_at' => now()->addHour()]);
        $this->get('/saas/ai')->assertOk()->assertSee('Open the dated cost report');
        $this->get('/platform/ai-costs')->assertOk()->assertSee('School AI costs');
    }
}
