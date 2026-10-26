<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Ephemeral provider data, never persisted in the metering or quarantine ledger. */
final readonly class ResolvedVisionInput
{
    public const MAXIMUM_ENCODED_LENGTH = 1398104;

    /** @param list<VisionImage> $images */
    public function __construct(public string $text, public array $images)
    {
        if (trim($text) === '' || strlen($text) > 32768 || ! mb_check_encoding($text, 'UTF-8') || str_contains($text, "\0")
            || ! array_is_list($images) || count($images) < 1 || count($images) > 3) {
            throw new AiUnavailable('vision_media_invalid');
        }
        $ids = [];
        foreach ($images as $image) {
            if (! $image instanceof VisionImage || in_array($image->id, $ids, true)) {
                throw new AiUnavailable('vision_media_invalid');
            }
            $ids[] = $image->id;
        }
    }

    public function manifest(): array
    {
        return ['text' => $this->text, 'media' => array_map(fn (VisionImage $image) => $image->reference(), $this->images)];
    }

    public function payload(): array
    {
        return ['text' => $this->text, 'images' => array_map(fn (VisionImage $image) => ['mime' => 'image/png', 'data' => base64_encode($image->bytes)], $this->images)];
    }

    /** Validate again at every adapter boundary; no arbitrary URLs or extra metadata. */
    public static function units(array $payload): int
    {
        if (array_keys($payload) !== ['text', 'images'] || ! is_string($payload['text']) || ! is_array($payload['images']) || ! array_is_list($payload['images'])) {
            throw new AiUnavailable('vision_media_invalid');
        }
        $images = [];
        foreach ($payload['images'] as $index => $image) {
            if (! is_array($image) || array_keys($image) !== ['mime', 'data'] || $image['mime'] !== 'image/png' || ! is_string($image['data']) || strlen($image['data']) > self::MAXIMUM_ENCODED_LENGTH) {
                throw new AiUnavailable('vision_media_invalid');
            }
            $bytes = base64_decode($image['data'], true);
            if ($bytes === false || base64_encode($bytes) !== $image['data']) {
                throw new AiUnavailable('vision_media_invalid');
            }
            $images[] = new VisionImage('image_'.$index, hash('sha256', $bytes), $bytes);
        }
        $resolved = new self($payload['text'], $images);

        // Conservative allowance for each bounded image; actual usage must still fit.
        return strlen($resolved->text) + count($images) * 8192;
    }
}
