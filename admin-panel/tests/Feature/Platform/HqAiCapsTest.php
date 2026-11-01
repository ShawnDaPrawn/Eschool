<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Ai\AiSchoolLimit;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Ai\Operations\PlatformAiCaps;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * AI spending caps set in HQ (owner decision, 31 October 2026): Owners edit
 * each feature's daily caps at /saas/ai; the config/ai.php values are the
 * defaults until then; a school's own lower limit still applies on top and is
 * never overwritten. engineering/specs/platform/hq-ai.md.
 */
class HqAiCapsTest extends TestCase
{
    use UsesTwoSchools;

    private const REASON = 'Raised for the term four pilot budget.';

    private const SECRET = 'sk-synthetic-provider-key-never-audited';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        DB::table('schools')->update(['timezone' => 'Africa/Johannesburg']);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00', 'Africa/Johannesburg'));
        config(['ai.providers.openai.key' => self::SECRET]);
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-ai-caps-password-418!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** What the gateway uses inside a school: HQ's caps over the defaults, then the school's own lower limits. */
    private function inForce(int $schoolId, string $feature): array
    {
        return app(CurrentSchool::class)->run($schoolId, fn (): array => array_intersect_key(
            app(AiSchoolControls::class)->apply(app(PlatformAiCaps::class)->apply($feature, (array) config('ai.inference.features.'.$feature))),
            PlatformAiCaps::MAXIMUM));
    }

    /** @return array<string, mixed> */
    private function written(): array
    {
        return [DB::table('platform_ai_caps')->orderBy('id')->get()->toArray(), DB::table('platform_ai_cap_changes')->count(),
            AuditLog::query()->where('action', 'platform_ai_caps_changed')->count(), DB::table('ai_school_limits')->orderBy('id')->get()->toArray()];
    }

