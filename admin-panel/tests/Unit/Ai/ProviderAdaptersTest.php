<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\DeepSeekProvider;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\ProviderTransport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProviderAdaptersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['ai.inference.enabled' => true]);
        foreach (['openai', 'anthropic', 'deepseek'] as $provider) {
            config(['ai.providers.'.$provider.'.qualified' => true, 'ai.providers.'.$provider.'.key' => 'test-only-credential']);
        }
    }

    private function fake(array|callable $callback): void
    {
        // Each case owns fresh stub responses; the bounded transport closes bodies.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($callback);
    }

    private function request(Capability $capability = Capability::Complete): ProviderRequest
    {
        return new ProviderRequest($capability, (string) Str::uuid(), 'protocol-test-model', str_repeat('a', 64), 'School draft instructions.', 'Explain fractions.', 128, 4096);
    }

    private function chat(string $stop = 'stop', int $tokens = 12): array
    {
        return ['model' => 'protocol-test-model', 'choices' => [['index' => 0, 'finish_reason' => $stop, 'message' => ['role' => 'assistant', 'content' => 'A school draft.']]],
            'usage' => ['prompt_tokens' => 20, 'completion_tokens' => $tokens, 'total_tokens' => 20 + $tokens]];
    }

    private function refusal(callable $callback, string $reason): void
    {
        try {
            $callback();
            $this->fail('Expected bounded provider refusal.');
        } catch (AiUnavailable $e) {
            $this->assertSame($reason, $e->reason);
            $this->assertStringNotContainsString('sensitive', $e->getMessage());
        }
    }

    public function test_real_provider_shapes_send_bounded_private_metadata_and_parse_usage(): void
    {
        $options = [];
        $this->fake(function ($request, $requestOptions) use (&$options) {
            $options[] = $requestOptions;
            if (str_contains($request->url(), 'anthropic')) {
                return Http::response(['model' => 'protocol-test-model', 'role' => 'assistant', 'stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'A school draft.']],
                    'usage' => ['input_tokens' => 20, 'output_tokens' => 12, 'cache_read_input_tokens' => 3, 'cache_creation_input_tokens' => 2]]);
            }

            return Http::response($this->chat());
        });
        foreach ([OpenAiProvider::class, AnthropicProvider::class, DeepSeekProvider::class] as $class) {
            $request = $this->request();
            $result = app($class)->infer($request);
            $this->assertSame('A school draft.', $result->output);
            $this->assertSame($class === AnthropicProvider::class ? 37 : 32, $result->tokens);
            Http::assertSent(fn ($sent) => $sent->hasHeader('Idempotency-Key', $request->operationId.'-inference')
                && $sent->hasHeader('X-Client-Request-Id', $request->operationId));
        }
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request['max_completion_tokens'] === 128 && $request['store'] === false && $request['user'] === str_repeat('a', 64));
        Http::assertSent(fn ($request) => $request->url() === 'https://api.anthropic.com/v1/messages'
            && $request['max_tokens'] === 128 && $request['metadata']['user_id'] === str_repeat('a', 64)
            && $request->hasHeader('anthropic-version', '2023-06-01'));
        Http::assertSent(fn ($request) => $request->url() === 'https://api.deepseek.com/chat/completions' && $request['max_tokens'] === 128);
        foreach ($options as $value) {
            $this->assertFalse($value['allow_redirects']);
            $this->assertTrue($value['verify']);
            $this->assertSame(15, $value['timeout']);
            $this->assertSame(3, $value['connect_timeout']);
        }
    }

    public function test_unqualified_or_disabled_providers_and_invalid_origins_never_send(): void
    {
        config(['ai.providers.openai.qualified' => false]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_not_qualified');
        config(['ai.providers.openai.qualified' => true, 'ai.inference.enabled' => false]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_not_qualified');
        config(['ai.inference.enabled' => true]);
        $this->refusal(fn () => app(ProviderTransport::class)->post('other', '/chat/completions', [], (string) Str::uuid()), 'provider_not_qualified');
        $this->refusal(fn () => app(ProviderTransport::class)->post('openai', 'https://attacker.invalid', [], (string) Str::uuid()), 'provider_not_qualified');
        config(['ai.providers.openai.key' => '']);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_not_qualified');
        Http::assertNothingSent();
    }

    public function test_direct_adapters_refuse_raw_identities_invalid_models_and_missing_token_bounds(): void
    {
        $valid = $this->request();
        foreach ([
            new ProviderRequest(Capability::Complete, $valid->operationId, 'test-model', 'A real name', 'system', 'input', 128, 4096),
            new ProviderRequest(Capability::Complete, $valid->operationId, '../model', $valid->anonymousActor, 'system', 'input', 128, 4096),
            new ProviderRequest(Capability::Complete, $valid->operationId, 'test-model', $valid->anonymousActor, 'system', 'input', 128, 128),
            new ProviderRequest(Capability::Complete, 'not-uuid', 'test-model', $valid->anonymousActor, 'system', 'input', 128, 4096),
        ] as $request) {
            $this->refusal(fn () => app(OpenAiProvider::class)->infer($request), 'provider_request_invalid');
        }
        Http::assertNothingSent();
    }

    public function test_truncated_tool_filtered_malformed_and_over_budget_completions_are_refused(): void
    {
        foreach (['length', 'tool_calls', 'content_filter', 'aborted'] as $stop) {
            $this->fake(['*' => Http::response($this->chat($stop))]);
            $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_protocol_invalid');
        }
        $this->fake(['*' => Http::response($this->chat('stop', 129))]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_usage_invalid');
        $data = $this->chat();
        $data['usage']['prompt_tokens'] = 4096;
        $this->fake(['*' => Http::response($data)]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_usage_invalid');
        $mismatch = $this->chat();
        $mismatch['model'] = 'unexpected-model';
        $this->fake(['*' => Http::response($mismatch)]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_protocol_invalid');
        unset($data['usage']);
        $this->fake(['*' => Http::response($data)]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_protocol_invalid');
    }

    public function test_transport_refuses_redirect_errors_invalid_json_and_oversized_responses_without_leaking_bodies(): void
    {
        foreach ([302, 401, 429, 500] as $status) {
            $this->fake(['*' => Http::response('sensitive provider error detail', $status, ['Location' => 'https://attacker.invalid'])]);
            $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_unavailable');
        }
        $this->fake(['*' => Http::response('sensitive invalid json')]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_protocol_invalid');
        $this->fake(['*' => Http::response(str_repeat('x', 1048577))]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_response_limit');
        $this->fake(fn () => throw new ConnectionException('sensitive timeout details'));
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request()), 'provider_unavailable');
    }

    public function test_embedding_shape_and_capability_limits_are_explicit(): void
    {
        $this->fake(['*' => Http::response(['model' => 'protocol-test-model', 'data' => [['index' => 0, 'embedding' => [0.5, -0.2, 1]]], 'usage' => ['total_tokens' => 7]])]);
        $this->assertSame([0.5, -0.2, 1], app(OpenAiProvider::class)->infer($this->request(Capability::Embed))->output);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/embeddings' && $request['encoding_format'] === 'float');
        $this->fake(['*' => Http::response(['model' => 'protocol-test-model', 'data' => [['index' => 0, 'embedding' => ['private text']]], 'usage' => ['total_tokens' => 7]])]);
        $this->refusal(fn () => app(OpenAiProvider::class)->infer($this->request(Capability::Embed)), 'provider_protocol_invalid');
        $this->refusal(fn () => app(DeepSeekProvider::class)->infer($this->request(Capability::Embed)), 'capability_unavailable');
        $this->refusal(fn () => app(AnthropicProvider::class)->infer($this->request(Capability::Vision)), 'media_policy_required');
    }

    public function test_anthropic_refuses_tools_truncation_and_unreported_cached_usage(): void
    {
        foreach ([
            ['model' => 'protocol-test-model', 'role' => 'assistant', 'stop_reason' => 'max_tokens', 'content' => [['type' => 'text', 'text' => 'truncated']], 'usage' => ['input_tokens' => 3, 'output_tokens' => 4]],
            ['model' => 'protocol-test-model', 'role' => 'assistant', 'stop_reason' => 'end_turn', 'content' => [['type' => 'tool_use', 'name' => 'private-tool']], 'usage' => ['input_tokens' => 3, 'output_tokens' => 4]],
            ['model' => 'protocol-test-model', 'role' => 'assistant', 'stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'draft']], 'usage' => ['input_tokens' => 3, 'output_tokens' => 4, 'cache_read_input_tokens' => 9999]],
        ] as $response) {
            $this->fake(['*' => Http::response($response)]);
            try {
                app(AnthropicProvider::class)->infer($this->request());
                $this->fail('Expected strict Anthropic refusal.');
            } catch (AiUnavailable $e) {
                $this->assertContains($e->reason, ['provider_protocol_invalid', 'provider_usage_invalid']);
            }
        }
    }

    public function test_moderation_has_separate_stage_receipts_and_malformed_results_fail_closed(): void
    {
        $request = $this->request();
        $context = new ModerationContext($request->operationId, $request->anonymousActor);
        $this->fake(['*' => Http::response(['results' => [['flagged' => false]]])]);
        $this->assertSame([], app(OpenAiModerator::class)->flags(Capability::Complete, 'input', 'Safe synthetic school text.', $context));
        Http::assertSent(fn ($sent) => $sent->url() === 'https://api.openai.com/v1/moderations' && $sent->hasHeader('Idempotency-Key', $request->operationId.'-input'));
        $this->fake(['*' => Http::response(['results' => [['flagged' => true]]])]);
        $this->assertSame(['provider_content_flag'], app(OpenAiModerator::class)->flags(Capability::Complete, 'output', 'Synthetic flagged text.', $context));
        Http::assertSent(fn ($sent) => $sent->hasHeader('Idempotency-Key', $request->operationId.'-output'));
        $this->fake(['*' => Http::response(['results' => [['flagged' => 'false']]])]);
        $this->refusal(fn () => app(OpenAiModerator::class)->flags(Capability::Complete, 'input', 'Text.', $context), 'moderation_invalid');
        $this->assertSame([], app(OpenAiModerator::class)->flags(Capability::Embed, 'output', [0.1, -0.5], $context));
    }
}
