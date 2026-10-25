<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ResolvedVisionInput;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Ai\Inference\VisionPhotoCropper;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\DeepSeekProvider;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Ai\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisionMediaTest extends TestCase
{
    private function original(): string
    {
        $image = imagecreatetruecolor(240, 120);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 4, 4, 'VISIBLE NAME CANARY', imagecolorallocate($image, 255, 0, 0));
        imagestring($image, 5, 4, 80, '1 / 2 = 0.5', imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function crop(bool $approved = true): VisionImage
    {
        return app(VisionPhotoCropper::class)->crop('opaque-crop', $this->original(), ['x' => 0, 'y' => 60, 'width' => 240, 'height' => 60], $approved);
    }

    private function refused(callable $call, string $reason): void
    {
        try {
            $call();
            $this->fail('Expected private image refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame($reason, $error->reason);
        }
    }

    public function test_explicit_approved_crop_removes_visible_name_pixels_outside_working_area(): void
    {
        $this->refused(fn () => $this->crop(false), 'vision_crop_approval_required');
        $crop = $this->crop();
        $pixels = imagecreatefromstring($crop->bytes);
        $red = 0;
        $blue = 0;
        for ($y = 0; $y < imagesy($pixels); $y++) {
            for ($x = 0; $x < imagesx($pixels); $x++) {
                $rgb = imagecolorat($pixels, $x, $y);
                if ($rgb === 0xFF0000) {
                    $red++;
                }
                if ($rgb === 0x0000FF) {
                    $blue++;
                }
            }
        }
        $this->assertSame(0, $red);
        $this->assertGreaterThan(0, $blue);
        $this->assertSame(240, imagesx($pixels));
        $this->assertSame(60, imagesy($pixels));
        $this->assertSame(hash('sha256', $crop->bytes), $crop->sha256);
        $this->assertSame('image/png', $crop->reference()['mime']);
        $this->refused(fn () => app(VisionPhotoCropper::class)->crop('opaque', $this->original(), ['x' => 240, 'y' => 0, 'width' => 10, 'height' => 10], true), 'vision_crop_invalid');
    }

    public function test_metadata_remote_urls_unbound_hashes_and_excess_images_are_refused(): void
    {
        $crop = $this->crop();
        $chunk = 'tEXtOriginalName=VISIBLE NAME CANARY';
        $metadata = pack('N', strlen($chunk) - 4).$chunk.hash('crc32b', $chunk, true);
        $poisoned = substr($crop->bytes, 0, -12).$metadata.substr($crop->bytes, -12);
        $this->refused(fn () => new VisionImage('opaque', hash('sha256', $poisoned), $poisoned), 'vision_media_invalid');
        $this->refused(fn () => new VisionImage('opaque', str_repeat('a', 64), $crop->bytes), 'vision_media_invalid');
        $this->refused(fn () => ResolvedVisionInput::units(['text' => 'working', 'images' => [['mime' => 'image/png', 'data' => 'https://private.invalid/name.png']]]), 'vision_media_invalid');
        $this->refused(fn () => new ResolvedVisionInput('working', [$crop, $crop]), 'vision_media_invalid');
        $this->refused(fn () => new ResolvedVisionInput('working', [$crop, $crop, $crop, $crop]), 'vision_media_invalid');
    }

    public function test_openai_and_anthropic_vision_shapes_and_multimodal_moderation_are_bounded(): void
    {
        config(['ai.inference.enabled' => true, 'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic',
            'ai.providers.anthropic.qualified' => true, 'ai.providers.anthropic.key' => 'synthetic']);
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            if (str_ends_with($request->url(), '/messages')) {
                return Http::response(['model' => 'test-model', 'role' => 'assistant', 'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'A working hint.']], 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]]);
            }

            return Http::response(['model' => 'test-model', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'A working hint.']]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120]]);
        });
        $input = (new ResolvedVisionInput('Approved working only', [$this->crop()]))->payload();
        $request = new ProviderRequest(Capability::Vision, (string) Str::uuid(), 'test-model', str_repeat('a', 64), 'Subject instructions', $input, 100, 12000);
        $this->assertSame(120, app(OpenAiProvider::class)->infer($request)->tokens);
        $this->assertSame(120, app(AnthropicProvider::class)->infer($request)->tokens);
        $this->assertSame([], app(OpenAiModerator::class)->flags(Capability::Vision, 'input', $input, new ModerationContext($request->operationId, $request->anonymousActor)));
        Http::assertSentCount(3);
        Http::assertSent(fn ($sent) => str_ends_with($sent->url(), '/messages') && $sent['messages'][0]['content'][1]['source']['type'] === 'base64'
            && $sent['messages'][0]['content'][1]['source']['media_type'] === 'image/png' && $sent['metadata']['user_id'] === str_repeat('a', 64));
        Http::assertSent(fn ($sent) => str_ends_with($sent->url(), '/chat/completions') && $sent['store'] === false && $sent['max_completion_tokens'] === 100);
        $this->refused(fn () => app(DeepSeekProvider::class)->preflight($request), 'capability_unavailable');
        Http::assertSentCount(3);
    }
}
