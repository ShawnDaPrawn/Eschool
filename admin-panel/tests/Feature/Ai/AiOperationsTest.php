<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiSchoolLimit;
use App\Models\Ai\AiSettingsOperation;
use App\Models\Ai\AiUsage;
use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Ai\Operations\AiPlatformCosts;
use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class AiOperationsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->set(1);
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
    }

    private function data(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'paused' => false, 'limits' => ['school_daily_tokens' => 1000, 'actor_daily_cents' => 10]];
    }

    private function usage(?string $currency = 'USD'): AiUsage
    {
        return AiUsage::create(['user_id' => $this->schoolA->ids['tara_user'], 'feature' => 'synthetic_metering', 'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic'), 'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'completed', 'used_units' => 100, 'cost_cents' => 3, 'reserved_cost_cents' => 2, 'reserved_units' => 50, 'billing_currency' => $currency, 'inference_output' => ['output' => 'Private learner body must not enter cost report'], 'inference_sources' => ['private-source']]);
    }

    private function grant(User $actor): void
    {
        PlatformStaffIdentity::create(['user_id' => $actor->id, 'active' => true]);
        PlatformAiCostGrant::create(['user_id' => $actor->id, 'active' => true, 'expires_at' => now()->addHour()]);
    }

    public function test_school_caps_are_versioned_idempotent_and_never_raise_server_allowance(): void
    {
        $service = app(AiSchoolControls::class);
        $data = $this->data();
        $this->assertSame(['revision' => 1], $service->save($this->superAdmin(), $data));
        $this->assertSame(['revision' => 1], $service->save($this->superAdmin(), $data));
        $this->assertSame(1, AiSettingsOperation::count());
        $this->assertSame(['school_daily_tokens' => 500, 'actor_daily_cents' => 10], $service->apply(['school_daily_tokens' => 500, 'actor_daily_cents' => 25]));
        $this->expectException(HttpException::class);
        $service->save($this->superAdmin(), [...$data, 'limits' => ['school_daily_tokens' => 2000]]);
    }

    public function test_school_and_account_pins_precede_malformed_input_and_teacher_cannot_manage(): void
    {
        config(['ai.inference.features' => []]); // Exercise the explicit empty catalogue, independent of registered disabled features.
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/ai-settings')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('No inference feature has been configured');
        $this->post('/ai-settings', ['expected_school_id' => 2, 'expected_account_id' => $admin->id, 'limits' => 'malformed'])->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/ai-settings')->assertForbidden();
        $this->assertSame(0, AiSchoolLimit::count());
    }

    public function test_clear_override_remains_platform_disabled_and_keys_never_appear_in_ui(): void
    {
        config(['ai.inference.enabled' => false, 'ai.providers.openai.key' => 'synthetic-private-key-never-render']);
        app(AiSchoolControls::class)->save($this->superAdmin(), [...$this->data(), 'limits' => []]);
        $this->assertSame(['school_daily_tokens' => 200], app(AiSchoolControls::class)->apply(['school_daily_tokens' => 200]));
        $this->assertFalse(config('ai.inference.enabled'));
        $this->actingAs($this->superAdmin())->get('/ai-settings')->assertOk()->assertDontSee('synthetic-private-key-never-render');
    }

    public function test_platform_identity_and_cost_grant_are_both_required_and_never_grant_school_controls(): void
    {
        $actor = $this->superAdmin();
        $this->usage();
        $this->actingAs($actor)->get('/platform/ai-costs')->assertForbidden();
        PlatformStaffIdentity::create(['user_id' => $actor->id, 'active' => true]);
        $this->get('/platform/ai-costs')->assertForbidden();
        PlatformAiCostGrant::create(['user_id' => $actor->id, 'active' => true, 'expires_at' => now()->addHour()]);
        $this->get('/platform/ai-costs')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('School AI costs')->assertDontSee('Private learner body')->assertDontSee('private-source');
        $this->get('/ai-settings')->assertForbidden();
        $this->get('/ai-flags')->assertForbidden();
    }

    public function test_costs_separate_currency_and_reservations_and_include_all_schools_without_private_content(): void
    {
        $actor = $this->superAdmin();
        $this->grant($actor);
        $this->usage('USD');
        $this->usage(null);
        app(CurrentSchool::class)->run(2, fn () => $this->usage('ZAR'));
        $report = app(AiPlatformCosts::class)->report($actor, now()->toDateString(), now()->toDateString());
        $this->assertCount(3, $report['rows']);
        foreach ($report['rows'] as $row) {
            $this->assertSame(3, (int) $row['cost_cents']);
            $this->assertSame(2, (int) $row['reserved_cost_cents']);
            $this->assertSame(100, (int) $row['used_tokens']);
            $this->assertSame(50, (int) $row['reserved_tokens']);
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertFalse(app(CurrentSchool::class)->isAcrossSchools());
        $this->assertStringNotContainsString('Private learner body', json_encode($report));
        $this->assertStringNotContainsString('user_id', json_encode($report));
        $this->assertStringNotContainsString('quarantined', json_encode($report));
    }

    public function test_expiry_revocation_and_impersonation_remove_global_cost_access(): void
    {
        $actor = $this->superAdmin();
        $this->grant($actor);
        $this->actingAs($actor)->get('/platform/ai-costs')->assertOk();
        $this->travel(2)->hours();
        $this->get('/platform/ai-costs')->assertForbidden();
        PlatformAiCostGrant::where('user_id', $actor->id)->update(['expires_at' => now()->addHour()]);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        try {
            app(AiPlatformCosts::class)->actor($actor);
            $this->fail('Impersonation obtained cost access.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        app(CurrentSchool::class)->set(1);
        PlatformStaffIdentity::where('user_id', $actor->id)->update(['active' => false]);
        $this->get('/platform/ai-costs')->assertForbidden();
    }

    public function test_console_grant_requires_an_existing_platform_identity_and_expires(): void
    {
        $actor = $this->superAdmin();
        $this->artisan('platform:ai-cost-access', ['user_id' => $actor->id])->assertFailed();
        PlatformStaffIdentity::create(['user_id' => $actor->id, 'active' => true]);
        $this->artisan('platform:ai-cost-access', ['user_id' => $actor->id, '--hours' => 2])->assertSuccessful();
        $this->assertTrue(PlatformAiCostGrant::firstOrFail()->active);
        $this->artisan('platform:ai-cost-access', ['user_id' => $actor->id, '--revoke' => true])->assertSuccessful();
        $this->assertFalse(PlatformAiCostGrant::firstOrFail()->active);
    }

    private function withAppliedClock(string $zone, callable $check): void
    {
        $timezone = date_default_timezone_get();
        $configured = config('app.timezone');
        $previous = now()->copy();
        try {
            config(['app.timezone' => $zone]);
            date_default_timezone_set($zone);
            $this->travelTo(CarbonImmutable::parse('2026-02-10 12:00:00', 'UTC')->setTimezone($zone));
            $check();
        } finally {
            config(['app.timezone' => $configured]);
            date_default_timezone_set($timezone);
            $this->travelTo($previous);
        }
    }

    public function test_expired_cost_grant_cannot_revive_in_a_negative_timezone(): void
    {
        $actor = $this->superAdmin();
        $this->grant($actor);
        PlatformAiCostGrant::where('user_id', $actor->id)->update(['expires_at' => '2026-02-10 11:00:00']);
        $this->withAppliedClock('Pacific/Honolulu', function () use ($actor): void {
            try {
                app(AiPlatformCosts::class)->actor($actor);
                $this->fail('An expired UTC cost grant revived under the applied clock.');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
            $this->assertSame('2026-02-10T11:00:00.000000Z', PlatformAiCostGrant::firstOrFail()->expires_at->toISOString());
        });
    }

    public function test_valid_cost_grant_survives_a_positive_timezone(): void
    {
        $actor = $this->superAdmin();
        $this->grant($actor);
        PlatformAiCostGrant::where('user_id', $actor->id)->update(['expires_at' => '2026-02-10 12:30:00']);
        $this->withAppliedClock('Pacific/Kiritimati', function () use ($actor): void {
            $this->assertSame($actor->id, app(AiPlatformCosts::class)->actor($actor)->id);
            $this->assertSame('2026-02-10T12:30:00.000000Z', PlatformAiCostGrant::firstOrFail()->expires_at->toISOString());
        });
    }

    public function test_console_cost_expiry_is_persisted_as_utc_from_an_applied_clock(): void
    {
        $actor = $this->superAdmin();
        PlatformStaffIdentity::create(['user_id' => $actor->id, 'active' => true]);
        $this->withAppliedClock('Pacific/Honolulu', function () use ($actor): void {
            $this->artisan('platform:ai-cost-access', ['user_id' => $actor->id, '--hours' => 2])->assertSuccessful();
            $grant = PlatformAiCostGrant::firstOrFail();
            $this->assertSame('2026-02-10 14:00:00', $grant->getRawOriginal('expires_at'));
            $this->assertSame('2026-02-10T14:00:00.000000Z', $grant->expires_at->toISOString());
        });
    }
}
