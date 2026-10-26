<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\ResolvedVisionInput;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Ai\Inference\VisionMediaResolver;
use App\Services\Ai\Inference\VisionPhotoCropper;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class VisionInferenceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->set(1);
        VisionProbePolicy::$owner = $this->schoolA->ids['tara_user'];
        VisionProbePolicy::$allowed = true;
        VisionProbePolicy::$wrongHash = false;
        VisionProbeResolver::$swap = false;
        VisionProbeResolver::$calls = 0;
        $image = imagecreatetruecolor(32, 32);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $original = (string) ob_get_clean();
        imagedestroy($image);
        VisionProbeResolver::$image = app(VisionPhotoCropper::class)->crop('opaque-photo-1', $original, ['x' => 0, 'y' => 0, 'width' => 32, 'height' => 32], true);
        config(['ai.inference.enabled' => true, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-protocol-only',
            'ai.inference.feature_policies.vision_probe' => VisionProbePolicy::class, 'ai.inference.vision_resolvers.vision_probe' => VisionProbeResolver::class,
            'ai.inference.features.vision_probe' => ['enabled' => true, 'roles' => ['Teacher'], 'capabilities' => ['vision'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class, 'moderator' => OpenAiModerator::class,
                'model' => 'protocol-test-model', 'prompt' => 'foundation', 'maximum_output_tokens' => 256, 'cents_per_million_tokens' => 1000,
                'school_daily_tokens' => 100000, 'actor_daily_tokens' => 50000, 'school_daily_cents' => 1000, 'actor_daily_cents' => 500]]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn ($request) => Http::response($this->reply($request->url())));
    }

    private function reply(string $url): array
    {
        return str_ends_with($url, '/moderations') ? ['results' => [['flagged' => false]]]
            : ['model' => 'protocol-test-model', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'A bounded working hint.']]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 20, 'total_tokens' => 140]];
    }

    private function request(): FeatureInferenceRequest
    {
        return new FeatureInferenceRequest('vision_probe', (string) Str::uuid(), []);
    }

    private function inferProbe(?FeatureInferenceRequest $request = null): mixed
    {
        return app(AiGateway::class)->runFeature(User::findOrFail(VisionProbePolicy::$owner), Capability::Vision, $request ?? $this->request());
    }

    private function refuse(callable $run, string $reason): void
    {
        try {
            $run();
            $this->fail('Expected bounded vision refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame($reason, $error->reason);
        }
    }

    public function test_registered_private_vision_is_moderated_metered_and_replayed_without_storing_pixels(): void
    {
        $request = $this->request();
        $this->assertSame('A bounded working hint.', $this->inferProbe($request)->output);
        $this->assertSame('A bounded working hint.', $this->inferProbe($request)->output);
        Http::assertSentCount(3);
        Http::assertSent(fn ($sent) => str_ends_with($sent->url(), '/chat/completions') && $sent['max_completion_tokens'] === 256
            && str_starts_with($sent['messages'][1]['content'][1]['image_url']['url'], 'data:image/png;base64,')
            && $sent['messages'][1]['content'][1]['image_url']['detail'] === 'high');
        Http::assertSent(fn ($sent) => str_ends_with($sent->url(), '/moderations') && is_array($sent['input']) && $sent['input'][1]['type'] === 'image_url');
        $usage = AiUsage::firstOrFail();
        $this->assertSame(140, $usage->used_units);
        $this->assertSame(0, $usage->reserved_units);
        $this->assertStringNotContainsString(base64_encode(VisionProbeResolver::$image->bytes), json_encode(DB::table('ai_usage')->first()));
        $this->assertSame(['approved-photo:opaque-photo-1'], $usage->inference_sources);
        $this->assertSame(0, AiQuarantineSnapshot::count());
    }

    public function test_missing_media_registry_and_generic_staff_vision_fail_before_reservation(): void
    {
        config(['ai.inference.vision_resolvers' => []]);
        $this->refuse(fn () => $this->inferProbe(), 'media_policy_required');
        config(['ai.inference.feature_policies' => []]);
        $this->refuse(fn () => app(AiGateway::class)->vision(User::findOrFail(VisionProbePolicy::$owner), new InferenceRequest('vision_probe', (string) Str::uuid(), 'foundation', 'Raw image bypass')), 'media_policy_required');
        $this->assertSame(0, AiUsage::count());
        Http::assertNothingSent();
    }

    public function test_changed_manifest_or_resolver_tenant_never_dispatches(): void
    {
        VisionProbePolicy::$wrongHash = true;
        $this->refuse(fn () => $this->inferProbe(), 'vision_manifest_changed');
        VisionProbePolicy::$wrongHash = false;
        VisionProbeResolver::$swap = true;
        $this->refuse(fn () => $this->inferProbe(), 'inference_context_changed');
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, AiUsage::count());
        Http::assertNothingSent();
    }

    public function test_private_feature_refuses_other_owner_school_and_role_before_loading_pixels(): void
    {
        foreach ([['gina_user', 1], ['tara_user', 2], ['asha_user', 1]] as [$actor, $school]) {
            try {
                app(CurrentSchool::class)->run($school, fn () => app(AiGateway::class)->runFeature(User::findOrFail($this->schoolA->ids[$actor]), Capability::Vision, $this->request()));
                $this->fail('Expected source authorization refusal.');
            } catch (HttpException|ModelNotFoundException $error) {
                $this->assertTrue($error instanceof ModelNotFoundException || $error->getStatusCode() === 403);
            }
        }
        $this->assertSame(0, VisionProbeResolver::$calls);
        $this->assertSame(0, AiUsage::count());
        Http::assertNothingSent();
    }

    public function test_image_allowance_is_part_of_caps_and_unqualified_provider_does_not_consume_budget(): void
    {
        config(['ai.inference.features.vision_probe.actor_daily_tokens' => 8192]);
        $this->refuse(fn () => $this->inferProbe(), 'actor_daily_cap');
        $this->assertSame(0, AiUsage::count());
        config(['ai.inference.features.vision_probe.actor_daily_tokens' => 50000, 'ai.providers.openai.qualified' => false]);
        $this->refuse(fn () => $this->inferProbe(), 'provider_not_qualified');
        $usage = AiUsage::firstOrFail();
        $this->assertSame(0, $usage->used_units);
        $this->assertSame(0, $usage->reserved_units);
        Http::assertNothingSent();
    }

    public function test_flagged_photo_input_retains_only_opaque_references_and_never_calls_inference(): void
    {
        Http::swap(new Factory);
        Http::fake(fn () => Http::response(['results' => [['flagged' => true]]]));
        $this->refuse(fn () => $this->inferProbe(), 'inference_quarantined');
        Http::assertSentCount(1);
        $usage = AiUsage::firstOrFail();
        $this->assertSame('quarantined', $usage->state);
        $this->assertSame(0, $usage->used_units);
        $private = AiQuarantineSnapshot::firstOrFail()->private_content;
        $this->assertSame(VisionProbeResolver::input()->manifest(), $private);
        $this->assertStringNotContainsString(base64_encode(VisionProbeResolver::$image->bytes), json_encode($private));
    }

    public function test_output_only_flag_retains_exact_approved_media_refs_without_pixels_or_new_sources(): void
    {
        $calls = 0;
        Http::swap(new Factory);
        Http::fake(function ($request) use (&$calls) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => ++$calls === 2]]]);
            }

            return Http::response($this->reply($request->url()));
        });
        $this->refuse(fn () => $this->inferProbe(), 'inference_quarantined');
        Http::assertSentCount(3);
        $snapshot = AiQuarantineSnapshot::firstOrFail();
        $this->assertSame('output', $snapshot->stage);
        $this->assertSame(['text' => 'A bounded working hint.', 'media' => VisionProbeResolver::input()->manifest()['media']], $snapshot->private_content);
        $this->assertSame(140, AiUsage::firstOrFail()->used_units);
        $this->assertStringNotContainsString(base64_encode(VisionProbeResolver::$image->bytes), json_encode($snapshot->private_content));
    }

    public function test_revocation_during_moderation_prevents_photo_dispatch(): void
    {
        Http::swap(new Factory);
        Http::fake(function ($request) {
            VisionProbePolicy::$allowed = false;

            return Http::response($this->reply($request->url()));
        });
        $this->refuse(fn () => $this->inferProbe(), 'vision_source_unavailable');
        Http::assertSentCount(1);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
    }

    public function test_resolver_withdrawal_during_moderation_prevents_dispatch(): void
    {
        Http::swap(new Factory);
        Http::fake(function ($request) {
            config(['ai.inference.vision_resolvers' => []]);

            return Http::response($this->reply($request->url()));
        });
        $this->refuse(fn () => $this->inferProbe(), 'media_policy_required');
        Http::assertSentCount(1);
        $this->assertSame(0, AiUsage::firstOrFail()->used_units);
    }
}