    public function test_owner_sets_caps_that_apply_from_the_next_request_with_history_and_audit(): void
    {
        $owner = $this->staff('owner');
        $this->actingAs($owner);
        $this->get('/saas/ai')->assertOk()->assertSee('Spending caps')->assertSee('Save caps for School Assistant')->assertSee('cents, USD')
            ->assertSee('No cap has been changed in HQ');
        $this->assertSame(1000, $this->inForce(1, 'school_assistant')['school_daily_cents']);

        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '2500', 'actor_daily_cents' => '75', 'learner_daily_cents' => '50', 'reason' => self::REASON])
            ->assertRedirect('/saas/ai#caps')->assertSessionHasNoErrors()->assertSessionHas('success', 'AI spending caps saved. They apply from the next request.');

        // In force at once; the learner cap was submitted at its default, so it stays the default.
        $this->assertSame(['school_daily_cents' => 2500, 'actor_daily_cents' => 75, 'learner_daily_cents' => 50], $this->inForce(1, 'school_assistant'));
        $this->assertSame(['actor_daily_cents' => 75, 'school_daily_cents' => 2500], DB::table('platform_ai_caps')->where('feature', 'school_assistant')->orderBy('cap')->pluck('cents', 'cap')->map(fn ($c) => (int) $c)->all());
        $history = DB::table('platform_ai_cap_changes')->orderBy('id')->get();
        $this->assertSame([['actor_daily_cents', null, 75, 50], ['school_daily_cents', null, 2500, 1000]],
            $history->sortBy('cap')->map(fn ($r) => [$r->cap, $r->old_cents, (int) $r->new_cents, (int) $r->default_cents])->values()->all());
        $this->assertSame([['USD', self::REASON, $owner->id]], $history->map(fn ($r) => [$r->currency, $r->reason, (int) $r->changed_by])->unique()->values()->all());
        // Other features keep their defaults.
        $this->assertSame(1000, $this->inForce(1, 'exam_coach')['school_daily_cents']);

        $page = $this->get('/saas/ai')->assertOk()->assertSee('US$ 25.00')->assertSee('set in HQ')->assertSee(self::REASON);
        $this->assertSame(2500, $page->viewData('features')['school_assistant']['caps']['school_cents']);

        // A blank cap goes back to the default; the same values again change nothing.
        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '', 'actor_daily_cents' => '75', 'reason' => 'Back to the platform default.'])->assertSessionHasNoErrors();
        $this->assertSame(1000, $this->inForce(1, 'school_assistant')['school_daily_cents']);
        $this->assertSame([2500, null], [(int) DB::table('platform_ai_cap_changes')->orderByDesc('id')->value('old_cents'), DB::table('platform_ai_cap_changes')->orderByDesc('id')->value('new_cents')]);
        $before = $this->written();
        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '1000', 'actor_daily_cents' => '75', 'reason' => self::REASON])
            ->assertSessionHas('success', 'Nothing changed: those caps were already in force.');
        $this->assertEquals($before, $this->written());

        // The tutor is personal: its per-learner cap is set here, and it has no school cap.
        $this->put('/saas/ai/caps', ['feature' => 'tutor', 'learner_daily_cents' => '20', 'reason' => self::REASON])->assertSessionHasNoErrors();
        $this->assertSame(20, $this->inForce(1, 'tutor')['learner_daily_cents']);

        $audit = AuditLog::query()->where('action', 'platform_ai_caps_changed')->orderBy('id')->get();
        $this->assertCount(3, $audit);
        $this->assertNull($audit->first()->school_id);
        $this->assertSame((int) $owner->id, (int) $audit->first()->actor_id);
        $this->assertSame(Arr::sortRecursive(['feature' => 'school_assistant', 'school_daily_cents' => 1000, 'actor_daily_cents' => 50]), Arr::sortRecursive($audit->first()->before));
        $this->get('/saas/audit')->assertOk()->assertSee('AI spending caps changed')->assertSee('school_daily_cents: 1000 → 2500');
    }

    public function test_invalid_caps_are_refused_and_nothing_is_written(): void
    {
        $this->actingAs($this->staff('owner'));
        $before = $this->written();
        foreach ([
            ['school_daily_cents' => '-1'], ['school_daily_cents' => '12.5'], ['school_daily_cents' => 'ten'],
            ['school_daily_cents' => (string) (PlatformAiCaps::MAXIMUM['school_daily_cents'] + 1)], ['actor_daily_cents' => '100001'],
        ] as $bad) {
            $this->putJson('/saas/ai/caps', ['feature' => 'school_assistant', 'reason' => self::REASON, ...$bad])->assertStatus(422)->assertJsonValidationErrors(array_keys($bad));
        }
        $this->putJson('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => 2000])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->putJson('/saas/ai/caps', ['feature' => 'unknown_feature', 'school_daily_cents' => 2000, 'reason' => self::REASON])->assertStatus(422)->assertJsonValidationErrors('feature');
        // The tutor has no school cap to set.
        $this->putJson('/saas/ai/caps', ['feature' => 'tutor', 'school_daily_cents' => 2000, 'reason' => self::REASON])->assertStatus(422)->assertJsonValidationErrors('school_daily_cents');
        // Zero is a cap: that budget is closed.
        $this->assertEquals($before, $this->written());
        $this->putJson('/saas/ai/caps', ['feature' => 'school_assistant', 'actor_daily_cents' => 0, 'reason' => self::REASON])->assertRedirect();
        $this->assertSame(0, $this->inForce(1, 'school_assistant')['actor_daily_cents']);
    }

    public function test_every_role_except_owner_is_refused_and_nothing_is_written(): void
    {
        $before = $this->written();
        foreach (['operations', 'support', 'finance', 'content', 'sales'] as $role) {
            $staff = $this->staff($role);
            $this->actingAs($staff);
            if (in_array($role, ['operations', 'support'], true)) {
                $this->get('/saas/ai')->assertOk()->assertSee('Spending caps')->assertDontSee('Save caps for');
            }
            $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '2500', 'reason' => self::REASON])->assertForbidden();
            $this->putJson('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => 2500, 'reason' => self::REASON])->assertForbidden();
            try {
                app(PlatformAiCaps::class)->save(['feature' => 'school_assistant', 'school_daily_cents' => 2500, 'reason' => self::REASON], $staff);
                $this->fail('The service saved caps for '.$role);
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertEquals($before, $this->written());
        $this->assertSame(1000, $this->inForce(1, 'school_assistant')['school_daily_cents']);
    }

    public function test_school_accounts_are_refused(): void
    {
        $before = $this->written();
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->schoolA->ids['tara_user'])] as $schoolUser) {
            $this->actingAs($schoolUser);
            $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '2500', 'reason' => self::REASON])->assertForbidden();
            try {
                app(PlatformAiCaps::class)->save(['feature' => 'school_assistant', 'school_daily_cents' => 2500, 'reason' => self::REASON], $schoolUser);
                $this->fail('A school account saved platform caps.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertEquals($before, $this->written());
    }

    public function test_the_lower_of_hq_and_the_schools_own_limit_applies_and_hq_never_overwrites_a_school(): void
    {
        // School A set its own lower limits at /ai-settings; School B set none.
        app(CurrentSchool::class)->run(1, fn () => AiSchoolLimit::create(['revision' => 3, 'paused' => false, 'limits' => ['school_daily_cents' => 600, 'learner_daily_cents' => 20]]));
        $schoolRows = fn (): array => DB::table('ai_school_limits')->orderBy('id')->get()->toArray();
        $limits = $schoolRows();
        $this->actingAs($this->staff('owner'));

        // HQ raises the ceiling: School A's own 600 is lower and still applies; School B gets HQ's.
        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '2000', 'learner_daily_cents' => '80', 'reason' => self::REASON])->assertSessionHasNoErrors();
        $this->assertSame([600, 20], [$this->inForce(1, 'school_assistant')['school_daily_cents'], $this->inForce(1, 'school_assistant')['learner_daily_cents']]);
        $this->assertSame([2000, 80], [$this->inForce(2, 'school_assistant')['school_daily_cents'], $this->inForce(2, 'school_assistant')['learner_daily_cents']]);
        $schools = collect($this->get('/saas/ai')->viewData('schools'))->keyBy('id');
        $this->assertSame(['US$ 6.00', 'US$ 20.00'], [$schools[1]['cap'], $schools[2]['cap']]);

        // HQ lowers it below School A's own limit: HQ's is now the lower one, for both schools.
        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '400', 'learner_daily_cents' => '80', 'reason' => 'Lowered while costs are reviewed.'])->assertSessionHasNoErrors();
        $this->assertSame([400, 400], [$this->inForce(1, 'school_assistant')['school_daily_cents'], $this->inForce(2, 'school_assistant')['school_daily_cents']]);

        // Neither school's own setting was touched, and School A's own limit never reached School B.
        $this->assertEquals($limits, $schoolRows());
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => AiSchoolLimit::query()->count()));
        $this->assertSame(['school_daily_cents' => 600, 'learner_daily_cents' => 20], app(CurrentSchool::class)->run(1, fn () => AiSchoolLimit::query()->firstOrFail()->limits));
    }

    public function test_the_audit_row_holds_no_secrets(): void
    {
        $this->actingAs($this->staff('owner'));
        $this->put('/saas/ai/caps', ['feature' => 'school_assistant', 'school_daily_cents' => '2500', 'reason' => self::REASON])->assertSessionHasNoErrors();

        $row = AuditLog::query()->where('action', 'platform_ai_caps_changed')->sole();
        // Audit JSON objects have no key order; still require the exact field set.
        $beforeKeys = array_keys($row->before);
        $afterKeys = array_keys($row->after);
        sort($beforeKeys);
        sort($afterKeys);
        $this->assertSame(['feature', 'school_daily_cents'], $beforeKeys);
        $this->assertSame(['changed_by', 'currency', 'feature', 'reason', 'school_daily_cents'], $afterKeys);
        $raw = json_encode([$row->before, $row->after, $row->getAttributes()]);
        foreach ([self::SECRET, (string) config('app.key'), 'provider_class', 'model', 'prompt', 'key'] as $secret) {
            $this->assertStringNotContainsString($secret, (string) $raw);
        }
        $this->get('/saas/audit')->assertOk()->assertDontSee(self::SECRET);
        $this->get('/saas/ai')->assertOk()->assertDontSee(self::SECRET);
    }
}
