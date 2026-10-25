<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiUsage;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Ai\Providers\DeepSeekProvider;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeatureInferenceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        FeatureProbePolicy::$allowed = true;
        FeatureProbeProvider::$calls = [];
        FeatureProbeProvider::$after = null;
        FeatureProbeProvider::$failure = false;
        FeatureProbeModerator::$flags = [];
        FeatureProbeModerator::$after = [];
        config(['ai.inference.enabled' => true, 'ai.inference.feature_policies.family_probe' => FeatureProbePolicy::class,
            'ai.inference.features.family_probe' => ['enabled' => true, 'roles' => ['Parent', 'Student', 'Teacher'], 'capabilities' => ['complete'],
                'provider' => 'synthetic-probe', 'provider_class' => FeatureProbeProvider::class, 'moderator' => FeatureProbeModerator::class,
                'model' => 'protocol-test-model', 'prompt' => 'foundation', 'maximum_output_tokens' => 256, 'cents_per_million_tokens' => 1000,
                'school_daily_tokens' => 100000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 500, 'learner_daily_cents' => 500]]);
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
    }

    private function actor(string $name = 'farid'): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function request(array $extra = [], ?string $operation = null): FeatureInferenceRequest
    {
        return new FeatureInferenceRequest('family_probe', $operation ?? (string) Str::uuid(), $extra + ['learner_id' => $this->schoolA->ids['asha'], 'subject_id' => $this->schoolA->ids['maths']]);
    }

    private function runProbe(?FeatureInferenceRequest $request = null, string $actor = 'farid'): ProviderResponse
    {
        return app(AiGateway::class)->runFeature($this->actor($actor), Capability::Complete, $request ?? $this->request());
    }

    private function refusal(callable $run, string $reason): void
    {
        try {
            $run();
            $this->fail('Expected bounded feature refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame($reason, $error->reason);
        }
    }

    public function test_registered_own_family_scope_meters_learner_and_replays_only_the_exact_intent(): void
    {
        $request = $this->request();
        $this->assertSame('Synthetic worked guidance.', $this->runProbe($request)->output);
        $this->assertSame('Synthetic worked guidance.', $this->runProbe($request)->output);
        $this->assertCount(1, FeatureProbeProvider::$calls);
        $usage = AiUsage::firstOrFail();
        $this->assertSame($this->schoolA->ids['asha'], (int) $usage->learner_id);
        $this->assertSame(100, $usage->used_units);
        $outbound = FeatureProbeProvider::$calls[0];
        $this->assertSame(hash_hmac('sha256', '1:learner:'.$this->schoolA->ids['asha'], config('app.key')), $outbound->anonymousActor);
        $this->assertLessThanOrEqual(10, $outbound->deadline->remainingSeconds());
        $this->assertSame(256, $outbound->maximumTokens);
        $this->refusal(fn () => $this->runProbe($this->request(['unused_input' => 'changed intent'], $request->operationId)), 'operation_conflict');
        $this->assertCount(1, FeatureProbeProvider::$calls);
    }

    public function test_registry_default_deny_and_registered_features_cannot_use_the_legacy_staff_bypass(): void
    {
        config(['ai.inference.feature_policies' => []]);
        $this->refusal(fn () => $this->runProbe(), 'feature_policy_unavailable');
        config(['ai.inference.feature_policies.family_probe' => FeatureProbePolicy::class]);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor('tara'), new InferenceRequest('family_probe', (string) Str::uuid(), 'foundation', 'Staff bypass')), 'feature_policy_required');
        $this->assertCount(0, FeatureProbeProvider::$calls);
        $this->assertSame(0, AiUsage::count());
    }

    public function test_central_scope_guard_refuses_other_owner_school_subject_role_and_unapproved_learner(): void
    {
        foreach ([[$this->request(), 'gina'], [$this->request(['learner_id' => $this->schoolB->ids['asha']]), 'farid'],
            [$this->request(['subject_id' => $this->schoolB->ids['maths']]), 'farid'], [$this->request(), 'tara'],
            [$this->request(['learner_id' => $this->schoolA->ids['ben']]), 'farid'], [$this->request(['no_learner' => true]), 'asha']] as [$request, $actor]) {
            try {
                $this->runProbe($request, $actor);
                $this->fail('The unapproved or foreign scope must not dispatch.');
            } catch (HttpException|ModelNotFoundException $error) {
                $this->assertTrue($error instanceof ModelNotFoundException || in_array($error->getStatusCode(), [403, 404], true));
            }
        }
        $this->refusal(fn () => $this->runProbe($this->request(['wrong_scope_school' => 2])), 'feature_scope_invalid');
        $this->refusal(fn () => $this->runProbe($this->request(['wrong_scope_actor' => 999])), 'feature_scope_invalid');
        $this->assertCount(0, FeatureProbeProvider::$calls);
        $this->assertSame(0, AiUsage::count());
    }

    public function test_approved_student_uses_own_scope_and_shared_learner_cap_across_guardian_accounts(): void
    {
        $this->runProbe(actor: 'asha');
        config(['ai.inference.features.family_probe.learner_daily_tokens' => 100]);
        $this->refusal(fn () => $this->runProbe(), 'learner_daily_cap');
        config(['ai.inference.features.family_probe.learner_daily_tokens' => 50000, 'ai.inference.features.family_probe.learner_daily_cents' => 1]);
        $this->refusal(fn () => $this->runProbe(), 'learner_daily_cap');
        $this->assertCount(1, FeatureProbeProvider::$calls);
        $this->assertSame(1, AiUsage::count());
    }

    public function test_source_withdrawal_blocks_replay_and_approval_withdrawal_during_provider_suppresses_output(): void
    {
        $request = $this->request();
        $this->runProbe($request);
        FeatureProbePolicy::$allowed = false;
        $this->refusal(fn () => $this->runProbe($request), 'feature_source_unavailable');
        FeatureProbePolicy::$allowed = true;
        FeatureProbeProvider::$after = fn () => LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->refusal(fn () => $this->runProbe(), 'inference_unavailable');
        $usage = AiUsage::latest('id')->firstOrFail();
        $this->assertNull($usage->inference_output);
        $this->assertSame(100, $usage->used_units);
    }

    public function test_policy_rechecks_after_moderation_restore_context_and_never_write_foreign_flags(): void
    {
        FeatureProbeModerator::$flags = ['input' => ['safeguarding_review']];
        FeatureProbeModerator::$after = ['input' => fn () => app(CurrentSchool::class)->set(2)];
        $this->refusal(fn () => $this->runProbe(), 'inference_context_changed');
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertDatabaseCount('ai_flags', 0);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
        $this->assertCount(0, FeatureProbeProvider::$calls);
    }

    public function test_unqualified_real_provider_preflight_never_moderates_or_charges_inference(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        config(['ai.inference.features.family_probe.provider' => 'deepseek', 'ai.inference.features.family_probe.provider_class' => DeepSeekProvider::class,
            'ai.inference.features.family_probe.moderator' => OpenAiModerator::class,
            'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'test-only', 'ai.providers.deepseek.qualified' => false]);
        $this->refusal(fn () => $this->runProbe(), 'provider_not_qualified');
        Http::assertNothingSent();
        $usage = AiUsage::firstOrFail();
        foreach (['used_units', 'reserved_units', 'cost_cents', 'reserved_cost_cents'] as $field) {
            $this->assertSame(0, $usage->$field);
        }
        $this->assertSame('failed', $usage->state);
    }

    public function test_qualification_revoked_after_moderation_settles_known_zero_but_unknown_http_failure_keeps_reservation(): void
    {
        config(['ai.inference.features.family_probe.provider' => 'deepseek', 'ai.inference.features.family_probe.provider_class' => DeepSeekProvider::class,
            'ai.inference.features.family_probe.moderator' => OpenAiModerator::class, 'ai.providers.openai.qualified' => true,
            'ai.providers.openai.key' => 'test-only', 'ai.providers.deepseek.qualified' => true, 'ai.providers.deepseek.key' => 'test-only']);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/moderations')) {
                config(['ai.providers.deepseek.qualified' => false]);

                return Http::response(['results' => [['flagged' => false]]]);
            }
            $this->fail('A locally unqualified inference provider must not send.');
        });
        $this->refusal(fn () => $this->runProbe(), 'provider_not_qualified');
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
        Http::assertSentCount(1);
        Http::swap(new Factory);
        config(['ai.providers.deepseek.qualified' => true]);
        Http::preventStrayRequests();
        $attempts = [];
        Http::fake(function ($request) use (&$attempts) {
            $attempts[] = $request->url();
            if (str_contains($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            throw new ConnectionException('Synthetic unknown outcome');
        });
        $this->refusal(fn () => $this->runProbe(), 'provider_unavailable');
        $this->assertGreaterThan(100, AiUsage::latest('id')->firstOrFail()->used_units);
        // Failed fake requests are not recorded as completed responses by Laravel.
        $this->assertCount(2, $attempts);
        $this->assertSame(1, count(array_filter($attempts, fn ($url) => str_contains($url, 'deepseek.com'))));
    }

    public function test_late_feature_result_cannot_escape_the_overall_deadline(): void
    {
        FeatureProbeProvider::$after = fn () => usleep(1100000);
        $this->refusal(fn () => $this->runProbe($this->request(['timeout_ms' => 1000])), 'inference_deadline_exceeded');
        $this->assertCount(1, FeatureProbeProvider::$calls);
        $usage = AiUsage::firstOrFail();
        $this->assertNull($usage->inference_output);
        $this->assertSame(100, $usage->used_units);
    }

    public function test_unknown_dispatch_is_fully_charged_but_valid_quarantined_result_uses_known_usage(): void
    {
        FeatureProbeProvider::$failure = true;
        $this->refusal(fn () => $this->runProbe(), 'inference_unavailable');
        $this->assertGreaterThan(100, AiUsage::firstOrFail()->used_units);
        FeatureProbeProvider::$failure = false;
        FeatureProbeModerator::$flags = ['output' => ['safeguarding_review']];
        $this->refusal(fn () => $this->runProbe(), 'inference_quarantined');
        $usage = AiUsage::latest('id')->firstOrFail();
        $this->assertSame('quarantined', $usage->state);
        $this->assertSame(100, $usage->used_units);
        $this->assertNull($usage->inference_output);
    }

    public function test_role_revocation_and_global_contexts_never_dispatch_or_expose_cached_output(): void
    {
        $request = $this->request();
        $this->runProbe($request);
        SchoolMembership::query()->where('user_id', $this->actor()->id)->update(['status' => 'ended']);
        try {
            $this->runProbe($request);
            $this->fail('A stale actor must not replay private output.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertCount(1, FeatureProbeProvider::$calls);
        app(CurrentSchool::class)->allowAcrossSchools();
        try {
            $this->runProbe();
            $this->fail('A global context is not a feature school scope.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        } finally {
            app(CurrentSchool::class)->set(1);
        }
    }
}

/** Deliberately minimal synthetic policy: central guards remain independently testable. */
class FeatureProbePolicy implements InferenceFeaturePolicy
{
    public static bool $allowed = true;

    public function feature(): string
    {
        return 'family_probe';
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        return new AuthorizedInferenceScope((int) ($request->payload['wrong_scope_school'] ?? app(CurrentSchool::class)->id()), (int) ($request->payload['wrong_scope_actor'] ?? $actor->id),
            new InferenceRequest('family_probe', $request->operationId, 'foundation', 'Explain fractions with worked guidance.', ['published:synthetic:v1']),
            ($request->payload['no_learner'] ?? false) ? null : (int) $request->payload['learner_id'], (int) $request->payload['subject_id'], (int) ($request->payload['timeout_ms'] ?? 10000));
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        if (! self::$allowed) {
            throw new AiUnavailable('feature_source_unavailable');
        }
    }
}

class FeatureProbeProvider implements InferenceProvider
{
    public static array $calls = [];

    public static mixed $after = null;

    public static bool $failure = false;

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$calls[] = $request;
        if (self::$failure) {
            throw new \RuntimeException('Synthetic unknown compute outcome');
        }
        if (self::$after !== null) {
            (self::$after)();
        }

        return new ProviderResponse('Synthetic worked guidance.', 100);
    }
}

class FeatureProbeModerator implements InferenceModerator
{
    public static array $flags = [];

    public static array $after = [];

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if (isset(self::$after[$direction])) {
            (self::$after[$direction])();
        }

        return self::$flags[$direction] ?? [];
    }
}
