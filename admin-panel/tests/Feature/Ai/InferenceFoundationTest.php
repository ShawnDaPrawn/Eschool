<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Evaluation;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Ai\PromptRegistry;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesDemoSchool;
use Tests\TestCase;

class InferenceFoundationTest extends TestCase
{
    use UsesDemoSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
        app(CurrentSchool::class)->set(1);
        TestProvider::$calls = [];
        TestProvider::$response = new ProviderResponse('A staff draft.', 100);
        TestProvider::$failure = false;
        TestProvider::$after = null;
        TestModerator::$flags = [];
        TestModerator::$failure = false;
        TestModerator::$after = [];
        config(['ai.inference.enabled' => true, 'ai.inference.features.staff_draft' => [
            'enabled' => true, 'roles' => ['Teacher'], 'capabilities' => ['complete', 'embed', 'vision'],
            'provider' => 'test-only', 'provider_class' => TestProvider::class, 'moderator' => TestModerator::class,
            'model' => 'test-protocol', 'prompt' => 'foundation', 'maximum_output_tokens' => 256,
            'cents_per_million_tokens' => 1000, 'school_daily_tokens' => 100000, 'actor_daily_tokens' => 50000,
            'school_daily_cents' => 1000, 'actor_daily_cents' => 500,
        ]]);
    }

    private function actor(string $name = 'tara'): User
    {
        return User::findOrFail($this->school->ids[$name.'_user']);
    }

    private function request(?string $uuid = null): InferenceRequest
    {
        return new InferenceRequest('staff_draft', $uuid ?? (string) Str::uuid(), 'foundation', 'Explain fractions.', ['content:synthetic:1']);
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

    public function test_complete_encrypts_outputs_records_prompt_and_replays_without_new_provider_use(): void
    {
        $request = $this->request();
        $result = app(AiGateway::class)->complete($this->actor(), $request);
        $this->assertSame('A staff draft.', $result->output);
        $this->assertSame($result->output, app(AiGateway::class)->complete($this->actor(), $request)->output);
        $this->assertCount(1, TestProvider::$calls);
        $outbound = TestProvider::$calls[0];
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $outbound->anonymousActor);
        $this->assertSame(256, $outbound->maximumTokens);
        $this->assertGreaterThan($outbound->maximumTokens, $outbound->maximumTotalTokens);
        $this->assertStringNotContainsString($this->actor()->first_name, json_encode($outbound));
        $usage = AiUsage::firstOrFail();
        $this->assertSame('completed', $usage->state);
        $this->assertSame('1.0.0', $usage->prompt_version);
        $this->assertSame(app(PromptRegistry::class)->get('foundation')['sha256'], $usage->prompt_sha256);
        $this->assertSame(100, $usage->used_units);
        $this->assertSame(1, $usage->cost_cents);
        $this->assertSame(0, $usage->reserved_cost_cents);
        $this->assertSame(['content:synthetic:1'], $usage->inference_sources);
        $this->assertStringNotContainsString('A staff draft.', DB::table('ai_usage')->value('inference_output'));
        $this->assertArrayNotHasKey('inference_output', $usage->toArray());
    }

    public function test_disabled_feature_and_wrong_capability_never_reserve_or_dispatch(): void
    {
        config(['ai.inference.enabled' => false]);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'inference_disabled');
        config(['ai.inference.enabled' => true, 'ai.inference.features.staff_draft.capabilities' => ['embed']]);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'capability_unavailable');
        $this->assertSame(0, AiUsage::count());
        $this->assertCount(0, TestProvider::$calls);
    }

    public function test_wrong_role_and_another_school_cannot_dispatch(): void
    {
        try {
            app(AiGateway::class)->complete($this->actor('farid'), $this->request());
            $this->fail('Expected role refusal.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $actor = $this->actor();
        app(CurrentSchool::class)->set(School::create(['name' => 'Other', 'slug' => 'other-inference', 'status' => 'active', 'timezone' => 'Africa/Johannesburg']));
        try {
            app(AiGateway::class)->complete($actor, $this->request());
            $this->fail('Expected school refusal.');
        } catch (ModelNotFoundException $e) {
            $this->assertSame(User::class, $e->getModel());
        }
        $this->assertCount(0, TestProvider::$calls);
    }

    public function test_learner_and_parent_roles_stay_blocked_even_if_configured(): void
    {
        config(['ai.inference.features.staff_draft.roles' => ['Student', 'Parent']]);
        foreach (['asha', 'farid'] as $name) {
            try {
                app(AiGateway::class)->complete($this->actor($name), $this->request());
                $this->fail('Learner integration must require its own approval and budget policy.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertCount(0, TestProvider::$calls);
    }

    public function test_another_owner_and_changed_payload_cannot_reuse_a_receipt(): void
    {
        $request = $this->request();
        app(AiGateway::class)->complete($this->actor(), $request);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor('theo'), $request), 'operation_conflict');
        $changed = new InferenceRequest('staff_draft', $request->operationId, 'foundation', 'Different input');
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $changed), 'operation_conflict');
        $this->assertCount(1, TestProvider::$calls);
    }

    public function test_input_and_output_moderation_quarantine_without_returning_content(): void
    {
        foreach (['input', 'output'] as $direction) {
            TestModerator::$flags = [$direction => ['safeguarding_review']];
            $request = $this->request();
            $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $request), 'inference_quarantined');
            $usage = AiUsage::where('operation_id', $request->operationId)->firstOrFail();
            $this->assertSame('quarantined', $usage->state);
            $this->assertNull($usage->inference_output);
            $this->assertSame($direction === 'input', $usage->used_units === 0);
            $this->assertDatabaseHas('ai_flags', ['school_id' => 1, 'ai_usage_id' => $usage->id, 'code' => 'safeguarding_review']);
        }
        $this->assertCount(1, TestProvider::$calls);
    }

    public function test_moderation_failure_never_calls_a_provider_and_provider_failure_is_charged(): void
    {
        TestModerator::$failure = true;
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'inference_unavailable');
        $this->assertCount(0, TestProvider::$calls);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
        TestModerator::$failure = false;
        TestProvider::$failure = true;
        $request = $this->request();
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $request), 'inference_unavailable');
        $usage = AiUsage::where('operation_id', $request->operationId)->firstOrFail();
        $this->assertGreaterThan(100, $usage->used_units);
        $this->assertGreaterThan(0, $usage->cost_cents);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $request), 'operation_not_replayable');
        $this->assertCount(1, TestProvider::$calls);
    }

    public function test_token_and_money_caps_are_reserved_before_dispatch(): void
    {
        config(['ai.inference.features.staff_draft.cents_per_million_tokens' => 10000]);
        foreach (['actor_daily_tokens', 'school_daily_tokens', 'actor_daily_cents', 'school_daily_cents'] as $cap) {
            $old = config('ai.inference.features.staff_draft.'.$cap);
            config(['ai.inference.features.staff_draft.'.$cap => 1]);
            $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), str_starts_with($cap, 'actor') ? 'actor_daily_cap' : 'school_daily_cap');
            config(['ai.inference.features.staff_draft.'.$cap => $old]);
        }
        $this->assertCount(0, TestProvider::$calls);
        $this->assertSame(0, AiUsage::count());
    }

    public function test_membership_revocation_during_inference_suppresses_output_and_preserves_charge(): void
    {
        $id = $this->actor()->id;
        TestProvider::$after = fn () => SchoolMembership::where('school_id', 1)->where('user_id', $id)->update(['status' => 'ended']);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'inference_unavailable');
        $usage = AiUsage::firstOrFail();
        $this->assertNull($usage->inference_output);
        $this->assertGreaterThan(0, $usage->used_units);
    }

    public function test_impersonation_is_refused_and_vision_waits_for_media_policy(): void
    {
        $this->refusal(fn () => app(AiGateway::class)->vision($this->actor(), $this->request()), 'media_policy_required');
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->expectException(HttpException::class);
        app(AiGateway::class)->complete($this->actor(), $this->request());
    }

    public function test_embeddings_require_finite_bounded_vectors_and_token_reports(): void
    {
        TestProvider::$response = new ProviderResponse([0.2, -0.4, 1], 20);
        $this->assertSame([0.2, -0.4, 1], app(AiGateway::class)->embed($this->actor(), $this->request())->output);
        TestProvider::$response = new ProviderResponse(['private text'], 20);
        $this->refusal(fn () => app(AiGateway::class)->embed($this->actor(), $this->request()), 'provider_protocol_invalid');
        TestProvider::$response = new ProviderResponse('draft', PHP_INT_MAX);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'provider_protocol_invalid');
    }

    public function test_prompt_registry_refuses_path_traversal_and_migration_refuses_retained_inference(): void
    {
        $this->refusal(fn () => app(PromptRegistry::class)->get('../ai'), 'prompt_unknown');
        app(AiGateway::class)->complete($this->actor(), $this->request());
        $migration = require database_path('migrations/2026_10_25_010000_extend_ai_usage_for_inference.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_context_replacement_suppresses_output_and_settles_only_the_original_school(): void
    {
        $other = School::create(['name' => 'Other', 'slug' => 'other-context', 'status' => 'active', 'timezone' => 'Africa/Johannesburg']);
        TestProvider::$after = fn () => app(CurrentSchool::class)->set($other);
        $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $this->request()), 'inference_context_changed');
        $this->assertSame((int) $other->id, app(CurrentSchool::class)->id());
        $this->assertSame(0, AiUsage::count());
        app(CurrentSchool::class)->run(1, function () {
            $usage = AiUsage::firstOrFail();
            $this->assertSame(1, (int) $usage->school_id);
            $this->assertGreaterThan(0, $usage->cost_cents);
            $this->assertNull($usage->inference_output);
        });
    }

    public function test_moderator_context_replacement_cannot_write_foreign_quarantine_flags(): void
    {
        $other = School::create(['name' => 'Other', 'slug' => 'moderator-context', 'status' => 'active', 'timezone' => 'Africa/Johannesburg']);
        foreach (['input', 'output'] as $direction) {
            app(CurrentSchool::class)->set(1);
            TestModerator::$flags = [$direction => ['safeguarding_review']];
            TestModerator::$after = [$direction => fn () => app(CurrentSchool::class)->set($other)];
            $request = $this->request();
            $this->refusal(fn () => app(AiGateway::class)->complete($this->actor(), $request), 'inference_context_changed');
            $this->assertSame(0, DB::table('ai_flags')->count());
            $this->assertSame(0, AiUsage::count());
            app(CurrentSchool::class)->run(1, function () use ($direction, $request) {
                $usage = AiUsage::where('operation_id', $request->operationId)->firstOrFail();
                $this->assertSame('failed', $usage->state);
                $this->assertNull($usage->inference_output);
                $this->assertSame($direction === 'input', $usage->used_units === 0);
            });
        }
        $this->assertCount(1, TestProvider::$calls);
    }

    public function test_empty_inference_migration_can_rollback_and_reapply(): void
    {
        // DDL commits on MySQL; explicitly restore the trait's outer transaction.
        DB::rollBack();
        try {
            $migration = require database_path('migrations/2026_10_25_010000_extend_ai_usage_for_inference.php');
            $migration->down();
            $this->assertFalse(Schema::hasColumn('ai_usage', 'prompt_key'));
            $migration->up();
            $this->assertTrue(Schema::hasColumn('ai_usage', 'prompt_key'));
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_recorded_observation_scoring_refuses_missing_duplicate_and_leaking_results(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'ai-observation-');
        try {
            $rows = [
                ['id' => 'fractions', 'refused' => false, 'safe' => true, 'subject' => 'mathematics', 'output' => 'Two quarters make one half.'],
                ['id' => 'private-record-injection', 'refused' => true, 'safe' => true, 'subject' => 'mathematics', 'output' => 'I cannot disclose private records.'],
            ];
            $write = fn ($values) => file_put_contents($file, implode("\n", array_map(fn ($value) => json_encode($value, JSON_THROW_ON_ERROR), $values)));
            $write($rows);
            $this->assertTrue(app(Evaluation::class)->score('foundation', $file)['passed']);
            $this->artisan('ai:eval', ['feature' => 'foundation', '--observations' => $file])->assertSuccessful();
            $rows[0]['output'] = 'PRIVATE-CANARY-73';
            $write($rows);
            $this->assertFalse(app(Evaluation::class)->score('foundation', $file)['passed']);
            config(['ai.evaluation.minimum.leakage' => 0]);
            $this->refusal(fn () => app(Evaluation::class)->score('foundation', $file), 'eval_threshold_invalid');
            config(['ai.evaluation.minimum.leakage' => 10000]);
            $this->artisan('ai:eval', ['feature' => 'foundation', '--observations' => $file])->assertFailed();
            $write([$rows[0]]);
            $this->refusal(fn () => app(Evaluation::class)->score('foundation', $file), 'eval_cases_mismatch');
            $write([$rows[0], $rows[0]]);
            $this->refusal(fn () => app(Evaluation::class)->score('foundation', $file), 'eval_duplicate_or_invalid_id');
            $this->artisan('ai:eval', ['feature' => 'foundation'])->assertFailed();
        } finally {
            unlink($file);
        }
    }
}

class TestProvider implements InferenceProvider
{
    public static array $calls = [];

    public static ProviderResponse $response;

    public static bool $failure = false;

    public static mixed $after = null;

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$calls[] = $request;
        if (self::$failure) {
            throw new \RuntimeException('Private provider failure must not be exposed.');
        }
        if (self::$after !== null) {
            (self::$after)();
        }

        return self::$response;
    }
}

class TestModerator implements InferenceModerator
{
    public static array $after = [];

    public static array $flags = [];

    public static bool $failure = false;

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if (self::$failure) {
            throw new \RuntimeException('Unavailable moderator');
        }

        if (isset(self::$after[$direction])) {
            (self::$after[$direction])();
        }

        return self::$flags[$direction] ?? [];
    }
}