class VisionProbePolicy implements InferenceFeaturePolicy
{
    public static int $owner;

    public static bool $allowed = true;

    public static bool $wrongHash = false;

    public function feature(): string
    {
        return 'vision_probe';
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless((int) $actor->id === self::$owner && app(CurrentSchool::class)->id() === 1, 403);
        $manifest = VisionProbeResolver::input()->manifest();
        if (self::$wrongHash) {
            $manifest['media'][0]['sha256'] = str_repeat('a', 64);
        }

        return new AuthorizedInferenceScope(1, (int) $actor->id, new InferenceRequest('vision_probe', $request->operationId, 'foundation', $manifest, ['approved-photo:opaque-photo-1']), audience: InferenceAudience::Staff);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        if (! self::$allowed) {
            throw new AiUnavailable('vision_source_unavailable');
        }
    }
}

class VisionProbeResolver implements VisionMediaResolver
{
    public static int $calls = 0;

    public static VisionImage $image;

    public static bool $swap = false;

    public static function input(): ResolvedVisionInput
    {
        return new ResolvedVisionInput('Check only the approved working crop.', [self::$image]);
    }

    public function resolve(User $actor, AuthorizedInferenceScope $scope): ResolvedVisionInput
    {
        self::$calls++;
        if (self::$swap) {
            app(CurrentSchool::class)->set(2);
        }

        return self::input();
    }
}
