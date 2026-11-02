<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Ai\AiSchoolLimit;
use App\Models\Ai\AiUsage;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Ai\Operations\PlatformAiCaps;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * The Mastery tutor is a personal service, not a school service (owner
 * decision, 31 October 2026): its spend is neither checked against nor added
 * to the school's daily total, and its own per-learner cap (set in HQ) still
 * blocks. engineering/specs/platform/hq-ai.md.
 */
class TutorPersonalSpendTest extends TestCase
{
    use UsesPracticeScenario;

    private ?User $owner = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'tutor-protocol-test',
            'ai.inference.features.tutor.cents_per_million_tokens' => 1000, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only']);
        // A school (staff) feature beside the tutor, to measure the school's own allowance.
        config(['ai.inference.features.staff_probe' => [
            'enabled' => true, 'roles' => ['Teacher'], 'capabilities' => ['complete'],
            'provider' => 'test-only', 'provider_class' => PersonalSpendProbeProvider::class, 'moderator' => PersonalSpendProbeModerator::class,
            'model' => 'test-protocol', 'prompt' => 'foundation', 'maximum_output_tokens' => 256,
            'cents_per_million_tokens' => 1000, 'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000,
            'school_daily_cents' => 1000, 'actor_daily_cents' => 500,
        ]]);
        PersonalSpendProbeProvider::$calls = 0;
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_string($content) ? $content : $content[0]['text'], true, 16, JSON_THROW_ON_ERROR);

            return Http::response(['model' => 'tutor-protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode(
                ['subject_token' => $input['subject_token'], 'kind' => 'hint', 'text' => 'Use equal parts and check your working.', 'question' => 'Which operation would you try next?'])]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function tutorTurn(): array
    {
        $session = app(TutorSessions::class)->create($this->actor('asha'), $this->group, [$this->source], (string) Str::uuid());

        return app(TutorSessions::class)->send($this->actor('asha'), $session['id'], (string) Str::uuid(), 'How can I start this fractions exercise?', '', []);
    }

    private function probe(): ProviderResponse
    {
        return app(AiGateway::class)->complete($this->actor('tara'), new InferenceRequest('staff_probe', (string) Str::uuid(), 'foundation', 'Explain fractions.', ['content:synthetic:1']));
    }

    private function refusal(callable $call, string $reason): void
    {
        try {
            $call();
            $this->fail('Expected inference refusal.');
        } catch (AiUnavailable $e) {
            $this->assertSame($reason, $e->reason);
        }
    }

    /** A metered receipt today in School A. */
    private function spend(string $feature, int $cents, string $who = 'tara', ?int $learner = null): void
    {
        AiUsage::create(['user_id' => $this->actor($who)->id, 'learner_id' => $learner, 'feature' => $feature, 'provider' => 'openai', 'operation_id' => (string) Str::uuid(),
            'request_hash' => hash('sha256', Str::random()), 'usage_date' => now(app(CurrentSchool::class)->school()->timezone)->toDateString(), 'unit' => 'tokens',
            'state' => 'completed', 'used_units' => 100, 'reserved_units' => 0, 'cost_cents' => $cents, 'reserved_cost_cents' => 0, 'billing_currency' => 'USD']);
    }

    private function owner(): User
    {
        if ($this->owner !== null) {
            return $this->owner;
        }
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Owner', 'last_name' => 'Operator', 'email' => 'owner@platform.example', 'mobile' => '',
            'password' => 'Synthetic-tutor-caps-password-418!', 'status' => 1]));
        app(CurrentSchool::class)->acrossSchools(function () use ($user): void {
            PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
            PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => 'owner', 'expires_at' => now()->addHour()]);
        });

        return $this->owner = $user;
    }

    private function hqCaps(array $caps): void
    {
        $school = app(CurrentSchool::class)->id();
        $owner = $this->owner();
        app(CurrentSchool::class)->forget();
        app(PlatformAiCaps::class)->save(['feature' => 'tutor', ...$caps, 'reason' => 'Synthetic tutor cap for the test.'], $owner);
        app(CurrentSchool::class)->set($school);
    }

    public function test_a_school_at_its_cap_can_still_use_the_tutor_within_the_learner_cap(): void
    {
        // School A has spent its whole daily cap (1000 cents) on a school feature.
        $this->spend('school_assistant', 1000);
        $this->refusal(fn () => $this->probe(), 'school_daily_cap');

        $turn = $this->tutorTurn();
        $this->assertSame('completed', $turn['state']);
        $usage = AiUsage::query()->where('feature', 'tutor')->sole();
        $this->assertSame('completed', $usage->state);
        $this->assertGreaterThan(0, $usage->cost_cents);
        // Still at its cap: the tutor did not take or give anything from the school's day.
        $this->refusal(fn () => $this->probe(), 'school_daily_cap');
        $this->assertSame(0, PersonalSpendProbeProvider::$calls);
    }

    public function test_tutor_spend_does_not_move_the_schools_remaining_allowance(): void
    {
        $this->tutorTurn();
        $this->spend('tutor', 5000, 'asha', $this->schoolA->ids['asha']);
        $this->assertGreaterThan(5000, (int) AiUsage::query()->where('feature', 'tutor')->sum('cost_cents'));

        // A one-cent school cap: the tutor's 50 dollars today leave the whole cent free.
        config(['ai.inference.features.staff_probe.school_daily_cents' => 1]);
        $this->assertSame('A staff draft.', $this->probe()->output);
        // That cent is now spent by a school feature, so the next school request is refused.
        $this->refusal(fn () => $this->probe(), 'school_daily_cap');
        $this->assertSame(1, PersonalSpendProbeProvider::$calls);
        // School spend is what the school's cap is checked against: tutor receipts are not in it.
        $this->assertSame(1, (int) AiUsage::query()->whereNotIn('feature', PlatformAiCaps::personalFeatures())->sum('cost_cents'));
    }

    public function test_the_per_learner_tutor_cap_set_in_hq_still_blocks(): void
    {
        $learner = $this->schoolA->ids['asha'];
        // HQ closes the tutor for every learner: refused before any provider call.
        $this->hqCaps(['learner_daily_cents' => 0]);
        $turn = $this->tutorTurn();
        $this->assertSame(['failed', 'learner_daily_cap', null], [$turn['state'], $turn['failure_code'], $turn['response']]);
        Http::assertNothingSent();

        // HQ allows 10 cents a learner a day. School-feature spend for the learner is not tutor spend.
        $this->hqCaps(['learner_daily_cents' => 10]);
        $this->spend('exam_coach', 10, 'asha', $learner);
        $this->assertSame('completed', $this->tutorTurn()['state']);
        // The learner's own tutor spend reaches the cap: blocked.
        $this->spend('tutor', 10, 'asha', $learner);
        $this->assertSame('learner_daily_cap', $this->tutorTurn()['failure_code']);
    }

    public function test_the_schools_own_lower_learner_limit_applies_to_the_tutor_on_top_of_hq(): void
    {
        $this->hqCaps(['learner_daily_cents' => 500]);
        AiSchoolLimit::create(['revision' => 1, 'paused' => false, 'limits' => ['learner_daily_cents' => 0]]);
        $this->assertSame('learner_daily_cap', $this->tutorTurn()['failure_code']);
        AiSchoolLimit::query()->firstOrFail()->update(['limits' => ['learner_daily_cents' => 600]]);
        // HQ's 500 is now the lower of the two, and allows the turn.
        $this->assertSame('completed', $this->tutorTurn()['state']);
    }
}

class PersonalSpendProbeProvider implements InferenceProvider
{
    public static int $calls = 0;

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$calls++;

        return new ProviderResponse('A staff draft.', 100);
    }
}

class PersonalSpendProbeModerator implements InferenceModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        return [];
    }
}
